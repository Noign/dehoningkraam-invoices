<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'vat_percentage' => ['required', Rule::in(Product::VAT_PERCENTAGES)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
            'name.required' => 'Vul een naam in.',
            'name.max' => 'De naam mag maximaal 255 tekens lang zijn.',
            'description.max' => 'De omschrijving mag maximaal 5000 tekens lang zijn.',
            'unit.required' => 'Vul een eenheid in.',
            'unit.max' => 'De eenheid mag maximaal 20 tekens lang zijn.',
            'price.required' => 'Vul een prijs in.',
            'price.numeric' => 'Vul een geldige prijs in.',
            'price.decimal' => 'De prijs mag maximaal twee decimalen hebben.',
            'price.min' => 'De prijs mag niet negatief zijn.',
            'price.max' => 'De prijs is te hoog.',
            'vat_percentage.required' => 'Kies een btw-percentage.',
            'vat_percentage.in' => 'Kies een geldig btw-percentage.',
            'image.uploaded' => 'De afbeelding kon niet worden geüpload.',
            'image.image' => 'Het bestand moet een afbeelding zijn.',
            'image.mimes' => 'De afbeelding moet een JPG-, PNG- of WebP-bestand zijn.',
            'image.max' => 'De afbeelding mag maximaal 5 MB groot zijn.',
        ];
    }

    /**
     * Get the validated product attributes, with the price converted to cents.
     *
     * @return array{name: string, description: string|null, unit: string, price_including_vat_in_cents: int, vat_percentage: int}
     */
    public function productAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->validated('description'),
            'unit' => $this->string('unit')->toString(),
            'price_including_vat_in_cents' => (int) round($this->float('price') * 100),
            'vat_percentage' => $this->integer('vat_percentage'),
        ];
    }
}
