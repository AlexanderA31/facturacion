<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Services\ClaveAccesoBarcode;
use App\Services\SriComprobanteService;
use Illuminate\Support\Facades\Storage;
use PDF;

class PdfGeneratorService
{
    protected $sriService;

    public function __construct(SriComprobanteService $sriService)
    {
        $this->sriService = $sriService;
    }

    public function generate(Comprobante $comprobante): string
    {
        $clave_acceso = $comprobante->clave_acceso;

        // Generar y guardar el código de barras si no existe
        $barcodePath = "barcodes/{$clave_acceso}.png";
        if (!Storage::disk('public')->exists($barcodePath)) {
            $pngBase64 = ClaveAccesoBarcode::makeBase64($clave_acceso);
            $pngBinary = base64_decode($pngBase64);
            Storage::disk('public')->put($barcodePath, $pngBinary);
        }

        // Obtener el ambiente del comprobante
        $ambiente = strval($comprobante->ambiente);

        // Consultar el XML desde el SRI
        $xmlString = $this->sriService->consultarXmlAutorizado($clave_acceso, $ambiente);

        // Parsear el XML
        $xmlObject = simplexml_load_string($xmlString);

        // Extraer los datos para la vista
        $data = [
            'numeroAutorizacion' => $comprobante->clave_acceso,
            'fechaAutorizacion' => $comprobante->fecha_autorizacion,
            'infoTributaria' => $xmlObject->infoTributaria,
            'infoFactura' => $xmlObject->infoFactura,
            'detalles' => $xmlObject->detalles->detalle,
            'infoAdicional' => $xmlObject->infoAdicional ?? null,
            'logo_path' => $comprobante->user->logo_path ?? null,
            'user' => $comprobante->user,
            'barcode_path' => Storage::disk('public')->path($barcodePath),
        ];

        // Generar el PDF
        $pdf = PDF::loadView('pdf.invoice', $data);

        // Guardar el PDF en un archivo temporal en el disco público
        $facturaNumero = $xmlObject->infoTributaria->estab . '-' . $xmlObject->infoTributaria->ptoEmi . '-' . $xmlObject->infoTributaria->secuencial;
        $fileName = 'temp/FAC-' . $facturaNumero . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
