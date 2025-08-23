<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseInvoice>
 */
class PurchaseInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_comprobante' => '01',
            'establecimiento' => '001',
            'punto_emision' => '001',
            'secuencial' => $this->faker->unique()->numerify('#########'),
            'fecha_emision' => $this->faker->date(),
            'fecha_registro' => $this->faker->date(),
            'autorizacion' => $this->faker->unique()->numerify('#################################################'),
            'parte_relacionada' => 'NO',
            'cod_sustento' => '01',
        ];
    }
}
