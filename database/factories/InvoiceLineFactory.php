<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => null,
            'position' => 1,
            'description' => fake()->words(2, true),
            'quantity' => '40.00',
            'unit' => 'st',
            'vat_percentage' => 9,
            'unit_price_including_vat_in_cents' => 850,
            'unit_price_excluding_vat_in_cents' => 780,
            'total_including_vat_in_cents' => 34000,
            'total_excluding_vat_in_cents' => 31193,
        ];
    }
}
