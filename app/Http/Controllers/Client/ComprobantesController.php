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
use App\Jobs\CreateBulkDownloadZipJob;
use App\Jobs\GenerarComprobanteJob;
use App\Models\BulkDownloadJob;
use App\Services\ClaveAccesoBarcode;
use App\Services\FileGenerationService;
use App\Services\SriComprobanteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;
use ZipArchive;
use Carbon\Carbon;


class ComprobantesController extends Controller
{
    protected $sriService;
    protected $fileGenerationService;

    public function __construct(SriComprobanteService $sriService, FileGenerationService $fileGenerationService)
    {
        $this->sriService = $sriService;
        $this->fileGenerationService = $fileGenerationService;
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

    public function exportAuthorized(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'fecha_desde' => ['nullable', 'date'],
                'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Parámetros no válidos',
                    $validator->errors(),
                    422
                );
            }

            $user = auth()->user();
            $query = Comprobante::where('user_id', $user->id)->where('estado', 'autorizado');

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $fecha_desde = Carbon::parse($request->fecha_desde)->startOfDay();
                $fecha_hasta = Carbon::parse($request->fecha_hasta)->endOfDay();
                $query->whereBetween('fecha_autorizacion', [$fecha_desde, $fecha_hasta]);
            }

            $comprobantes = $query->orderByDesc('fecha_emision')->get();

            return $this->sendResponse(
                'Comprobantes autorizados recuperados exitosamente para exportación.',
                $comprobantes,
                200
            );
        } catch (\Exception $e) {
            return $this->sendError('Error al obtener los comprobantes para exportación', $e->getMessage(), 500);
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


    public function getXml(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);
            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);
            $xml = $this->fileGenerationService->generateXmlContent($comprobante);

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

    public function getPdf(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);
            $comprobante = Comprobante::findByClaveAcceso($clave_acceso);
            $fileName = '';
            $pdfContent = $this->fileGenerationService->generatePdfContent($comprobante, $fileName);

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

        $user = Auth::user();
        $clavesAcceso = $request->input('claves_acceso');
        $format = $request->input('format');

        $job = BulkDownloadJob::create([
            'user_id' => $user->id,
            'format' => $format,
            'total_files' => count($clavesAcceso),
        ]);

        CreateBulkDownloadZipJob::dispatch($job, $clavesAcceso);

        return $this->sendResponse('La solicitud de descarga ha sido aceptada y se está procesando en segundo plano.', ['job_id' => $job->id], 202);
    }

    public function getBulkDownloadStatus(string $jobId)
    {
        try {
            $job = BulkDownloadJob::findOrFail($jobId);
            Gate::authorize('view', $job);

            return $this->sendResponse('Estado del trabajo de descarga masiva.', $job);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Trabajo no encontrado', 'No se encontró el trabajo de descarga masiva.', 404);
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', 'No tienes permiso para ver este trabajo.', 403);
        }
    }

    public function downloadBulkZip(string $jobId)
    {
        try {
            $job = BulkDownloadJob::findOrFail($jobId);
            Gate::authorize('view', $job);

            if ($job->status !== \App\Enums\BulkDownloadStatusEnum::COMPLETED) {
                return $this->sendError('El archivo no está listo', 'El archivo ZIP aún no está listo para descargar.', 409);
            }

            if (!$job->file_path || !Storage::disk('public')->exists($job->file_path)) {
                $expectedPath = $job->file_path ? Storage::disk('public')->path($job->file_path) : 'null';
                return $this->sendError(
                    'Archivo no encontrado',
                    'El archivo ZIP no se encontró en el servidor. Path: ' . $expectedPath,
                    404
                );
            }

            return Storage::disk('public')->download($job->file_path);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Trabajo no encontrado', 'No se encontró el trabajo de descarga masiva.', 404);
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', 'No tienes permiso para descargar este archivo.', 403);
        }
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