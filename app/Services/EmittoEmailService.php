<?php

namespace App\Services;

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
     * Sends an invoice email using the Emitto API with native cURL.
     *
     * @param string $recipientEmail
     * @param string $subject
     * @param string $message
     * @param array $attachments Array of attachments, each with 'filename' and 'path' (URL).
     * @return bool
     * @throws \Exception
     */
    public function sendInvoiceEmail(string $recipientEmail, string $subject, string $message, array $attachments): bool
    {
        if (!$this->secretKey) {
            Log::error('EmittoEmailService: La clave secreta de Emitto no está configurada.');
            return false;
        }

        try {
            $payload = json_encode([
                'from' => config('mail.from.address', 'noreply@example.com'),
                'subjectEmail' => $subject,
                'sendTo' => [$recipientEmail],
                'message' => $message,
                'attachments' => $attachments,
            ]);

            $headers = [
                'x-key-emitto: ' . $this->secretKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($payload),
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/email/send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($curlError) {
                throw new \Exception('cURL Error: ' . $curlError);
            }

            if ($httpCode >= 400) {
                Log::error('EmittoEmailService: Falló el envío de correo.', [
                    'status' => $httpCode,
                    'response' => $responseBody,
                ]);
                return false;
            }

            Log::info('EmittoEmailService: Correo enviado exitosamente a ' . $recipientEmail, [
                'status' => $httpCode,
            ]);
            return true;

        } catch (\Exception $e) {
            Log::error('EmittoEmailService: Excepción al enviar correo.', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
