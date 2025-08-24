<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicesController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->query('per_page', 10);
            $userId = auth()->id();
            $purchaseInvoices = PurchaseInvoice::with('supplier', 'items', 'taxes')
                ->where('user_id', $userId)
                ->paginate($perPage);

            return $this->sendResponse('Facturas de compra recuperadas con éxito', $purchaseInvoices);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar las facturas de compra', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'proveedor_id' => 'required|exists:suppliers,id',
            'tipo_comprobante' => 'required|string|max:2',
            'establecimiento' => 'required|string|max:3',
            'punto_emision' => 'required|string|max:3',
            'secuencial' => 'required|string|max:9',
            'fecha_emision' => 'required|date',
            'fecha_registro' => 'required|date',
            'autorizacion' => 'required|string|max:49',
            'parte_relacionada' => 'required|in:SI,NO',
            'cod_sustento' => 'required|string|max:2',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string|max:300',
            'items.*.cantidad' => 'required|numeric',
            'items.*.precio_unitario' => 'required|numeric',
            'items.*.descuento' => 'required|numeric',
            'taxes' => 'required|array',
            'taxes.*.codigo_impuesto' => 'required|string|max:1',
            'taxes.*.codigo_porcentaje' => 'required|string|max:4',
            'taxes.*.base_imponible' => 'required|numeric',
            'taxes.*.valor' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Datos no válidos', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            $invoiceData = collect($validatedData)->except(['items', 'taxes'])->all();
            $invoiceData['user_id'] = auth()->id();

            $purchaseInvoice = PurchaseInvoice::create($invoiceData);

            $purchaseInvoice->items()->createMany($validatedData['items']);
            $purchaseInvoice->taxes()->createMany($validatedData['taxes']);

            DB::commit();

            return $this->sendResponse('Factura de compra creada exitosamente', $purchaseInvoice->load('items', 'taxes'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error al crear la factura de compra', $e->getMessage(), 500);
        }
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        try {
            Gate::authorize('view', $purchaseInvoice);
            return $this->sendResponse('Factura de compra recuperada con éxito', $purchaseInvoice->load('supplier', 'items', 'taxes'));
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar la factura de compra', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        Gate::authorize('update', $purchaseInvoice);
        // Update logic is complex due to relations, will implement if requested by user.
        // For now, returning a not implemented error.
        return $this->sendError('Funcionalidad no implementada', 'La actualización de facturas de compra aún no está implementada.', 501);
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        try {
            Gate::authorize('delete', $purchaseInvoice);
            $purchaseInvoice->delete();
            return $this->sendResponse('Factura de compra eliminada exitosamente');
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la factura de compra', $e->getMessage(), 500);
        }
    }
}
