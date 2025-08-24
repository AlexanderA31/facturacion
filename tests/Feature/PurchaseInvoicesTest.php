<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_purchase_invoices()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();
        PurchaseInvoice::factory()->count(3)->for($user)->for($supplier)->create();

        $response = $this->actingAs($user)->getJson('/api/purchase-invoices');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_can_create_a_purchase_invoice()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();
        $invoiceData = [
            'proveedor_id' => $supplier->id,
            'tipo_comprobante' => '01',
            'establecimiento' => '001',
            'punto_emision' => '001',
            'secuencial' => '000000001',
            'fecha_emision' => '2025-08-23',
            'fecha_registro' => '2025-08-23',
            'autorizacion' => '1234567890',
            'parte_relacionada' => 'NO',
            'cod_sustento' => '01',
            'items' => [
                ['descripcion' => 'Test Item', 'cantidad' => 1, 'precio_unitario' => 100, 'descuento' => 0]
            ],
            'taxes' => [
                ['codigo_impuesto' => '2', 'codigo_porcentaje' => '2', 'base_imponible' => 100, 'valor' => 12]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/purchase-invoices', $invoiceData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchase_invoices', ['secuencial' => '000000001']);
    }
}
