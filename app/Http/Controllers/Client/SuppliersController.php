<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;

class SuppliersController extends Controller
{
    public function index()
    {
        try {
            $perPage = request()->query('per_page', 10);
            $userId = auth()->id();
            // This needs to be adjusted based on how suppliers are linked to users.
            // For now, I'll assume a direct user_id on suppliers table is needed.
            // I will add it in the migration.
            $suppliers = Supplier::where('user_id', $userId)->paginate($perPage);

            return $this->sendResponse('Proveedores recuperados con éxito', $suppliers);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar los proveedores', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'tipo_id' => 'required|string|in:01,02,03',
                'identificacion' => 'required|string|max:20|unique:suppliers,identificacion',
                'razon_social' => 'required|string|max:300',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Datos no válidos', $validator->errors(), 422);
            }

            $supplier = new Supplier($validator->validated());
            $supplier->user_id = auth()->id();
            $supplier->save();

            return $this->sendResponse('Proveedor creado exitosamente', $supplier, 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear el proveedor', $e->getMessage(), 500);
        }
    }

    public function show(Supplier $supplier)
    {
        try {
            // Assuming a policy exists for suppliers
            Gate::authorize('view', $supplier);
            return $this->sendResponse('Proveedor recuperado con éxito', $supplier);
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar el proveedor', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Supplier $supplier)
    {
        try {
            Gate::authorize('update', $supplier);

            $validator = \Validator::make($request->all(), [
                'tipo_id' => 'sometimes|required|string|in:01,02,03',
                'identificacion' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('suppliers')->ignore($supplier->id)],
                'razon_social' => 'sometimes|required|string|max:300',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Datos no válidos', $validator->errors(), 422);
            }

            $supplier->update($validator->validated());

            return $this->sendResponse('Proveedor actualizado exitosamente', $supplier);
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar el proveedor', $e->getMessage(), 500);
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            Gate::authorize('delete', $supplier);
            $supplier->delete();
            return $this->sendResponse('Proveedor eliminado exitosamente');
        } catch (AuthorizationException $e) {
            return $this->sendError('Acceso denegado', $e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar el proveedor', $e->getMessage(), 500);
        }
    }
}
