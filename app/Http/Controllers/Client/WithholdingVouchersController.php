<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\WithholdingVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class WithholdingVouchersController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->query('per_page', 10);
            $userId = auth()->id();
            $withholdingVouchers = WithholdingVoucher::with('supplier', 'purchaseInvoice', 'incomeLines', 'vatLines')
                ->where('user_id', $userId)
                ->paginate($perPage);

            return $this->sendResponse('Retenciones recuperadas con éxito', $withholdingVouchers);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar las retenciones', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'proveedor_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchase_invoices,id',
            'establecimiento' => 'required|string|max:3',
            'punto_emision' => 'required|string|max:3',
            'secuencial' => 'required|string|max:9',
            'autorizacion' => 'required|string|max:49',
            'fecha_emision' => 'required|date',
            'incomeLines' => 'sometimes|array',
            'incomeLines.*.cod_ret_air' => 'required|string|max:5',
            'incomeLines.*.base_imponible' => 'required|numeric',
            'incomeLines.*.porcentaje' => 'required|numeric',
            'incomeLines.*.valor' => 'required|numeric',
            'vatLines' => 'sometimes|array',
            'vatLines.*.cod_ret_iva' => 'required|string|max:5',
            'vatLines.*.base_imponible' => 'required|numeric',
            'vatLines.*.porcentaje' => 'required|numeric',
            'vatLines.*.valor' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Datos no válidos', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            $voucherData = collect($validatedData)->except(['incomeLines', 'vatLines'])->all();
            $voucherData['user_id'] = auth()->id();

            $withholdingVoucher = WithholdingVoucher::create($voucherData);

            if (isset($validatedData['incomeLines'])) {
                $withholdingVoucher->incomeLines()->createMany($validatedData['incomeLines']);
            }
            if (isset($validatedData['vatLines'])) {
                $withholdingVoucher->vatLines()->createMany($validatedData['vatLines']);
            }

            DB::commit();

            return $this->sendResponse('Retención creada exitosamente', $withholdingVoucher->load('incomeLines', 'vatLines'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error al crear la retención', $e->getMessage(), 500);
        }
    }

    public function show(WithholdingVoucher $withholdingVoucher)
    {
        try {
            Gate::authorize('view', $withholdingVoucher);
            return $this->sendResponse('Retención recuperada con éxito', $withholdingVoucher->load('supplier', 'purchaseInvoice', 'incomeLines', 'vatLines'));
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar la retención', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, WithholdingVoucher $withholdingVoucher)
    {
        Gate::authorize('update', $withholdingVoucher);
        return $this->sendError('Funcionalidad no implementada', 'La actualización de retenciones aún no está implementada.', 501);
    }

    public function destroy(WithholdingVoucher $withholdingVoucher)
    {
        try {
            Gate::authorize('delete', $withholdingVoucher);
            $withholdingVoucher->delete();
            return $this->sendResponse('Retención eliminada exitosamente');
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la retención', $e->getMessage(), 500);
        }
    }
}
