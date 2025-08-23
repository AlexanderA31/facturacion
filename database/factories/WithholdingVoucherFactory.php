<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WithholdingVoucher>
 */
class WithholdingVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento' => '001',
            'punto_emision' => '001',
            'secuencial' => $this->faker->unique()->numerify('#########'),
            'autorizacion' => $this->faker->unique()->numerify('#################################################'),
            'fecha_emision' => $this->faker->date(),
        ];
    }
}
