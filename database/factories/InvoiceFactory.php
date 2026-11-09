<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => Invoice::formatNumber(2026, fake()->unique()->numberBetween(1, 99999)),
            'organisation_id' => null,
            'invoice_date' => '2026-10-29',
            'due_date' => '2026-11-29',
            'seller' => [
                'company_name' => fake()->company(),
                'address' => fake()->streetAddress(),
                'postal_code' => fake()->postcode(),
                'city' => fake()->city(),
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'kvk_number' => fake()->numerify('########'),
                'vat_number' => fake()->numerify('NL#########B##'),
                'iban' => fake()->numerify('NL##TEST##########'),
                'iban_account_name' => fake()->company(),
                'payment_term_months' => 1,
            ],
            'customer' => [
                'name' => fake()->company(),
                'address' => fake()->streetAddress(),
                'postal_code' => fake()->postcode(),
                'city' => fake()->city(),
                'email' => fake()->safeEmail(),
            ],
            'vat_breakdown' => [[
                'percentage' => 9,
                'excluding_vat_in_cents' => 31193,
                'vat_in_cents' => 2807,
                'including_vat_in_cents' => 34000,
            ]],
            'subtotal_in_cents' => 31193,
            'vat_in_cents' => 2807,
            'total_in_cents' => 34000,
            'payment_note' => null,
            'note' => null,
        ];
    }
}
