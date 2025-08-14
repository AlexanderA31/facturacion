<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoadSignatureRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\RolesEnum;
use App\Services\CertificadoFirma;
use Illuminate\Support\Facades\Log;

class PerfilClientController extends Controller implements HasMiddleware
{
    protected $certificadoFirmaService;

    public function __construct(CertificadoFirma $certificadoFirmaService)
    {
        $this->certificadoFirmaService = $certificadoFirmaService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('role:' . RolesEnum::CLIENTE->value, only: ['update']),
        ];
    }

    /* ---------------------------- Obtener perfil --------------------------- */
    public function show()
    {
        try {
            $user = auth()->user();

            return $this->sendResponse('Perfil recuperado exitosamente', new UserResource($user));
        } catch(\Exception $e) {
            return $this->sendError('Error al recuperar el perfil', $e->getMessage(), 500);
        }
    }

    /* ------------------------- Actualizar perfil ------------------------ */
    public function update(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'razonSocial' => 'sometimes|string',
                'nombreComercial' => 'sometimes|string',
                'dirMatriz' => 'sometimes|string',
                'contribuyenteEspecial' => 'sometimes|string|nullable',
                'obligadoContabilidad' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Datos no válidos', $validator->errors(), 422);
            }

            $validated = $validator->validated();
            $user->update($validated);

            return $this->sendResponse('Perfil actualizado exitosamente', new UserResource($user));
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar el perfil', $e->getMessage(), 500);
        }
    }

    /* ------------------------- Actualizar contraseña ------------------------ */
    public function updatePassword(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|current_password',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Datos no válidos', $validator->errors(), 422);
            }

            $user->update([
                'password' => bcrypt($request->password)
            ]);

            return $this->sendResponse('Contraseña actualizada exitosamente', []);
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar la contraseña', $e->getMessage(), 500);
        }
    }

    /* --------------- Actualizar certificado .p12 --------------- */
    public function updateSignature(LoadSignatureRequest $request)
    {
        Log::info("Iniciando el proceso de actualización de firma para el usuario: " . auth()->id());
        try {
            $user = auth()->user();

            $certificateFile = $request->file('signature_file');
            $certificateKey = $request->input('signature_key');
            $existingCertificatePath = $user->signature_path;

            $result = $this->certificadoFirmaService->handleCertificate(
                $certificateFile,
                $certificateKey,
                $user->ruc,
                $existingCertificatePath
            );

            $user->update([
                'signature_path' => $result['signature_path'],
                'signature_key' => $result['signature_key'],
                'signature_expires_at' => $result['expires_at'],
            ]);

            return $this->sendResponse('Certificado y clave de firma actualizados exitosamente', [
                'expires_at' => $result['expires_at'],
            ]);
        } catch(\Exception $e) {
            return $this->sendError($e->getMessage(), null, 500);
        }
    }
}
