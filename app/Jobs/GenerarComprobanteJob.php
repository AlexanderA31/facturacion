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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\EmittoEmailService;
use App\Services\PdfGeneratorService;

class GenerarComprobanteJob implements ShouldQueue, ShouldBeUnique
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

    public function handle(ComprobanteGenerator $comprobanteGenerator, EmittoEmailService $emittoEmailService, PdfGeneratorService $pdfGenerator)
    {
        Log::info("Iniciando generación del comprobante ID: {$this->comprobante->id}");
        $this->comprobante->update(['estado' => EstadosComprobanteEnum::PROCESANDO->value]);

        $transactionStarted = false;
        try {
            \DB::beginTransaction();
            $transactionStarted = true;

            $puntoEmision = PuntoEmision::where('id', $this->puntoEmision->id)->lockForUpdate()->first();
            $preparedData = $this->prepararDatosComprobante($puntoEmision, json_decode($this->comprobante->payload, true));
            $this->actualizarSecuencial($puntoEmision, $preparedData['secuencial']);

            $generado = $this->generarXML($comprobanteGenerator, $preparedData, $puntoEmision);
            $this->claveAcceso = $generado['accessKey'];

            $xmlFilePath = $this->guardarXMLTemporal($generado['xml'], $this->type->value);
            $signedFileRelativePath = $this->firmarComprobante($xmlFilePath);

            \DB::commit();

            $this->actualizarEstadoFirmado($preparedData);
            $this->autorizarComprobanteFirmado($signedFileRelativePath, $emittoEmailService, $pdfGenerator, $preparedData);

        } catch (\Throwable $e) {
            if ($transactionStarted) {
                \DB::rollBack();
            }
            $this->comprobante->update(['estado' => EstadosComprobanteEnum::FALLIDO->value, 'error_message' => $e->getMessage()]);
            Log::error("Error en generación del comprobante [ID {$this->comprobante->id}]: " . $e->getMessage());
            throw $e;
        }
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
        return $comprobanteGenerator->factura($preparedData, $this->user, $puntoEmision);
    }

    private function guardarXMLTemporal(string $xml, string $tipo): string
    {
        $this->uniqueId = Str::uuid()->toString();
        $xmlFilePath = storage_path('app/temp/' . $tipo . '_temporal_' . $this->uniqueId . '.xml');
        file_put_contents($xmlFilePath, $xml);
        return $xmlFilePath;
    }

    private function firmarXMLTemporal(string $xmlFilePath): string
    {
        $tempSignedFile = null;
        try {
            $password = decrypt($this->user->signature_key);
            $signatureFilePath = storage_path('app/private/' . $this->user->signature_path);

            $tempOutputDir = storage_path('app/temp/firmados');
            if (!file_exists($tempOutputDir)) mkdir($tempOutputDir, 0777, true);
            $tempOutputFile = 'signed_' . $this->uniqueId . '.xml';
            $tempSignedFile = $tempOutputDir . '/' . $tempOutputFile;

            $command = "java -jar " . escapeshellarg(base_path('app/firmador/sri-fat.jar')) . " " .
                escapeshellarg($signatureFilePath) . " " . escapeshellarg($password) . " " .
                escapeshellarg($xmlFilePath) . " " . escapeshellarg($tempOutputDir) . " " . escapeshellarg($tempOutputFile);

            exec($command, $output, $return_var);

            if ($return_var !== 0 || !file_exists($tempSignedFile)) {
                Log::error('Error ejecutando el firmador.', ['output' => $output]);
                throw new \Exception('Error ejecutando el firmador.');
            }

            $signedFileContent = file_get_contents($tempSignedFile);
            $relativeStoragePath = 'comprobantes/firmados/' . $this->claveAcceso . '.xml';
            Storage::disk('public')->put($relativeStoragePath, $signedFileContent);

            return $relativeStoragePath;
        } finally {
            if ($tempSignedFile && file_exists($tempSignedFile)) @unlink($tempSignedFile);
        }
    }

    private function firmarComprobante(string $xmlFilePath): string
    {
        try {
            return $this->firmarXMLTemporal($xmlFilePath);
        } finally {
            if (file_exists($xmlFilePath)) @unlink($xmlFilePath);
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

    private function autorizarComprobanteFirmado(string $signedFileRelativePath, EmittoEmailService $emittoEmailService, PdfGeneratorService $pdfGenerator, array $preparedData): void
    {
        try {
            $sriSender = new SriComprobanteService();
            $response = $sriSender->enviarYAutorizarComprobante(Storage::disk('public')->get($signedFileRelativePath), $this->claveAcceso);

            if (!$response['success']) {
                throw new SriException($response['error'] ?? 'El comprobante no fue recibido');
            }

            $this->comprobante->update([
                'estado' => EstadosComprobanteEnum::AUTORIZADO->value,
                'fecha_autorizacion' => $response['autorizacion']->fechaAutorizacion ?? now(),
            ]);

            Log::info("✅ Comprobante autorizado: {$this->claveAcceso}");

            if ($this->user->enviar_factura_por_correo) {
                $this->enviarCorreoFactura($signedFileRelativePath, $emittoEmailService, $pdfGenerator, $preparedData);
            }

        } catch (SriException $e) {
            if (Str::contains($e->getMessage(), ['SRI no disponible', 'Parsing WSDL'])) {
                Log::warning("SRI no disponible. Reintentando... [{$this->claveAcceso}]");
                $this->release();
                return;
            }
            $this->comprobante->update(['estado' => EstadosComprobanteEnum::RECHAZADO->value, 'error_message' => $e->getMessage()]);
            Log::error("❌ Comprobante rechazado por el SRI [{$this->claveAcceso}]: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->comprobante->update(['estado' => EstadosComprobanteEnum::FALLIDO->value, 'error_message' => $e->getMessage()]);
            Log::error("🔥 Error inesperado en autorización [{$this->claveAcceso}]: " . $e->getMessage());
        }
    }

    private function enviarCorreoFactura(string $signedFileRelativePath, EmittoEmailService $emittoEmailService, PdfGeneratorService $pdfGenerator, array $preparedData): void
    {
        try {
            $payload = json_decode($this->comprobante->payload, true);
            $recipientEmail = $payload['infoAdicional']['email'] ?? null;

            if (!$recipientEmail) {
                Log::warning("No se encontró correo de destinatario para el comprobante {$this->claveAcceso}.");
                return;
            }

            $pdfRelativePath = $pdfGenerator->generate($this->comprobante);

            $data_for_view = [
                'logo_path'     => $this->user->logo_path ? Storage::disk('public')->url($this->user->logo_path) : null,
                'user_name'     => $this->user->name,
                'client_name'   => $payload['razonSocialComprador'],
                'invoice_number'=> $preparedData['estab'] . '-' . $preparedData['ptoEmi'] . '-' . $preparedData['secuencial'],
                'invoice_date'  => $payload['fechaEmision'],
                'invoice_total' => $payload['importeTotal'],
                'preview_url'   => Storage::disk('public')->url($pdfRelativePath),
            ];

            $message = view('emails.invoice', $data_for_view)->render();

            $attachments = [
                ['filename' => "{$this->claveAcceso}.xml", 'path' => Storage::disk('public')->path($signedFileRelativePath)],
                ['filename' => "{$this->claveAcceso}.pdf", 'path' => Storage::disk('public')->path($pdfRelativePath)]
            ];

            $fromAddress = $this->user->from_email ?? config('mail.from.address');

            $emittoEmailService->sendInvoiceEmail($recipientEmail, "Nuevo Comprobante Electrónico: {$this->claveAcceso}", $message, $attachments, $fromAddress);

        } catch (\Exception $e) {
            Log::error("Error al intentar enviar el correo para el comprobante {$this->claveAcceso}: " . $e->getMessage());
        }
    }
}
