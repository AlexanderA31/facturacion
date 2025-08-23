<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnexoTransaccionalRequest;
use App\Services\AnexoTransaccionalService;

class AnexoTransaccionalController extends Controller
{
    protected $anexoService;

    public function __construct(AnexoTransaccionalService $anexoService)
    {
        $this->anexoService = $anexoService;
    }

    /**
     * Generate the Anexo Transaccional Simplificado (ATS) report.
     *
     * @param  \App\Http\Requests\AnexoTransaccionalRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function generarAnexo(AnexoTransaccionalRequest $request)
    {
        $validated = $request->validated();
        $year = $validated['year'];
        $month = $validated['month'];

        try {
            $xml = $this->anexoService->generarXml($year, $month);

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="ats.xml"',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el anexo: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
