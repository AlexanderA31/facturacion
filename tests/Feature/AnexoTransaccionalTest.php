<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Comprobante;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseTax;
use App\Models\WithholdingVoucher;
use App\Models\WithholdingIncomeLine;
use App\Models\WithholdingVatLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\TipoComprobanteEnum;

class AnexoTransaccionalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_anexo_transaccional()
    {
        // 1. Setup
        $user = User::factory()->create([
            'ruc' => '1790012345001',
            'razonSocial' => 'MI EMPRESA S.A.',
        ]);
        $establecimiento = Establecimiento::factory()->for($user)->create();
        $puntoEmision = PuntoEmision::factory()->for($establecimiento)->create();

        // Create sales data
        Comprobante::factory()->for($user)->create([
            'fecha_emision' => '2025-08-15',
            'establecimiento' => $establecimiento->numero,
            'punto_emision' => $puntoEmision->numero,
        ]);

        // Create purchase data
        $supplier = Supplier::factory()->create();
        $purchase = PurchaseInvoice::factory()->for($user)->for($supplier)->create([
            'fecha_emision' => '2025-08-20',
        ]);
        PurchaseItem::factory()->for($purchase, 'purchaseInvoice')->create();
        PurchaseTax::factory()->for($purchase, 'purchaseInvoice')->create();

        // Create retention data
        $withholding = WithholdingVoucher::factory()->for($user)->for($supplier)->create([
            'fecha_emision' => '2025-08-22',
            'purchase_id' => $purchase->id,
        ]);
        WithholdingIncomeLine::factory()->for($withholding, 'withholdingVoucher')->create();
        WithholdingVatLine::factory()->for($withholding, 'withholdingVoucher')->create();

        // 2. Action
        $response = $this->actingAs($user)->getJson('/api/anexo-transaccional/2025/08');

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<razonSocial>MI EMPRESA S.A.</razonSocial>', $xml);
        $this->assertStringContainsString('<compra>', $xml);
        $this->assertStringContainsString('<venta>', $xml);
        $this->assertStringContainsString('<retencion>', $xml);
    }
}
