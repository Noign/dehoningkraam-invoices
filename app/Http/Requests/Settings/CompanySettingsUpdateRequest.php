<?php

namespace App\Http\Requests\Settings;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CompanySettingsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'kvk_number' => ['required', 'regex:/^\d{8}$/'],
            'vat_number' => ['required', 'regex:/^NL\d{9}B\d{2}$/'],
            'iban' => ['required', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/'],
            'iban_account_name' => ['required', 'string', 'max:255'],
            'payment_term_months' => ['required', 'integer', 'between:1,12'],
            'invoice_note' => ['nullable', 'string', 'max:255'],
            'next_invoice_number' => ['required', 'integer', 'between:1,99999'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Vul een bedrijfsnaam in.',
            'company_name.max' => 'De bedrijfsnaam mag maximaal 255 tekens lang zijn.',
            'address.required' => 'Vul een adres in.',
            'address.max' => 'Het adres mag maximaal 255 tekens lang zijn.',
            'postal_code.required' => 'Vul een postcode in.',
            'postal_code.max' => 'De postcode mag maximaal 20 tekens lang zijn.',
            'city.required' => 'Vul een plaats in.',
            'city.max' => 'De plaats mag maximaal 255 tekens lang zijn.',
            'email.required' => 'Vul een e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Het e-mailadres mag maximaal 255 tekens lang zijn.',
            'phone.max' => 'Het telefoonnummer mag maximaal 30 tekens lang zijn.',
            'kvk_number.required' => 'Vul een KvK-nummer in.',
            'kvk_number.regex' => 'Een KvK-nummer bestaat uit 8 cijfers.',
            'vat_number.required' => 'Vul een btw-id in.',
            'vat_number.regex' => 'Vul een geldig btw-id in, bijvoorbeeld NL123456789B01.',
            'iban.required' => 'Vul een IBAN in.',
            'iban.regex' => 'Vul een geldig IBAN in.',
            'iban_account_name.required' => 'Vul de naam van de rekeninghouder in.',
            'iban_account_name.max' => 'De naam van de rekeninghouder mag maximaal 255 tekens lang zijn.',
            'payment_term_months.required' => 'Vul een betaaltermijn in.',
            'payment_term_months.integer' => 'De betaaltermijn moet een heel aantal maanden zijn.',
            'payment_term_months.between' => 'De betaaltermijn moet tussen 1 en 12 maanden liggen.',
            'invoice_note.max' => 'De opmerking mag maximaal 255 tekens lang zijn.',
            'next_invoice_number.required' => 'Vul het volgende factuurnummer in.',
            'next_invoice_number.integer' => 'Het factuurnummer moet een heel getal zijn.',
            'next_invoice_number.between' => 'Het factuurnummer moet tussen 1 en 99999 liggen.',
        ];
    }

    /**
     * Get the validation callbacks that run after the base rules.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('next_invoice_number')) {
                    return;
                }

                $number = Invoice::formatNumber(
                    CompanySetting::current()->next_invoice_number_year ?? now()->year,
                    $this->integer('next_invoice_number'),
                );

                if (Invoice::query()->where('number', '>=', $number)->exists()) {
                    $validator->errors()->add('next_invoice_number', "Factuurnummer {$number} of een hoger nummer is al gebruikt.");
                }
            },
        ];
    }

    /**
     * Normalise identifiers so they are stored in one consistent notation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'vat_number' => $this->string('vat_number')->remove([' ', '.'])->upper()->toString(),
            'iban' => $this->string('iban')->remove(' ')->upper()->toString(),
        ]);
    }
}
