<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\TipoComprobanteEnum;
use App\Enums\EstadosComprobanteEnum;
use App\Enums\AmbientesEnum;
use App\Models\User;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comprobante>
 */
class ComprobanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tipo_comprobante' => TipoComprobanteEnum::FACTURA->value,
            'ambiente' => AmbientesEnum::PRUEBAS->value,
            'estado' => EstadosComprobanteEnum::PENDIENTE->value,
            'fecha_emision' => now(),
            'payload' => file_get_contents(base_path('tests/fixtures/sample_invoice.json')),
            'clave_acceso' => $this->faker->unique()->numerify('#################################################'),
        ];
    }
}
