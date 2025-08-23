<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WithholdingVatLine>
 */
class WithholdingVatLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cod_ret_iva' => '1',
            'base_imponible' => $this->faker->randomFloat(2, 100, 1000),
            'porcentaje' => 30,
            'valor' => function (array $attributes) {
                return $attributes['base_imponible'] * 0.30;
            },
        ];
    }
}
