<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompanySettingsUpdateRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    /**
     * Show the company settings page.
     */
    public function edit(): Response
    {
        $settings = CompanySetting::current();

        return Inertia::render('settings/Company', [
            'settings' => $settings,
            'invoiceNumberYear' => $settings->next_invoice_number_year ?? now()->year,
        ]);
    }

    /**
     * Update the company settings.
     */
    public function update(CompanySettingsUpdateRequest $request): RedirectResponse
    {
        CompanySetting::current()->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bedrijfsgegevens opgeslagen.')]);

        return to_route('company-settings.edit');
    }
}
