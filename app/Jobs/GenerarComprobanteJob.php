<?php

namespace App\Jobs;

use App\Exceptions\SriException;
use App\Services\ComprobanteGenerator;
use App\Services\DocumentData;
use App\Models\Comprobante;
use App\Models\User;
use App\Services\SriComprobanteService;
use App\Enums\TipoComprobanteEnum;
use App\Enums\EstadosComprobanteEnum;
use App\Models\PuntoEmision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\EmittoEmailService;

class GenerarComprobanteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Comprobante $comprobante;
    protected User $user;
    public PuntoEmision $puntoEmision;
    public TipoComprobanteEnum $type;
    protected $uniqueId;
    protected $claveAcceso;

    public function __construct(Comprobante $comprobante, User $user, PuntoEmision $puntoEmision, TipoComprobanteEnum $type)
    {
        $this->comprobante = $comprobante;
        $this->user = $user;
        $this->puntoEmision = $puntoEmision;
        $this->type = $type;
    }

    /**
     * Summary of handle
     * @param \App\Services\ComprobanteGenerator $comprobanteGenerator
     * @throws \Exception
     * @return void
     */
    public function handle(ComprobanteGenerator $comprobanteGenerator, EmittoEmailService $emittoEmailService)
    {
        Log::info("Iniciando generación del comprobante ID: {$this->comprobante->id}");

        $this->comprobante->update(['estado' => EstadosComprobanteEnum::PROCESANDO->value]);

        $transactionStarted = false;
        $signedFilePath = null;

        try {
            // Comenzar transacción
            \DB::beginTransaction();
            $transactionStarted = true;

            // Bloquear cambios en el punto de emisión
            $puntoEmision = $this->bloquearPuntoEmision();

            // Preparar los datos para el comprobante
            $preparedData = $this->prepararDatosComprobante($puntoEmision, json_decode($this->comprobante->payload, true));

            // Actualizar secuencial
            $this->actualizarSecuencial($puntoEmision, $preparedData['secuencial']);

            // Generar XML
            $generado = $this->generarXML($comprobanteGenerator, $preparedData, $puntoEmision);
            $this->claveAcceso = $generado['accessKey'];

            // Guardar XML temporal
            $xmlFilePath = $this->guardarXMLTemporal($generado['xml'], $this->type->value);

            // Firmar XML
            $signedFilePath = $this->firmarComprobante($xmlFilePath);

            // Guardar cambios
            \DB::commit();

            // Actualizar estado
            $this->actualizarEstadoFirmado($preparedData);

            // Enviar y autorizar
            $this->autorizarComprobanteFirmado($signedFilePath, $emittoEmailService);
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                \DB::rollBack();
            }

            $this->comprobante->update([
                'estado' => EstadosComprobanteEnum::FALLIDO->value,
                'error_message' => $e->getMessage(),
            ]);
            Log::error("Error en generación del comprobante [ID {$this->comprobante->id}]: " . $e->getMessage());
            throw $e;
        } finally {
            if ($signedFilePath && file_exists($signedFilePath)) {
                @unlink($signedFilePath);
                Log::info("Archivo firmado eliminado: $signedFilePath");
            }
        }
    }

    private function bloquearPuntoEmision(): PuntoEmision
    {
        return PuntoEmision::where('id', $this->puntoEmision->id)->lockForUpdate()->first();
    }

    private function prepararDatosComprobante(PuntoEmision $puntoEmision, array $data = []): array
    {
        return app(DocumentData::class)->prepareData($puntoEmision, $this->user, $data);
    }

    private function actualizarSecuencial(PuntoEmision $puntoEmision, string $secuencial): void
    {
        $puntoEmision->ultimoSecuencial = $secuencial;
        $puntoEmision->save();
    }

    private function generarXML(ComprobanteGenerator $comprobanteGenerator, array $preparedData, PuntoEmision $puntoEmision): array
    {
        try {
            return $comprobanteGenerator->factura($preparedData, $this->user, $puntoEmision);
        } catch (\Exception $e) {
            throw new \Exception('Error en el generador de comprobante: ' . $e->getMessage());
        }
    }

    private function guardarXMLTemporal(string $xml, string $tipo): string
    {
        $xmlDir = storage_path('app/public/comprobantes');
        if (!file_exists($xmlDir)) {
            mkdir($xmlDir, 0777, true);
        }

        $this->uniqueId = Str::uuid()->toString(); // asegúrate de declarar esto como propiedad
        $xmlFilePath = $xmlDir . '/' . $tipo . '_temporal_' . $this->uniqueId . '.xml';
        file_put_contents($xmlFilePath, $xml);
        Log::info('XML temporal guardado');
        return $xmlFilePath;
    }

    private function firmarXMLTemporal(string $xmlFilePath): string
    {
        try {
            $password = decrypt($this->user->signature_key);
            $signatureFilePath = storage_path('app/private/' . $this->user->signature_path);

            $outputDir = storage_path('app/public/comprobantes/firmados');
            $outputFile = 'outputFile_' . $this->uniqueId . '.xml';

            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            if (!is_writable($outputDir)) {
                Log::error('El directorio de salida no tiene permisos de escritura: ' . $outputDir);
                throw new \Exception('El directorio de salida no tiene permisos de escritura.');
            }

            $command = "java -jar " . escapeshellarg(base_path('app/firmador/sri-fat.jar')) . " " .
                escapeshellarg($signatureFilePath) . " " .
                escapeshellarg($password) . " " .
                escapeshellarg($xmlFilePath) . " " .
                escapeshellarg($outputDir) . " " . escapeshellarg($outputFile);

            exec($command, $output, $return_var);
            Log::info('Código de retorno de la firma: ' . $return_var);

            $signedFilePath = $outputDir . '/' . $outputFile;

            if (!file_exists($signedFilePath)) {
                Log::error('El archivo firmado no se ha creado: ' . $signedFilePath);
                throw new \Exception('El archivo firmado no se ha creado.');
            }

            if ($return_var !== 0) {
                Log::error('Error al ejecutar el firmador: ' . implode("\n", $output));
                throw new \Exception('Error ejecutando el firmador.');
            }

            return $signedFilePath;
        } catch (\Exception $e) {
            throw new \Exception('Error al firmar el XML: ' . $e->getMessage());
        }
    }

    private function firmarComprobante(string $xmlFilePath): string
    {
        try {
            return $this->firmarXMLTemporal($xmlFilePath);
        } catch (\Exception $e) {
            Log::error("Error durante la firma del XML: " . $e->getMessage());
            throw $e;
        } finally {
            $this->eliminarXMLTemporal($xmlFilePath);
        }
    }

    private function eliminarXMLTemporal(string $xmlFilePath): void
    {
        if (file_exists($xmlFilePath)) {
            unlink($xmlFilePath);
            Log::info("XML temporal eliminado");
        }
    }

    private function actualizarEstadoFirmado(array $preparedData): void
    {
        $this->comprobante->update([
            'estado' => EstadosComprobanteEnum::FIRMADO->value,
            'clave_acceso' => $this->claveAcceso,
            'establecimiento' => $preparedData['estab'],
            'punto_emision' => $preparedData['ptoEmi'],
            'secuencial' => $preparedData['secuencial'],
            'procesado_en' => now(),
        ]);
    }

    private function autorizarComprobanteFirmado(string $signedFilePath, EmittoEmailService $emittoEmailService): void
    {
        try {
            $sriSender = new SriComprobanteService();
            $response = $sriSender->enviarYAutorizarComprobante(
                file_get_contents($signedFilePath),
                $this->claveAcceso
            );

            if (!$response['success']) {
                throw new SriException($response['error'] ?? 'El comprobante no fue recibido');
            }

            $autorizacion = $response['autorizacion'];

            $this->comprobante->update([
                'estado' => EstadosComprobanteEnum::AUTORIZADO->value,
                'fecha_autorizacion' => $autorizacion->fechaAutorizacion ?? now(),
            ]);

            Log::info("✅ Comprobante autorizado: {$this->claveAcceso}");

            // Enviar correo si está activado
            try {
                if ($this->user->enviar_factura_por_correo) {
                    $payload = json_decode($this->comprobante->payload, true);
                    $recipientEmail = $payload['infoAdicional']['email'] ?? null;

                    if ($recipientEmail) {
                        $subject = "Nuevo Comprobante Electrónico: {$this->claveAcceso}";
                        $message = "Estimado cliente, se ha generado un nuevo comprobante electrónico. Puede encontrar los detalles adjuntos.";
                        $attachments = [
                            ['filename' => "{$this->claveAcceso}.xml", 'path' => $signedFilePath]
                            // Nota: La generación y adjunto de PDF no está implementada en este job.
                        ];

                        $emittoEmailService->sendInvoiceEmail($recipientEmail, $subject, $message, $attachments);
                    } else {
                        Log::warning("No se encontró correo de destinatario para el comprobante {$this->claveAcceso}.");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al intentar enviar el correo para el comprobante {$this->claveAcceso}: " . $e->getMessage());
                // No relanzar la excepción para no marcar el job como fallido si solo el correo falla.
            }

        } catch (SriException $e) {
            $this->comprobante->update([
                'estado' => EstadosComprobanteEnum::RECHAZADO->value,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            Log::error("❌ Comprobante rechazado por el SRI [{$this->claveAcceso}]: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->comprobante->update([
                'estado' => EstadosComprobanteEnum::FALLIDO->value,
                'error_message' => $e->getMessage(),
            ]);
            Log::error("🔥 Error inesperado en autorización [{$this->claveAcceso}]: " . $e->getMessage());
        }
    }
}
