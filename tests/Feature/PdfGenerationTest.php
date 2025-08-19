<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Comprobante;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Services\SriComprobanteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use App\Enums\EstadosComprobanteEnum;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_pdf_for_invoice()
    {
        // 1. Setup
        $user = User::factory()->create();
        $establecimiento = Establecimiento::factory()->for($user)->create();
        $puntoEmision = PuntoEmision::factory()->for($user)->for($establecimiento)->create();

        $claveAcceso = Str::random(49);

        $comprobante = Comprobante::factory()->for($user)->create([
            'clave_acceso' => $claveAcceso,
            'estado' => EstadosComprobanteEnum::AUTORIZADO->value,
        ]);

        // 2. Mock the SriComprobanteService
        $xmlString = file_get_contents(base_path('tests/fixtures/sample_invoice.xml'));
        $this->mock(SriComprobanteService::class, function (MockInterface $mock) use ($xmlString) {
            $mock->shouldReceive('consultarXmlAutorizado')->andReturn($xmlString);
        });

        // 3. Action
        $response = $this->actingAs($user)->getJson(route('comprobantes.pdf', ['clave_acceso' => $claveAcceso]));

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }
}
