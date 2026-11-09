<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvoiceDraftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.type' => ['required', 'in:product,discount,info'],
            'lines.*.product_id' => ['exclude_if:lines.*.type,info', 'required', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required_if:lines.*.type,info', 'nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required_unless:lines.*.type,info', 'nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999'],
            'lines.*.unit_price' => ['exclude_if:lines.*.type,info', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
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
            'organisation_id.required' => 'Kies een organisatie.',
            'organisation_id.exists' => 'Deze organisatie bestaat niet meer.',
            'invoice_date.required' => 'Vul een factuurdatum in.',
            'invoice_date.date' => 'Vul een geldige factuurdatum in.',
            'due_date.date' => 'Vul een geldige vervaldatum in.',
            'due_date.after_or_equal' => 'De vervaldatum mag niet voor de factuurdatum liggen.',
            'payment_note.max' => 'De betaalnotitie mag maximaal 255 tekens lang zijn.',
            'note.max' => 'De opmerking mag maximaal 255 tekens lang zijn.',
            'lines.required' => 'Voeg minimaal één product toe.',
            'lines.min' => 'Voeg minimaal één product toe.',
            'lines.*.product_id.required' => 'Kies een product.',
            'lines.*.product_id.exists' => 'Dit product bestaat niet meer.',
            'lines.*.description.required_if' => 'Vul een omschrijving in.',
            'lines.*.description.max' => 'De omschrijving mag maximaal 255 tekens lang zijn.',
            'lines.*.quantity.required_unless' => 'Vul een aantal in.',
            'lines.*.quantity.numeric' => 'Vul een geldig aantal in.',
            'lines.*.quantity.decimal' => 'Het aantal mag maximaal twee decimalen hebben.',
            'lines.*.quantity.gt' => 'Het aantal moet groter zijn dan 0.',
            'lines.*.quantity.max' => 'Het aantal is te hoog.',
            'lines.*.unit_price.numeric' => 'Vul een geldige prijs in.',
            'lines.*.unit_price.decimal' => 'De prijs mag maximaal twee decimalen hebben.',
            'lines.*.unit_price.min' => 'De prijs mag niet negatief zijn.',
            'lines.*.unit_price.max' => 'De prijs is te hoog.',
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
                $lines = collect($this->array('lines'));

                if ($lines->isNotEmpty() && $lines->where('type', 'product')->isEmpty()) {
                    $validator->errors()->add('lines', 'Voeg minimaal één product toe.');
                }
            },
        ];
    }
}
