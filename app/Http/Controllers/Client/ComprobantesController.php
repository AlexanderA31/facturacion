<?php

namespace App\Http\Controllers\Client;

use App\Enums\AmbientesEnum;
use App\Enums\EstadosComprobanteEnum;
use App\Enums\TipoComprobanteEnum;
use App\Exceptions\SriException;
use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\PuntoEmision;
use App\Http\Requests\FacturaRequest;
use App\Jobs\GenerarComprobanteJob;
use App\Services\ClaveAccesoBarcode;
use App\Services\SriComprobanteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;
use ZipArchive;


class ComprobantesController extends Controller
{
    protected $sriService;

    public function __construct(SriComprobanteService $sriService)
    {
        $this->sriService = $sriService;
    }

    private function validarClaveAcceso(string $claveAcceso)
    {
        $validator = \Validator::make(['clave_acceso' => $claveAcceso], [
            'clave_acceso' => ['required', 'string', 'size:49', 'regex:/^[0-9]+$/']
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function index(Request $request)
    {
        // Validar los filtros
        $validator = \Validator::make($request->all(), [
            'tipo' => ['nullable', 'string', 'in:' . implode(',', TipoComprobanteEnum::values())],
            'estado' => ['nullable', 'string', 'in:' . implode(',', EstadosComprobanteEnum::values())],
            'ambiente' => ['nullable', 'string', 'in:' . implode(',', AmbientesEnum::values())],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                'Parámetros no válidos',
                $validator->errors(),
                422
            );
        }

        try {
            $user = auth()->user();

            $query = Comprobante::where('user_id', $user->id);

            if ($request->filled('tipo')) {
                $query->where('tipo_comprobante', $request->tipo);
            }

            if ($request->filled('ambiente')) {
                $query->where('ambiente', $request->ambiente);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
            }

            $perPage = $request->input('per_page', 10);

            $comprobantes = $query->orderByDesc('fecha_emision')->paginate($perPage);

            return $this->sendResponse(
                'Comprobantes recuperados exitosamente.',
                $comprobantes,
                200
            );
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al obtener los comprobantes', $e->getMessage(), 500);
        }
    }


    public function show(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);

            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);

            Gate::authorize('view', $comprobante);

            return $this->sendResponse(
                'Comprobante recuperado correctamente.',
                $comprobante,
                200
            );
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar el comprobante', $e->getMessage(), 404);
        }
    }


