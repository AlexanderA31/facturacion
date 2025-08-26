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
            // Cargar configuración de OpenSSL personalizada para habilitar el proveedor legacy.
            // Esto es un workaround para certificados antiguos.
            $customOpenSSLConf = base_path('config/openssl.cnf');
            if (file_exists($customOpenSSLConf)) {
                putenv('OPENSSL_CONF=' . $customOpenSSLConf);
                Log::info('Cargando configuración de OpenSSL personalizada: ' . $customOpenSSLConf);
            }

            // 1. Validar y leer el contenido del certificado
            Log::info("Paso 1: Leyendo contenido del certificado.");
            $certificateContent = file_get_contents($certificateFile->getRealPath());
            $certData = [];

            Log::info("Intentando leer el archivo PKCS12 con openssl_pkcs12_read.");
            if (!openssl_pkcs12_read($certificateContent, $certData, $certificateKey)) {
                Log::error("Fallo en openssl_pkcs12_read. La clave o el archivo son inválidos.");
                // Añadimos un log más detallado para entender la causa raíz del fallo.
                while ($error = openssl_error_string()) {
                    Log::error("Detalle de OpenSSL: " . $error);
                }
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

            $rucMatch = false;
            // Caso 1: El RUC del usuario (13 dígitos) comienza con el RUC/Cédula del certificado (10 dígitos).
            // Esto cubre el caso de personas naturales donde el RUC es Cédula + '001'.
            if (strlen($expectedRuc) == 13 && strlen($certRuc) == 10 && str_starts_with($expectedRuc, $certRuc)) {
                $rucMatch = true;
            }

            // Caso 2: El RUC del usuario (13 dígitos) es idéntico al RUC del certificado (13 dígitos).
            // Esto cubre el caso de empresas donde el certificado contiene el RUC completo.
            if (strlen($expectedRuc) == 13 && strlen($certRuc) == 13 && $expectedRuc === $certRuc) {
                $rucMatch = true;
            }

            if (!$rucMatch) {
                Log::error("El RUC no coincide. Certificado: " . $certRuc . " | Usuario: " . $expectedRuc);
                throw new Exception("El RUC del certificado ($certRuc) no coincide con el RUC del usuario ($expectedRuc).");
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
