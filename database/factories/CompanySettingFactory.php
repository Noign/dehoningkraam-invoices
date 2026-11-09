<?php

namespace Database\Factories;

use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
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
            'invoice_note' => fake()->sentence(),
            'next_invoice_number' => 5,
            'next_invoice_number_year' => null,
        ];
    }
}
