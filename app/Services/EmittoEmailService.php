<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class EmittoEmailService
{
    protected $baseUrl;
    protected $secretKey;
    protected $guzzleClient;

    public function __construct()
    {
        $this->baseUrl = config('services.emitto.base_url');
        $this->secretKey = config('services.emitto.secret');
        $this->guzzleClient = new Client();
    }

    /**
     * Sends an invoice email using the Emitto API with Guzzle.
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
            $response = $this->guzzleClient->request('POST', "{$this->baseUrl}/email/send", [
                'headers' => [
                    'x-key-emitto' => $this->secretKey,
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'from' => config('mail.from.address', 'noreply@example.com'),
                    'subjectEmail' => $subject,
                    'sendTo' => [$recipientEmail],
                    'message' => $message,
                    'attachments' => $attachments,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                Log::error('EmittoEmailService: Falló el envío de correo.', [
                    'status' => $statusCode,
                    'response' => (string) $response->getBody(),
                ]);
                return false;
            }

            Log::info('EmittoEmailService: Correo enviado exitosamente a ' . $recipientEmail, [
                'status' => $statusCode,
            ]);
            return true;

        } catch (GuzzleException $e) {
            Log::error('EmittoEmailService: Excepción al enviar correo (Guzzle).', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