    public function getEstado(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);

            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);

            Gate::authorize('view', $comprobante);

            $estado = $comprobante->estado;

            return $this->sendResponse(
                'Estado del comprobante obtenido correctamente.',
                ['estado' => $estado],
                200
            );
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (\Exception $e) {
            return $this->sendError('Error al obtener el estado del comprobante', [], 500);
        }
    }


    private function generateXmlContent(Comprobante $comprobante)
    {
        // Validar que el comprobante haya sido autorizado
        if (trim(strtolower($comprobante->estado)) !== EstadosComprobanteEnum::AUTORIZADO->value) {
            throw new SriException('No es posible obtener el XML porque el comprobante no ha sido autorizado por el SRI');
        }

        // Autorizar la acción
        Gate::authorize('viewXml', $comprobante);

        // Obtener el ambiente del comprobante
        $ambiente = strval($comprobante->ambiente);

        // Consultar el XML desde el SRI
        return $this->sriService->consultarXmlAutorizado($comprobante->clave_acceso, $ambiente);
    }

    public function getXml(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);
            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);
            $xml = $this->generateXmlContent($comprobante);

            return $this->sendResponse(
                'XML obtenido exitosamente',
                ['xml' => $xml]
            );
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage() . $e->getTrace(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (SriException $e) {
            return $this->sendError('Error de consulta en el SRI', $e->getMessage(), 409);
        } catch (\Exception $e) {
            return $this->sendError('Error inesperado al consultar el XML', $e->getMessage(), 500);
        }
    }


    private function generatePdfContent(Comprobante $comprobante, &$fileName)
    {
        // Validar que el comprobante haya sido autorizado
        if (trim(strtolower($comprobante->estado)) !== EstadosComprobanteEnum::AUTORIZADO->value) {
            throw new SriException('No es posible generar el PDF porque el comprobante no ha sido autorizado por el SRI');
        }

        // Autorizar la acción
        Gate::authorize('view', $comprobante);

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
            'barcode_path' => storage_path('app/public/' . $barcodePath),
        ];

        // Generar y descargar el PDF
        $pdf = PDF::loadView('pdf.invoice', $data);
        $facturaNumero = $xmlObject->infoTributaria->estab . '-' . $xmlObject->infoTributaria->ptoEmi . '-' . $xmlObject->infoTributaria->secuencial;
        $fileName = 'FAC-' . $facturaNumero . '.pdf';

        return $pdf->output();
    }


    public function getPdf(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);
            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);
            $fileName = '';
            $pdfContent = $this->generatePdfContent($comprobante, $fileName);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (SriException $e) {
            return $this->sendError('Error de consulta en el SRI', $e->getMessage(), 409);
        } catch (\Exception $e) {
            return $this->sendError('Error inesperado al generar el PDF', $e->getMessage(), 500);
        }
    }

    public function descargarMasivo(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'claves_acceso' => ['required', 'array'],
            'claves_acceso.*' => ['string', 'size:49', 'regex:/^[0-9]+$/'],
            'format' => ['required', 'string', 'in:pdf,xml'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Parámetros no válidos', $validator->errors(), 422);
        }

        $clavesAcceso = $request->input('claves_acceso');
        $format = $request->input('format');
        $zipFileName = 'comprobantes.' . date('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return $this->sendError('Error al crear el archivo ZIP', 'No se pudo crear el archivo ZIP.', 500);
        }

        foreach ($clavesAcceso as $claveAcceso) {
            try {
                $comprobante = Comprobante::findByClaveAcceso($claveAcceso);
                Gate::authorize('view', $comprobante);

                if ($format === 'pdf') {
                    $fileName = '';
                    $content = $this->generatePdfContent($comprobante, $fileName);
                    $zip->addFromString($fileName, $content);
                } else {
                    $content = $this->generateXmlContent($comprobante);
                    $zip->addFromString($claveAcceso . '.xml', $content);
                }
            } catch (\Exception $e) {
                \Log::error("Error al procesar el comprobante {$claveAcceso} para el ZIP: " . $e->getMessage());
                // Continue to the next file
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }


    public function generateFactura(FacturaRequest $request, PuntoEmision $puntoEmision)
    {
        $validated_data = null;
        try {
            // 1. Autorizar acceso a punto de emision de usuario
            Gate::authorize('view', $puntoEmision);

            // 2. Validar firma del usuario
            $user = \Auth::user();
            Gate::authorize('firma', $user);

            // 3. Validar datos del comprobante
            $validated_data = $request->validated();

            // Si no se proporciona fecha de emisión, usar la fecha actual del servidor
            if (!isset($validated_data['fechaEmision'])) {
                $validated_data['fechaEmision'] = now()->format('Y-m-d H:i:s');
            }

            // 4. Generar comprobante
            try {
                $comprobante = Comprobante::create([
                    'user_id' => $user->id,
                    'tipo_comprobante' => TipoComprobanteEnum::FACTURA->value,
                    'ambiente' => $user->ambiente,
                    'cliente_email' => $validated_data['infoAdicional']['email'] ?? null,
                    'cliente_ruc' => $validated_data['identificacionComprador'],
                    'fecha_emision' => $validated_data['fechaEmision'],
                    'payload' => json_encode($validated_data),
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Error al registrar el comprobante: ' . $e->getMessage());
            }

            // 5. Lanzar job de generación de comprobante
            \Log::info("Llamando a job...");
            $job = GenerarComprobanteJob::dispatch($comprobante, $user, $puntoEmision, TipoComprobanteEnum::FACTURA);

            return $this->sendResponse('Tu comprobante se está procesando. Recibirás una notificación cuando esté listo');
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), $e->status());
        } catch (\Exception $e) {
            return $this->sendError('Error al generar la factura', $e->getMessage(), 500, $validated_data);
        }
    }
}