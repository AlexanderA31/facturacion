<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\WithholdingVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithholdingVouchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_withholding_vouchers()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();
        WithholdingVoucher::factory()->count(3)->for($user)->for($supplier)->create();

        $response = $this->actingAs($user)->getJson('/api/withholding-vouchers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_can_create_a_withholding_voucher()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();
        $purchase = PurchaseInvoice::factory()->for($user)->for($supplier)->create();
        $withholdingData = [
            'proveedor_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'establecimiento' => '001',
            'punto_emision' => '001',
            'secuencial' => '000000001',
            'autorizacion' => '1234567890',
            'fecha_emision' => '2025-08-23',
            'incomeLines' => [
                ['cod_ret_air' => '3440', 'base_imponible' => 100, 'porcentaje' => 2.75, 'valor' => 2.75]
            ],
            'vatLines' => [
                ['cod_ret_iva' => '1', 'base_imponible' => 100, 'porcentaje' => 30, 'valor' => 30]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/withholding-vouchers', $withholdingData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('withholding_vouchers', ['secuencial' => '000000001']);
    }
}
