<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class CertificadoFirma
{
    /**
     * Valida un certificado .p12 y su contraseña usando el JAR firmador.
     *
     * @param string $p12Path Ruta al archivo .p12.
     * @param string $password Contraseña del certificado.
     * @return boolean
     * @throws Exception Si el JAR no puede procesar el archivo.
     */
    private function validateCertificateWithJar($p12Path, $password)
    {
        Log::info("Iniciando validación del certificado con JAR.");

        $jarPath = base_path('app/firmador/sri-fat.jar');
        $tempDir = storage_path('app/temp_jar_validation');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Crear un archivo XML temporal para el proceso de firma
        $dummyXmlPath = $tempDir . '/dummy.xml';
        file_put_contents($dummyXmlPath, '<test/>');

        $outputXmlPath = $tempDir . '/dummy_signed.xml';

        $command = sprintf(
            'java -jar %s %s %s %s %s %s',
            escapeshellarg($jarPath),
            escapeshellarg($p12Path),
            escapeshellarg($password),
            escapeshellarg($dummyXmlPath),
            escapeshellarg($tempDir),
            'dummy_signed.xml'
        );

        Log::info("Ejecutando comando de validación JAR: " . $command);
        exec($command, $output, $return_var);

        // Limpiar archivos temporales
        @unlink($dummyXmlPath);
        if (file_exists($outputXmlPath)) {
            @unlink($outputXmlPath);
        }
        @rmdir($tempDir);

        Log::info("Comando JAR finalizado. Código de retorno: " . $return_var);
        Log::info("Salida del comando JAR: " . implode("\n", $output));

        if ($return_var !== 0) {
            $errorOutput = implode("\n", $output);
            if (str_contains(strtolower($errorOutput), 'password')) {
                 throw new Exception('La contraseña del certificado parece ser incorrecta (verificado con JAR).');
            }
            throw new Exception('El JAR firmador no pudo procesar el archivo. El archivo puede estar corrupto o no ser un .p12 válido.');
        }

        Log::info("Validación con JAR exitosa.");
        return true;
    }

    public function handleCertificate($certificateFile, $certificateKey, $expectedRuc, $existingCertificatePath = null)
    {
        Log::info("Servicio CertificadoFirma: Iniciando manejo de certificado.");

        $tempP12Path = $certificateFile->getRealPath();

        // 1. Validar la integridad del archivo y la clave usando el método alternativo con JAR
        $this->validateCertificateWithJar($tempP12Path, $certificateKey);

        // 2. Si la validación con JAR es exitosa, proceder a extraer metadatos con OpenSSL
        try {
            Log::info("Validación con JAR exitosa, procediendo a extraer metadatos con OpenSSL.");
            $certificateContent = file_get_contents($tempP12Path);
            $certData = [];

            if (!openssl_pkcs12_read($certificateContent, $certData, $certificateKey)) {
                Log::error("Fallo en openssl_pkcs12_read DESPUÉS de la validación con JAR. Esto puede indicar un problema con la extensión OpenSSL de PHP.");
                throw new Exception('El archivo y la clave son válidos, pero ocurrió un error al leerlos con OpenSSL en el servidor.');
            }

            $x509Cert = $certData['cert'];
            $parsedCert = openssl_x509_parse($x509Cert);
            if (!$parsedCert) {
                throw new Exception('No se pudo analizar el certificado con OpenSSL.');
            }

            $validTo = $parsedCert['validTo_time_t'];
            if (time() > $validTo) {
                throw new Exception('El certificado ha caducado.');
            }

            $certRuc = $parsedCert['subject']['serialNumber'] ?? null;
            if (!$certRuc) {
                throw new Exception('No se pudo obtener el RUC del certificado.');
            }
            $certRuc = preg_replace('/[^0-9]/', '', $certRuc);
            if (substr($expectedRuc, 0, 10) !== $certRuc) {
                throw new Exception("El RUC del certificado ($certRuc) no coincide con el RUC del usuario ($expectedRuc).");
            }

            if ($existingCertificatePath && Storage::exists($existingCertificatePath)) {
                Storage::delete($existingCertificatePath);
            }

            $fileName = 'signature_' . uniqid() . '.p12';
            $filePath = $certificateFile->storeAs('signatures', $fileName);
            Log::info("Archivo guardado exitosamente en: " . $filePath);

            return [
                'signature_path' => $filePath,
                'signature_key' => encrypt($certificateKey),
                'expires_at' => date('Y-m-d H:i:s', $validTo),
            ];

        } catch (Exception $e) {
            Log::error("Excepción capturada en handleCertificate: " . $e->getMessage());
            throw $e;
        }
    }
}
