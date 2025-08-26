<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use phpseclib3\File\PKCS12;
use phpseclib3\File\X509;

class CertificadoFirma
{
    public function handleCertificate($certificateFile, $certificateKey, $expectedRuc, $existingCertificatePath = null)
    {
        Log::info("Servicio CertificadoFirma: Iniciando manejo de certificado.");

        try {
            // 1. Validar y leer el contenido del certificado
            Log::info("Paso 1: Leyendo contenido del certificado.");
            $certificateContent = file_get_contents($certificateFile->getRealPath());

            Log::info("Intentando leer el archivo PKCS12 con phpseclib.");
            // Permitir algoritmos MAC antiguos que pueden estar en algunos certificados.
            PKCS12::setEnableLegacyMac(true);
            $certData = PKCS12::load($certificateContent, $certificateKey);

            if (empty($certData) || !isset($certData['cert'])) {
                Log::error("Fallo al cargar el archivo PKCS12 con phpseclib. El archivo o la clave son inválidos.");
                throw new Exception('El archivo del certificado o la clave son inválidos.');
            }
            Log::info("Lectura de PKCS12 con phpseclib exitosa.");

            // 2. Validar la fecha de expiración y el RUC usando phpseclib
            Log::info("Paso 2: Validando certificado con phpseclib.");
            $x509 = new X509();
            if (!$x509->loadX509($certData['cert'])) {
                Log::error("Fallo al cargar el certificado X509 con phpseclib.");
                throw new Exception('No se pudo analizar el certificado.');
            }

            // Validar fecha de expiración
            $validTo = $x509->getEndDate();
            if ($validTo === false) {
                Log::error("No se pudo obtener la fecha de expiración del certificado.");
                throw new Exception('No se pudo analizar el certificado.');
            }
            if (new \DateTime() > $validTo) {
                Log::error("El certificado ha caducado. Fecha de expiración: " . $validTo->format('Y-m-d H:i:s'));
                throw new Exception('El certificado ha caducado.');
            }
            Log::info("Validación de fecha de expiración exitosa.");

            // 3. Validar que el RUC del certificado coincida con el del usuario
            Log::info("Paso 3: Validando RUC.");
            $subjectDN = $x509->getSubjectDN(X509::DN_ARRAY);
            $certRuc = $subjectDN['serialNumber'] ?? null;

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
                'expires_at' => $validTo->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error("Excepción capturada en handleCertificate: " . $e->getMessage());
            // Re-lanzamos la excepción para que el controlador la maneje
            throw $e;
        }
    }
}
