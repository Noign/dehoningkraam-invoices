<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OrganisationRequest extends FormRequest
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
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
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
            'address.required' => 'Vul een adres in.',
            'address.max' => 'Het adres mag maximaal 255 tekens lang zijn.',
            'postal_code.required' => 'Vul een postcode in.',
            'postal_code.max' => 'De postcode mag maximaal 20 tekens lang zijn.',
            'city.required' => 'Vul een plaats in.',
            'city.max' => 'De plaats mag maximaal 255 tekens lang zijn.',
            'email.required' => 'Vul een e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Het e-mailadres mag maximaal 255 tekens lang zijn.',
        ];
    }
}
