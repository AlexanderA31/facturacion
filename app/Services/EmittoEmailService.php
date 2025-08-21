<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmittoEmailService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.emitto.base_url');
        $this->secretKey = config('services.emitto.secret');
    }

    /**
     * Sends an invoice email using the Emitto API.
     *
     * @param string $recipientEmail
     * @param string $subject
     * @param string $message
     * @param array $attachments Array of attachments, each with 'filename' and 'path'.
     * @return bool
     * @throws \Exception
     */
    public function sendInvoiceEmail(string $recipientEmail, string $subject, string $message, array $attachments): bool
    {
        if (!$this->secretKey) {
            Log::error('EmittoEmailService: La clave secreta de Emitto no está configurada.');
            // No lanzar excepción para no detener el proceso de facturación si el correo es opcional.
            // Se podría cambiar a lanzar una excepción si el envío de correo es crítico.
            return false;
        }

        try {
            $response = Http::withHeaders([
                'x-key-emitto' => $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/email/send", [
                'from' => config('mail.from.address', 'noreply@example.com'),
                'subjectEmail' => $subject,
                'sendTo' => [$recipientEmail],
                'message' => $message,
                'attachments' => $attachments,
            ]);

            if ($response->failed()) {
                Log::error('EmittoEmailService: Falló el envío de correo.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                // Opcional: lanzar una excepción para reintentar el trabajo si es necesario.
                // throw new \Exception("Error al enviar correo: " . $response->body());
                return false;
            }

            Log::info('EmittoEmailService: Correo enviado exitosamente a ' . $recipientEmail);
            return true;

        } catch (\Exception $e) {
            Log::error('EmittoEmailService: Excepción al enviar correo.', [
                'message' => $e->getMessage(),
            ]);
            throw $e; // Relanzar la excepción para que el job que lo llama pueda manejarla.
        }
    }
}
