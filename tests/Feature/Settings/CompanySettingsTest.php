<?php

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validCompanySettingsPayload(array $overrides = []): array
{
    return [
        'company_name' => 'Imkerij Test',
        'address' => 'Teststraat 1',
        'postal_code' => '1234 AB',
        'city' => 'Teststad',
        'email' => 'info@imkerij.test',
        'phone' => '0612345678',
        'kvk_number' => '12345678',
        'vat_number' => 'NL123456789B01',
        'iban' => 'NL00TEST0123456789',
        'iban_account_name' => 'Imkerij Test',
        'payment_term_months' => 1,
        'invoice_note' => 'Dank u wel!',
        'next_invoice_number' => 7,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function (string $method) {
    $response = $this->{$method}(route('company-settings.edit'));

    $response->assertRedirect(route('login'));
})->with(['get', 'put']);

test('the page shows the defaults before anything is saved', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('company-settings.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/Company')
        ->where('settings.payment_term_months', 1)
        ->where('settings.invoice_note', 'Bedankt voor uw bestelling!')
        ->where('settings.next_invoice_number', 5)
        ->where('invoiceNumberYear', now()->year));
    expect(CompanySetting::query()->count())->toBe(0);
});

test('the page shows the saved settings', function () {
    CompanySetting::factory()->create(['company_name' => 'Imkerij Test']);

    $response = $this->actingAs(User::factory()->create())->get(route('company-settings.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('settings.company_name', 'Imkerij Test'));
});

test('saving for the first time creates the settings', function () {
    $response = $this->actingAs(User::factory()->create())
        ->put(route('company-settings.update'), validCompanySettingsPayload());

    $response->assertSessionHasNoErrors()->assertRedirect(route('company-settings.edit'));

    $this->assertDatabaseHas('company_settings', validCompanySettingsPayload());
});

test('saving again updates the existing settings instead of adding a record', function () {
    CompanySetting::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('company-settings.update'), validCompanySettingsPayload(['city' => 'Andere Stad']))
        ->assertSessionHasNoErrors();

    expect(CompanySetting::query()->sole()->city)->toBe('Andere Stad');
});

test('the iban and vat number are stored without spaces and in capitals', function () {
    $this->actingAs(User::factory()->create())->put(route('company-settings.update'), validCompanySettingsPayload([
        'iban' => 'nl00 test 0123 4567 89',
        'vat_number' => 'nl 1234.56.789.b01',
    ]))->assertSessionHasNoErrors();

    $settings = CompanySetting::query()->sole();

    expect($settings->iban)->toBe('NL00TEST0123456789')
        ->and($settings->vat_number)->toBe('NL123456789B01');
});

test('phone and invoice note are optional', function () {
    $this->actingAs(User::factory()->create())->put(route('company-settings.update'), validCompanySettingsPayload([
        'phone' => null,
        'invoice_note' => null,
    ]))->assertSessionHasNoErrors();

    $settings = CompanySetting::query()->sole();

    expect($settings->phone)->toBeNull()
        ->and($settings->invoice_note)->toBeNull();
});

test('required fields are validated', function () {
    $response = $this->actingAs(User::factory()->create())->put(route('company-settings.update'), []);

    $response->assertSessionHasErrors([
        'company_name' => 'Vul een bedrijfsnaam in.',
        'address' => 'Vul een adres in.',
        'postal_code' => 'Vul een postcode in.',
        'city' => 'Vul een plaats in.',
        'email' => 'Vul een e-mailadres in.',
        'kvk_number' => 'Vul een KvK-nummer in.',
        'vat_number' => 'Vul een btw-id in.',
        'iban' => 'Vul een IBAN in.',
        'iban_account_name' => 'Vul de naam van de rekeninghouder in.',
        'payment_term_months' => 'Vul een betaaltermijn in.',
        'next_invoice_number' => 'Vul het volgende factuurnummer in.',
    ]);
    expect(CompanySetting::query()->count())->toBe(0);
});

test('invalid values are rejected', function (array $overrides, string $field, string $message) {
    $response = $this->actingAs(User::factory()->create())
        ->put(route('company-settings.update'), validCompanySettingsPayload($overrides));

    $response->assertSessionHasErrors([$field => $message]);
    expect(CompanySetting::query()->count())->toBe(0);
})->with([
    'invalid email' => [['email' => 'geen-email'], 'email', 'Vul een geldig e-mailadres in.'],
    'kvk number too short' => [['kvk_number' => '1234567'], 'kvk_number', 'Een KvK-nummer bestaat uit 8 cijfers.'],
    'kvk number with letters' => [['kvk_number' => '1234567a'], 'kvk_number', 'Een KvK-nummer bestaat uit 8 cijfers.'],
    'invalid vat number' => [['vat_number' => 'NL123'], 'vat_number', 'Vul een geldig btw-id in, bijvoorbeeld NL123456789B01.'],
    'invalid iban' => [['iban' => '12345'], 'iban', 'Vul een geldig IBAN in.'],
    'payment term too short' => [['payment_term_months' => 0], 'payment_term_months', 'De betaaltermijn moet tussen 1 en 12 maanden liggen.'],
    'payment term too long' => [['payment_term_months' => 13], 'payment_term_months', 'De betaaltermijn moet tussen 1 en 12 maanden liggen.'],
]);

test('the next invoice number cannot be set to a number that is already used', function (int $nextInvoiceNumber) {
    CompanySetting::factory()->create(['next_invoice_number' => 11, 'next_invoice_number_year' => 2026]);
    Invoice::factory()->create(['number' => '2026-00010']);

    $response = $this->actingAs(User::factory()->create())
        ->put(route('company-settings.update'), validCompanySettingsPayload(['next_invoice_number' => $nextInvoiceNumber]));

    $response->assertSessionHasErrors(['next_invoice_number' => "Factuurnummer 2026-000{$nextInvoiceNumber} of een hoger nummer is al gebruikt."]);
    expect(CompanySetting::query()->sole()->next_invoice_number)->toBe(11);
})->with(['the used number' => 10]);

test('the next invoice number can be raised above the numbers in use', function () {
    CompanySetting::factory()->create(['next_invoice_number' => 11, 'next_invoice_number_year' => 2026]);
    Invoice::factory()->create(['number' => '2026-00010']);

    $this->actingAs(User::factory()->create())
        ->put(route('company-settings.update'), validCompanySettingsPayload(['next_invoice_number' => 20]))
        ->assertSessionHasNoErrors();

    expect(CompanySetting::query()->sole()->next_invoice_number)->toBe(20);
});
