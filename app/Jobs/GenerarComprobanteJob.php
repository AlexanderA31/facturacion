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
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EmittoEmailService;
use App\Services\PdfGeneratorService;

class GenerarComprobanteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900];

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
    public function handle(ComprobanteGenerator $comprobanteGenerator, EmittoEmailService $emittoEmailService, PdfGeneratorService $pdfGenerator)
    {
        Log::info("Iniciando generación del comprobante ID: {$this->comprobante->id}");

        $this->comprobante->update(['estado' => EstadosComprobanteEnum::PROCESANDO->value]);

        $signedFilePath = null;

        try {
            // --- Atomic transaction for sequential number ---
            $preparedData = \DB::transaction(function () {
                $puntoEmisionLocked = $this->bloquearPuntoEmision();
                $preparedData = $this->prepararDatosComprobante($puntoEmisionLocked, json_decode($this->comprobante->payload, true));
                $this->actualizarSecuencial($puntoEmisionLocked, $preparedData['secuencial']);
                return $preparedData;
            }, 5); // 5 attempts in case of deadlock
            // --- Lock is released after this point ---

            // Generate XML
            $generado = $this->generarXML($comprobanteGenerator, $preparedData, $this->puntoEmision);
            $this->claveAcceso = $generado['accessKey'];

            // Guardar XML temporal
            $xmlFilePath = $this->guardarXMLTemporal($generado['xml'], $this->type->value);

            // Firmar XML
            $signedFilePath = $this->firmarComprobante($xmlFilePath);

            // Actualizar estado del comprobante (fuera de la transacción del secuencial)
            $this->actualizarEstadoFirmado($preparedData);

            // Enviar y autorizar
            $this->autorizarComprobanteFirmado($signedFilePath, $emittoEmailService, $pdfGenerator);

        } catch (\Throwable $e) {
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
        $proximo = (int)$puntoEmision->proximo_secuencial + 1;
        $puntoEmision->proximo_secuencial = str_pad($proximo, 9, '0', STR_PAD_LEFT);
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
        $xmlDir = storage_path('app/temp/comprobantes');
        if (!file_exists($xmlDir)) {
            mkdir($xmlDir, 0777, true);
        }
        $this->uniqueId = Str::uuid()->toString();
        $xmlFilePath = $xmlDir . '/' . $tipo . '_temporal_' . $this->uniqueId . '.xml';
        file_put_contents($xmlFilePath, $xml);
        return $xmlFilePath;
    }

    private function firmarXMLTemporal(string $xmlFilePath): string
    {
        try {
            $password = decrypt($this->user->signature_key);
            $signatureFilePath = storage_path('app/private/' . $this->user->signature_path);
            $outputDir = storage_path('app/temp/comprobantes_firmados');
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            $outputFile = 'signed_' . $this->uniqueId . '.xml';
            $signedFilePath = $outputDir . '/' . $outputFile;

            $command = "java -jar " . escapeshellarg(base_path('app/firmador/sri-fat.jar')) . " " .
                escapeshellarg($signatureFilePath) . " " .
                escapeshellarg($password) . " " .
                escapeshellarg($xmlFilePath) . " " .
                escapeshellarg($outputDir) . " " . escapeshellarg($outputFile);

            exec($command, $output, $return_var);

            if ($return_var !== 0 || !file_exists($signedFilePath)) {
                Log::error('Error ejecutando el firmador.', ['output' => $output]);
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
            @unlink($xmlFilePath);
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

    private function autorizarComprobanteFirmado(string $signedFilePath, EmittoEmailService $emittoEmailService, PdfGeneratorService $pdfGenerator): void
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

            $pdfPath = null;
            try {
                if ($this->user->enviar_factura_por_correo) {
                    $payload = json_decode($this->comprobante->payload, true);
                    $recipientEmail = $payload['infoAdicional']['email'] ?? null;

                    if ($recipientEmail) {
                        $numeroComprobante = "{$this->comprobante->establecimiento}-{$this->comprobante->punto_emision}-{$this->comprobante->secuencial}";
                        $subject = "Ha recibido su documento electrónico: FAC {$numeroComprobante}";

                        // Generate PDF and store it publicly
                        $pdfPath = $pdfGenerator->generate($this->comprobante, 'public');
                        $relativePath = str_replace(storage_path('app/public') . '/', '', $pdfPath);
                        $pdfUrl = Storage::disk('public')->url($relativePath);

                        // Prepare data for the email template
                        $logoUrl = null;
                        if ($this->user->logo_path) {
                            $logoUrl = Storage::disk('public')->url($this->user->logo_path);
                        }

                        $emailData = [
                            'logoUrl' => $logoUrl,
                            'claveAcceso' => $this->claveAcceso,
                            'total' => $this->getImporteTotal($payload),
                            'pdfUrl' => $pdfUrl,
                        ];

                        $message = view('emails.invoice', $emailData)->render();

                        $attachments = [
                            ['filename' => "{$this->claveAcceso}.xml", 'path' => $signedFilePath],
                            ['filename' => "{$this->claveAcceso}.pdf", 'path' => $pdfPath],
                        ];

                        $emittoEmailService->sendInvoiceEmail($this->user, $recipientEmail, $subject, $message, $attachments);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al intentar enviar el correo para el comprobante {$this->claveAcceso}: " . $e->getMessage());
            }

        } catch (SriException $e) {
            $errorMessage = $e->getMessage();
            if (Str::contains($errorMessage, ['SRI no disponible', 'Parsing WSDL'])) {
                Log::warning("SRI no disponible. Reintentando... [{$this->claveAcceso}]");
                $this->release();
                return;
            }
            $this->comprobante->update(['estado' => EstadosComprobanteEnum::RECHAZADO->value, 'error_message' => $errorMessage]);
            Log::error("❌ Comprobante rechazado por el SRI [{$this->claveAcceso}]: " . $errorMessage);
        } catch (\Exception $e) {
            $this->comprobante->update(['estado' => EstadosComprobanteEnum::FALLIDO->value, 'error_message' => $e->getMessage()]);
            Log::error("🔥 Error inesperado en autorización [{$this->claveAcceso}]: " . $e->getMessage());
        }
    }

    private function getImporteTotal(array $payload): float
    {
        // Based on XmlBlockGenerator, the payload is flat.
        if (isset($payload['importeTotal'])) {
            return (float) $payload['importeTotal'];
        }

        // Fallback for other potential document types that might be stored differently.
        if (isset($payload['valorModificacion'])) { // e.g., Nota de Crédito
            return (float) $payload['valorModificacion'];
        }

        return 0.0;
    }
}
