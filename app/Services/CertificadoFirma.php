<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class CertificadoFirma
{
    public function handleCertificate($certificateFile, $certificateKey, $expectedRuc, $existingCertificatePath = null)
    {
        Log::info("Servicio CertificadoFirma: Iniciando manejo de certificado.");

        try {
            // 1. Validar y leer el contenido del certificado
            Log::info("Paso 1: Leyendo contenido del certificado.");
            $certificateContent = file_get_contents($certificateFile->getRealPath());
            $certData = [];

            Log::info("Intentando leer el archivo PKCS12 con openssl_pkcs12_read.");
            if (!openssl_pkcs12_read($certificateContent, $certData, $certificateKey)) {
                Log::error("Fallo en openssl_pkcs12_read. La clave o el archivo son inválidos.");
                throw new Exception('El archivo del certificado o la clave son inválidos.');
            }
            Log::info("openssl_pkcs12_read exitoso.");

            // 2. Validar la fecha de expiración
            Log::info("Paso 2: Validando fecha de expiración.");
            $x509Cert = $certData['cert'];
            $parsedCert = openssl_x509_parse($x509Cert);
            if (!$parsedCert) {
                Log::error("Fallo en openssl_x509_parse. No se pudo analizar el certificado.");
                throw new Exception('No se pudo analizar el certificado.');
            }
            Log::info("openssl_x509_parse exitoso.");

            $validTo = $parsedCert['validTo_time_t'];
            Log::info("Fecha de expiración del certificado (timestamp): " . $validTo);
            if (time() > $validTo) {
                Log::error("El certificado ha caducado. Fecha de expiración: " . date('Y-m-d H:i:s', $validTo));
                throw new Exception('El certificado ha caducado.');
            }
            Log::info("Validación de fecha de expiración exitosa.");

            // 3. Validar que el RUC del certificado coincida con el del usuario
            Log::info("Paso 3: Validando RUC.");
            $certRuc = $parsedCert['subject']['serialNumber'] ?? null;

            if (!$certRuc) {
                Log::error("No se pudo encontrar el serialNumber en el certificado.");
                throw new Exception('No se pudo obtener el RUC del certificado.');
            }
            Log::info("serialNumber encontrado en el certificado: " . $certRuc);

            $certRuc = preg_replace('/[^0-9]/', '', $certRuc);
            Log::info("RUC limpiado del certificado: " . $certRuc . ". RUC esperado del usuario: " . $expectedRuc);

            if (substr($expectedRuc, 0, 10) !== $certRuc) {
                Log::error("El RUC no coincide. Certificado: " . $certRuc . " | Usuario: " . $expectedRuc);
                throw new Exception("El RUC del certificado no coincide con el RUC del usuario.");
            }
            Log::info("Validación de RUC exitosa.");

            // 4. Reemplazar el certificado anterior si existe
            if ($existingCertificatePath && Storage::exists($existingCertificatePath)) {
                Log::info("Paso 4: Eliminando certificado anterior: " . $existingCertificatePath);
                Storage::delete($existingCertificatePath);
            }

            // 5. Guardar el archivo en el almacenamiento
            $fileName = 'signature_' . uniqid() . '.p12';
            Log::info("Paso 5: Guardando nuevo archivo de firma como: " . $fileName);
            $filePath = $certificateFile->storeAs('signatures', $fileName);
            Log::info("Archivo guardado exitosamente en: " . $filePath);

            return [
                'signature_path' => $filePath,
                'signature_key' => encrypt($certificateKey),
                'expires_at' => date('Y-m-d H:i:s', $validTo),
            ];
        } catch (Exception $e) {
            Log::error("Excepción capturada en handleCertificate: " . $e->getMessage());
            // Re-lanzamos la excepción para que el controlador la maneje
            throw $e;
        }
    }
}
