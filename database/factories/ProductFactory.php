<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'unit' => 'st',
            'price_including_vat_in_cents' => fake()->numberBetween(100, 5000),
            'vat_percentage' => fake()->randomElement(Product::VAT_PERCENTAGES),
            'image_path' => null,
        ];
    }
}
