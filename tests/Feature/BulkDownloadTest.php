<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Comprobante;
use App\Services\SriComprobanteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use App\Enums\EstadosComprobanteEnum;
use ZipArchive;

class BulkDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the SriComprobanteService for all tests in this class
        $xmlString = file_get_contents(base_path('tests/fixtures/sample_invoice.xml'));
        $this->mock(SriComprobanteService::class, function (MockInterface $mock) use ($xmlString) {
            $mock->shouldReceive('consultarXmlAutorizado')->andReturn($xmlString);
        });
    }

    public function test_can_download_multiple_pdfs_as_zip()
    {
        // 1. Setup
        $user = User::factory()->create();
        $comprobantes = Comprobante::factory()->count(3)->for($user)->create([
            'estado' => EstadosComprobanteEnum::AUTORIZADO->value,
        ]);
        $clavesAcceso = $comprobantes->pluck('clave_acceso')->toArray();

        // 2. Action
        $response = $this->actingAs($user)->postJson('/api/comprobantes/descargar-masivo', [
            'claves_acceso' => $clavesAcceso,
            'format' => 'pdf',
        ]);

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');

        // Save the response content to a temporary file to inspect it
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip');
        file_put_contents($tempZipPath, $response->getContent());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempZipPath) === TRUE);
        $this->assertEquals(3, $zip->numFiles);

        // Clean up the temporary file
        unlink($tempZipPath);
    }

    public function test_can_download_multiple_xmls_as_zip()
    {
        // 1. Setup
        $user = User::factory()->create();
        $comprobantes = Comprobante::factory()->count(3)->for($user)->create([
            'estado' => EstadosComprobanteEnum::AUTORIZADO->value,
        ]);
        $clavesAcceso = $comprobantes->pluck('clave_acceso')->toArray();

        // 2. Action
        $response = $this->actingAs($user)->postJson('/api/comprobantes/descargar-masivo', [
            'claves_acceso' => $clavesAcceso,
            'format' => 'xml',
        ]);

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');

        // Save the response content to a temporary file to inspect it
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip');
        file_put_contents($tempZipPath, $response->getContent());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempZipPath) === TRUE);
        $this->assertEquals(3, $zip->numFiles);
        $this->assertEquals($clavesAcceso[0] . '.xml', $zip->getNameIndex(0));

        // Clean up the temporary file
        unlink($tempZipPath);
    }
}
