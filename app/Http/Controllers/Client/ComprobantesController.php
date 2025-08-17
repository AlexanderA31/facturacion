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
use App\Services\SriComprobanteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use dealfonso\sapp\PDF\Signature;


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


    private function getComprobanteForDownload(string $clave_acceso): Comprobante
    {
        $comprobante = Comprobante::findByClaveAcceso($clave_acceso);

        // First, authorize that the user can perform a download action on this record.
        Gate::authorize('viewXml', $comprobante);

        // If it's already authorized, we are good to go.
        if ($comprobante->estado === EstadosComprobanteEnum::AUTORIZADO->value) {
            return $comprobante;
        }

        // If it's a rejected sequential error, find the original authorized one.
        if (
            $comprobante->estado === EstadosComprobanteEnum::RECHAZADO->value &&
            $comprobante->error_message === 'ERROR SECUENCIAL REGISTRADO'
        ) {
            $original = Comprobante::where('user_id', $comprobante->user_id)
                ->where('establecimiento', $comprobante->establecimiento)
                ->where('punto_emision', $comprobante->punto_emision)
                ->where('secuencial', $comprobante->secuencial)
                ->where('estado', EstadosComprobanteEnum::AUTORIZADO->value)
                ->first();

            if ($original) {
                // The user is authorized to see the duplicate, so they are authorized to see the original.
                return $original;
            }
        }

        // For all other cases, throw an exception.
        throw new AuthorizationException('Este comprobante no está autorizado para descarga.');
    }


    public function getXml(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);

            $comprobante = $this->getComprobanteForDownload($clave_acceso);

            // The method returns a guaranteed authorized comprobante, so we use its clave_acceso
            $authorized_clave_acceso = $comprobante->clave_acceso;

            $ambiente = strval($comprobante->ambiente);
            $xml = $this->sriService->consultarXmlAutorizado($authorized_clave_acceso, $ambiente);

            return $this->sendResponse(
                'XML obtenido exitosamente',
                ['xml' => $xml]
            );
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (SriException $e) {
            return $this->sendError('Error de consulta en el SRI', $e->getMessage(), 502);
        } catch (\Exception $e) {
            return $this->sendError('Error inesperado al consultar el XML', $e->getMessage(), 500);
        }
    }


    public function getPdf(string $clave_acceso)
    {
        try {
            $this->validarClaveAcceso($clave_acceso);

            $comprobante = $this->getComprobanteForDownload($clave_acceso);

            // The method returns a guaranteed authorized comprobante, so we use its clave_acceso
            $authorized_clave_acceso = $comprobante->clave_acceso;

            $xmlString = $this->sriService->consultarXmlAutorizado($authorized_clave_acceso, $comprobante->ambiente);
            $xml = simplexml_load_string($xmlString);

            if ($xml === false) {
                throw new \Exception('No se pudo parsear el XML del comprobante.');
            }

            $data = [
                'infoTributaria' => $xml->infoTributaria,
                'infoFactura' => $xml->infoFactura,
                'detalles' => $xml->detalles->detalle,
            ];

            $pdf = Pdf::loadView('pdf.invoice', $data);
            $unsignedPdfContent = $pdf->output();

            $user = auth()->user();
            if (!$user->signature_path || !$user->signature_key) {
                throw new \Exception('El usuario no tiene una firma electrónica configurada.');
            }

            $p12Content = Storage::disk('private')->get($user->signature_path);
            $password = decrypt($user->signature_key);

            $signedPdfContent = Signature::sign($unsignedPdfContent, $p12Content, $password);

            return response($signedPdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $clave_acceso . '.pdf"',
            ]);

        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Comprobante no encontrado', 'No se encontró el comprobante con la clave de acceso proporcionada.', 404);
        } catch (SriException $e) {
            return $this->sendError('Error de consulta en el SRI', $e->getMessage(), 502);
        } catch (\Exception $e) {
            return $this->sendError('No se pudo generar el PDF: ' . $e->getMessage(), 500);
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
                $validated_data['fechaEmision'] = now()->format('Y-m-d');
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
