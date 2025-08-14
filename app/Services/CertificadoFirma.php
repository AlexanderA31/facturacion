<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class CertificadoFirma
{
    /**
     * Carga, valida y guarda el certificado y su clave.
     *
     * @param \Illuminate\Http\UploadedFile $certificateFile
     * @param string $certificateKey
     * @param string $expectedRuc
     * @param string|null $existingCertificatePath
     * @return array
     * @throws Exception
     */
    public function handleCertificate($certificateFile, $certificateKey, $expectedRuc, $existingCertificatePath = null)
    {
        // 1. Validar y leer el contenido del certificado
        $certificateContent = file_get_contents($certificateFile->getRealPath());
        $certData = [];

        if (!openssl_pkcs12_read($certificateContent, $certData, $certificateKey)) {
            throw new Exception('El archivo del certificado o la clave son inválidos.');
        }

        // 2. Validar la fecha de expiración
        $x509Cert = $certData['cert'];
        $parsedCert = openssl_x509_parse($x509Cert);
        if (!$parsedCert) {
            throw new Exception('No se pudo analizar el certificado.');
        }

        $validTo = $parsedCert['validTo_time_t']; // Fecha de expiración como timestamp
        if (time() > $validTo) {
            throw new Exception('El certificado ha caducado.');
        }

        // 3. Validar que el RUC del certificado coincida con el del usuario
        $certRuc = $parsedCert['subject']['serialNumber'] ?? null;

        if (!$certRuc) {
            throw new Exception('No se pudo obtener el RUC del certificado.');
        }

        $certRuc = preg_replace('/[^0-9]/', '', $certRuc); // Limpiamos todo menos números

        if (substr($expectedRuc, 0, 10) !== $certRuc) {
            throw new Exception("El RUC del certificado ($certRuc) no coincide con el RUC del usuario ($expectedRuc).");
        }

        // 4. Reemplazar el certificado anterior si existe
        if ($existingCertificatePath && Storage::exists($existingCertificatePath)) {
            Storage::delete($existingCertificatePath);
        }

        // 5. Guardar el archivo en el almacenamiento
        $fileName = 'signature_' . uniqid() . '.p12';
        $filePath = $certificateFile->storeAs('signatures', $fileName);

        return [
            'signature_path' => $filePath,
            'signature_key' => encrypt($certificateKey), // Encriptar la clave para almacenarla de manera segura
            'expires_at' => date('Y-m-d H:i:s', $validTo), // Fecha de expiración formateada
        ];
    }
}
