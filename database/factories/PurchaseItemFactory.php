<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descripcion' => $this->faker->sentence,
            'cantidad' => $this->faker->randomFloat(2, 1, 100),
            'precio_unitario' => $this->faker->randomFloat(2, 1, 1000),
            'descuento' => 0,
        ];
    }
}
