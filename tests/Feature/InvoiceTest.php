<?php

use App\Http\Controllers\InvoiceController;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function invoicePayload(Organisation $organisation, Product $product, array $overrides = []): array
{
    return [
        'organisation_id' => $organisation->id,
        'invoice_date' => '2026-10-29',
        'lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '40']],
        ...$overrides,
    ];
}

function honeyProduct(int $priceIncludingVatInCents = 850, int $vatPercentage = 9): Product
{
    return Product::factory()->create([
        'name' => 'Zomerhoning 370gr',
        'unit' => 'st',
        'price_including_vat_in_cents' => $priceIncludingVatInCents,
        'vat_percentage' => $vatPercentage,
    ]);
}

test('guests are redirected to the login page', function (string $method, string $routeName) {
    $invoice = Invoice::factory()->create();

    $response = $this->{$method}(route($routeName, $invoice));

    $response->assertRedirect(route('login'));
})->with([
    'overview' => ['get', 'invoices.index'],
    'create form' => ['get', 'invoices.create'],
    'create preview' => ['post', 'invoices.preview.store'],
    'show preview' => ['get', 'invoices.preview.show'],
    'finalize' => ['post', 'invoices.store'],
    'show' => ['get', 'invoices.show'],
    'download pdf' => ['get', 'invoices.pdf.show'],
]);

test('the preview reproduces the agreed price including vat without storing an invoice', function () {
    CompanySetting::factory()->create();
    $organisation = Organisation::factory()->create(['name' => 'Wilde Melk']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('invoices.preview.store'), invoicePayload($organisation, honeyProduct()));

    $response->assertSessionHasNoErrors()->assertRedirect(route('invoices.preview.show'));
    expect(Invoice::query()->count())->toBe(0);

    $this->get(route('invoices.preview.show'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('invoices/Preview')
        ->where('expectedNumber', '2026-00005')
        ->where('invoice.customer.name', 'Wilde Melk')
        ->where('invoice.subtotal_in_cents', 31193)
        ->where('invoice.vat_in_cents', 2807)
        ->where('invoice.total_in_cents', 34000)
        ->where('invoice.lines.0.description', 'Zomerhoning 370gr')
        ->where('invoice.lines.0.unit', 'st')
        ->where('invoice.lines.0.unit_price_excluding_vat_in_cents', 780)
        ->where('invoice.lines.0.total_excluding_vat_in_cents', 31193));
});

test('vat is broken down per rate and the amounts add up', function () {
    CompanySetting::factory()->create();
    $lines = [
        ['type' => 'product', 'product_id' => honeyProduct(850, 9)->id, 'quantity' => '40'],
        ['type' => 'product', 'product_id' => honeyProduct(1210, 21)->id, 'quantity' => '2'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct(), ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $invoice = session(InvoiceController::DRAFT_SESSION_KEY)['invoice'];

    expect($invoice['vat_breakdown'])->toBe([
        ['percentage' => 9, 'excluding_vat_in_cents' => 31193, 'vat_in_cents' => 2807, 'including_vat_in_cents' => 34000],
        ['percentage' => 21, 'excluding_vat_in_cents' => 2000, 'vat_in_cents' => 420, 'including_vat_in_cents' => 2420],
    ])
        ->and($invoice['subtotal_in_cents'])->toBe(33193)
        ->and($invoice['vat_in_cents'])->toBe(3227)
        ->and($invoice['total_in_cents'])->toBe(36420);
});

test('information lines appear on the invoice without affecting the totals', function () {
    CompanySetting::factory()->create();
    $lines = [
        ['type' => 'product', 'product_id' => honeyProduct()->id, 'quantity' => '40'],
        ['type' => 'info', 'description' => 'Opgehaald op 1-10-2026', 'quantity' => '20'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct(), ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $invoice = session(InvoiceController::DRAFT_SESSION_KEY)['invoice'];

    expect($invoice['total_in_cents'])->toBe(34000)
        ->and($invoice['lines'][1]['description'])->toBe('Opgehaald op 1-10-2026')
        ->and($invoice['lines'][1]['quantity'])->toEqual(20)
        ->and($invoice['lines'][1]['total_including_vat_in_cents'])->toBeNull();
});

test('a custom description replaces the product name on the line', function () {
    CompanySetting::factory()->create();
    $lines = [['type' => 'product', 'product_id' => honeyProduct()->id, 'quantity' => '40', 'description' => 'Zomerhoning (okt)']];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct(), ['lines' => $lines]));

    expect(session(InvoiceController::DRAFT_SESSION_KEY)['invoice']['lines'][0]['description'])->toBe('Zomerhoning (okt)');
});

test('a line can use an agreed price instead of the standard price', function () {
    CompanySetting::factory()->create();
    $product = honeyProduct(850);
    $lines = [['type' => 'product', 'product_id' => $product->id, 'quantity' => '40', 'unit_price' => '7.80']];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $invoice = session(InvoiceController::DRAFT_SESSION_KEY)['invoice'];

    expect($invoice['lines'][0]['unit_price_including_vat_in_cents'])->toBe(780)
        ->and($invoice['lines'][0]['unit_price_excluding_vat_in_cents'])->toBe(716)
        ->and($invoice['total_in_cents'])->toBe(31200)
        ->and($invoice['subtotal_in_cents'])->toBe(28624)
        ->and($invoice['vat_in_cents'])->toBe(2576)
        ->and($product->refresh()->price_including_vat_in_cents)->toBe(850);
});

test('free products lower both the total and the vat', function () {
    CompanySetting::factory()->create();
    $product = honeyProduct(850);
    $lines = [
        ['type' => 'product', 'product_id' => $product->id, 'quantity' => '20'],
        ['type' => 'discount', 'product_id' => $product->id, 'quantity' => '2'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $invoice = session(InvoiceController::DRAFT_SESSION_KEY)['invoice'];

    expect($invoice['lines'][1]['description'])->toBe('Korting: Zomerhoning 370gr')
        ->and($invoice['lines'][1]['unit_price_including_vat_in_cents'])->toBe(850)
        ->and($invoice['lines'][1]['total_including_vat_in_cents'])->toBe(-1700)
        ->and($invoice['lines'][1]['total_excluding_vat_in_cents'])->toBe(-1560)
        ->and($invoice['total_in_cents'])->toBe(15300)
        ->and($invoice['subtotal_in_cents'])->toBe(14036)
        ->and($invoice['vat_in_cents'])->toBe(1264)
        ->and($invoice['vat_breakdown'][0]['including_vat_in_cents'])->toBe(15300);
});

test('a discount can be a fixed amount with its own description', function () {
    CompanySetting::factory()->create();
    $product = honeyProduct(850);
    $lines = [
        ['type' => 'product', 'product_id' => $product->id, 'quantity' => '20'],
        ['type' => 'discount', 'product_id' => $product->id, 'quantity' => '1', 'unit_price' => '10', 'description' => 'Actiekorting'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $invoice = session(InvoiceController::DRAFT_SESSION_KEY)['invoice'];

    expect($invoice['lines'][1]['description'])->toBe('Actiekorting')
        ->and($invoice['total_in_cents'])->toBe(16000);
});

test('a discount is taken off at the vat rate of its own product', function () {
    CompanySetting::factory()->create();
    $lowRate = honeyProduct(850, 9);
    $highRate = honeyProduct(1210, 21);
    $lines = [
        ['type' => 'product', 'product_id' => $lowRate->id, 'quantity' => '10'],
        ['type' => 'product', 'product_id' => $highRate->id, 'quantity' => '10'],
        ['type' => 'discount', 'product_id' => $highRate->id, 'quantity' => '1'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $lowRate, ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    $breakdown = session(InvoiceController::DRAFT_SESSION_KEY)['invoice']['vat_breakdown'];

    expect($breakdown[0]['including_vat_in_cents'])->toBe(8500)
        ->and($breakdown[1]['including_vat_in_cents'])->toBe(10890);
});

test('a discount cannot exceed the amount at its vat rate', function (int $discountVatPercentage, string $quantity) {
    CompanySetting::factory()->create();
    $product = honeyProduct(850, 9);
    $discountProduct = honeyProduct(850, $discountVatPercentage);
    $lines = [
        ['type' => 'product', 'product_id' => $product->id, 'quantity' => '2'],
        ['type' => 'discount', 'product_id' => $discountProduct->id, 'quantity' => $quantity],
    ];

    $response = $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]));

    $response->assertSessionHasErrors(['lines' => 'De korting mag niet hoger zijn dan het bedrag van de producten met hetzelfde btw-tarief.']);
    expect(session()->has(InvoiceController::DRAFT_SESSION_KEY))->toBeFalse();
})->with([
    'more than the products' => [9, '3'],
    'a rate without products' => [21, '1'],
]);

test('a fully discounted invoice is allowed', function () {
    CompanySetting::factory()->create();
    $product = honeyProduct(850);
    $lines = [
        ['type' => 'product', 'product_id' => $product->id, 'quantity' => '2'],
        ['type' => 'discount', 'product_id' => $product->id, 'quantity' => '2'],
    ];

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]))
        ->assertSessionHasNoErrors();

    expect(session(InvoiceController::DRAFT_SESSION_KEY)['invoice']['total_in_cents'])->toBe(0);
});

test('discounts and agreed prices are stored on the final invoice', function () {
    CompanySetting::factory()->create();
    $product = honeyProduct(850);
    $lines = [
        ['type' => 'product', 'product_id' => $product->id, 'quantity' => '20', 'unit_price' => '7.80'],
        ['type' => 'discount', 'product_id' => $product->id, 'quantity' => '2', 'unit_price' => '7.80'],
    ];
    $this->actingAs(User::factory()->create());
    $this->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), $product, ['lines' => $lines]));

    $this->post(route('invoices.store'))->assertSessionHasNoErrors();

    $invoice = Invoice::query()->sole();

    expect($invoice->total_in_cents)->toBe(14040)
        ->and($invoice->lines()->pluck('total_including_vat_in_cents')->all())->toBe([15600, -1560])
        ->and($invoice->lines()->pluck('unit_price_including_vat_in_cents')->all())->toBe([780, 780]);
});

test('the due date defaults to the payment term and can be overridden', function (?string $dueDate, string $invoiceDate, string $expectedDueDate) {
    CompanySetting::factory()->create(['payment_term_months' => 1]);

    $this->actingAs(User::factory()->create())->post(route('invoices.preview.store'), invoicePayload(
        Organisation::factory()->create(),
        honeyProduct(),
        ['invoice_date' => $invoiceDate, 'due_date' => $dueDate],
    ))->assertSessionHasNoErrors();

    expect(session(InvoiceController::DRAFT_SESSION_KEY)['invoice']['due_date'])->toBe($expectedDueDate);
})->with([
    'default' => [null, '2026-10-29', '2026-11-29'],
    'month end does not overflow' => [null, '2026-01-31', '2026-02-28'],
    'overridden' => ['2026-11-15', '2026-10-29', '2026-11-15'],
]);

test('required fields are validated', function () {
    CompanySetting::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('invoices.preview.store'), []);

    $response->assertSessionHasErrors([
        'organisation_id' => 'Kies een organisatie.',
        'invoice_date' => 'Vul een factuurdatum in.',
        'lines' => 'Voeg minimaal één product toe.',
    ]);
});

test('invalid input is rejected', function (Closure $makeOverrides, string $field, string $message) {
    CompanySetting::factory()->create();
    $product = honeyProduct();

    $response = $this->actingAs(User::factory()->create())->post(
        route('invoices.preview.store'),
        invoicePayload(Organisation::factory()->create(), $product, $makeOverrides($product)),
    );

    $response->assertSessionHasErrors([$field => $message]);
    expect(session()->has(InvoiceController::DRAFT_SESSION_KEY))->toBeFalse();
})->with([
    'only information lines' => [fn () => ['lines' => [['type' => 'info', 'description' => 'Geleverd']]], 'lines', 'Voeg minimaal één product toe.'],
    'product line without product' => [fn () => ['lines' => [['type' => 'product', 'quantity' => '1']]], 'lines.0.product_id', 'Kies een product.'],
    'unknown product' => [fn () => ['lines' => [['type' => 'product', 'product_id' => 999, 'quantity' => '1']]], 'lines.0.product_id', 'Dit product bestaat niet meer.'],
    'product line without quantity' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id]]], 'lines.0.quantity', 'Vul een aantal in.'],
    'zero quantity' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '0']]], 'lines.0.quantity', 'Het aantal moet groter zijn dan 0.'],
    'information line without description' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '1'], ['type' => 'info']]], 'lines.1.description', 'Vul een omschrijving in.'],
    'only a discount' => [fn (Product $product) => ['lines' => [['type' => 'discount', 'product_id' => $product->id, 'quantity' => '1']]], 'lines', 'Voeg minimaal één product toe.'],
    'discount without product' => [fn () => ['lines' => [['type' => 'discount', 'quantity' => '1']]], 'lines.0.product_id', 'Kies een product.'],
    'discount without quantity' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '1'], ['type' => 'discount', 'product_id' => $product->id]]], 'lines.1.quantity', 'Vul een aantal in.'],
    'negative price' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '1', 'unit_price' => '-1']]], 'lines.0.unit_price', 'De prijs mag niet negatief zijn.'],
    'price with too many decimals' => [fn (Product $product) => ['lines' => [['type' => 'product', 'product_id' => $product->id, 'quantity' => '1', 'unit_price' => '1.999']]], 'lines.0.unit_price', 'De prijs mag maximaal twee decimalen hebben.'],
    'due date before invoice date' => [fn () => ['due_date' => '2026-10-01'], 'due_date', 'De vervaldatum mag niet voor de factuurdatum liggen.'],
    'unknown organisation' => [fn () => ['organisation_id' => 999], 'organisation_id', 'Deze organisatie bestaat niet meer.'],
]);

test('a draft cannot be made before the company settings are filled in', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct()));

    $response->assertSessionHasErrors(['organisation_id' => 'Vul eerst de bedrijfsgegevens in bij de instellingen.']);
});

test('a draft cannot be dated in a year before the last invoice', function () {
    CompanySetting::factory()->create(['next_invoice_number_year' => 2027, 'next_invoice_number' => 3]);

    $response = $this->actingAs(User::factory()->create())
        ->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct()));

    $response->assertSessionHasErrors(['invoice_date' => 'De factuurdatum mag niet in een eerder jaar liggen dan de laatste factuur.']);
});

test('the create form restores the draft when returning from the preview', function () {
    CompanySetting::factory()->create(['invoice_note' => 'Dank!']);
    $organisation = Organisation::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('invoices.create'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('invoices/Create')
        ->where('hasCompanySettings', true)
        ->where('defaultNote', 'Dank!')
        ->where('draftInput', null));

    $this->post(route('invoices.preview.store'), invoicePayload($organisation, honeyProduct()));

    $this->get(route('invoices.create'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('draftInput.organisation_id', $organisation->id));
});

test('the create form reports missing company settings', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('invoices.create'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->where('hasCompanySettings', false));
});

test('the preview page redirects to the form without a draft', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('invoices.preview.show'));

    $response->assertRedirect(route('invoices.create'));
});

test('finalizing stores exactly what the preview showed and assigns the number', function () {
    $settings = CompanySetting::factory()->create(['company_name' => 'Imkerij Test']);
    $organisation = Organisation::factory()->create(['name' => 'Wilde Melk']);
    $product = honeyProduct();
    $this->actingAs(User::factory()->create());
    $this->post(route('invoices.preview.store'), invoicePayload($organisation, $product, ['payment_note' => 'Contant voldaan']));

    $product->update(['price_including_vat_in_cents' => 9999]);
    $organisation->update(['name' => 'Andere Naam']);
    $settings->update(['company_name' => 'Andere Naam']);

    $response = $this->post(route('invoices.store'));
    $invoice = Invoice::query()->sole();
    $response->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->number)->toBe('2026-00005')
        ->and($invoice->total_in_cents)->toBe(34000)
        ->and($invoice->customer['name'])->toBe('Wilde Melk')
        ->and($invoice->seller['company_name'])->toBe('Imkerij Test')
        ->and($invoice->payment_note)->toBe('Contant voldaan')
        ->and($invoice->organisation_id)->toBe($organisation->id)
        ->and($invoice->lines()->sole()->unit_price_including_vat_in_cents)->toBe(850)
        ->and(session()->has(InvoiceController::DRAFT_SESSION_KEY))->toBeFalse();

    $settings->refresh();
    expect($settings->next_invoice_number)->toBe(6)
        ->and($settings->next_invoice_number_year)->toBe(2026);
});

test('submitting the finalize request twice creates one invoice', function () {
    CompanySetting::factory()->create();
    $this->actingAs(User::factory()->create());
    $this->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct()));

    $this->post(route('invoices.store'));
    $this->post(route('invoices.store'))->assertRedirect(route('invoices.index'));

    expect(Invoice::query()->count())->toBe(1);
});

test('invoice numbers follow on and restart in a new year', function () {
    CompanySetting::factory()->create(['next_invoice_number' => 12]);
    $organisation = Organisation::factory()->create();
    $product = honeyProduct();
    $this->actingAs(User::factory()->create());

    foreach (['2026-10-29', '2026-11-01', '2027-01-02', '2027-01-03'] as $invoiceDate) {
        $this->post(route('invoices.preview.store'), invoicePayload($organisation, $product, ['invoice_date' => $invoiceDate]))
            ->assertSessionHasNoErrors();
        $this->post(route('invoices.store'))->assertSessionHasNoErrors();
    }

    expect(Invoice::query()->orderBy('id')->pluck('number')->all())
        ->toBe(['2026-00012', '2026-00013', '2027-00001', '2027-00002']);
});

test('finalizing fails when the number is already taken', function () {
    CompanySetting::factory()->create(['next_invoice_number' => 5]);
    Invoice::factory()->create(['number' => '2026-00005']);
    $this->actingAs(User::factory()->create());
    $this->post(route('invoices.preview.store'), invoicePayload(Organisation::factory()->create(), honeyProduct()));

    $response = $this->post(route('invoices.store'));

    $response->assertSessionHasErrors(['number' => 'Factuurnummer 2026-00005 is al gebruikt. Pas het volgende factuurnummer aan bij de bedrijfsgegevens.']);
    expect(Invoice::query()->count())->toBe(1)
        ->and(session()->has(InvoiceController::DRAFT_SESSION_KEY))->toBeTrue();
});

test('finalizing still works when the product and organisation were deleted after the preview', function () {
    CompanySetting::factory()->create();
    $organisation = Organisation::factory()->create(['name' => 'Wilde Melk']);
    $product = honeyProduct();
    $this->actingAs(User::factory()->create());
    $this->post(route('invoices.preview.store'), invoicePayload($organisation, $product));

    $product->delete();
    $organisation->delete();

    $this->post(route('invoices.store'))->assertSessionHasNoErrors();

    $invoice = Invoice::query()->sole();

    expect($invoice->organisation_id)->toBeNull()
        ->and($invoice->customer['name'])->toBe('Wilde Melk')
        ->and($invoice->lines()->sole()->product_id)->toBeNull();
});

test('deleting a product or organisation keeps the final invoice intact', function () {
    $organisation = Organisation::factory()->create();
    $product = honeyProduct();
    $invoice = Invoice::factory()->create(['organisation_id' => $organisation->id]);
    $line = InvoiceLine::factory()->for($invoice)->create(['product_id' => $product->id]);

    $product->delete();
    $organisation->delete();

    expect($invoice->fresh()->organisation_id)->toBeNull()
        ->and($line->fresh()->product_id)->toBeNull()
        ->and($line->fresh()->total_including_vat_in_cents)->toBe(34000);
});

test('final invoices and their lines cannot be changed or deleted', function (Closure $attempt) {
    $line = InvoiceLine::factory()->create();

    expect(fn () => $attempt($line))->toThrow(LogicException::class);
})->with([
    'update invoice' => [fn (InvoiceLine $line) => $line->invoice->update(['note' => 'Gewijzigd'])],
    'delete invoice' => [fn (InvoiceLine $line) => $line->invoice->delete()],
    'update line' => [fn (InvoiceLine $line) => $line->update(['description' => 'Gewijzigd'])],
    'delete line' => [fn (InvoiceLine $line) => $line->delete()],
]);

test('no route allows changing or deleting a final invoice', function (string $method) {
    $invoice = Invoice::factory()->create();

    $response = $this->actingAs(User::factory()->create())->{$method}("/invoices/{$invoice->id}");

    $response->assertMethodNotAllowed();
    expect(Invoice::query()->whereKey($invoice)->exists())->toBeTrue();
})->with(['put', 'patch', 'delete']);

test('the overview lists invoices with the newest number first', function () {
    Invoice::factory()->create(['number' => '2026-00005']);
    Invoice::factory()->create(['number' => '2026-00006']);

    $response = $this->actingAs(User::factory()->create())->get(route('invoices.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('invoices/Index')
        ->where('invoices.0.number', '2026-00006')
        ->where('invoices.1.number', '2026-00005'));
});

test('a final invoice can be viewed with its lines', function () {
    $line = InvoiceLine::factory()->create(['description' => 'Zomerhoning']);

    $response = $this->actingAs(User::factory()->create())->get(route('invoices.show', $line->invoice));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('invoices/Show')
        ->where('invoice.number', $line->invoice->number)
        ->where('invoice.invoice_date', '2026-10-29')
        ->where('invoice.lines.0.description', 'Zomerhoning'));
});

test('a final invoice can be downloaded as a pdf', function () {
    $line = InvoiceLine::factory()->create();
    $invoice = $line->invoice;

    $response = $this->actingAs(User::factory()->create())->get(route('invoices.pdf.show', $invoice));

    $response->assertOk()->assertDownload("Factuur-{$invoice->number}.pdf");
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('the pdf contains the invoice details, discounts and information lines', function () {
    $invoice = Invoice::factory()->create([
        'number' => '2026-00005',
        'seller' => [
            'company_name' => 'Imkerij Test',
            'address' => 'Teststraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Teststad',
            'email' => 'info@imkerij.test',
            'phone' => null,
            'kvk_number' => '12345678',
            'vat_number' => 'NL123456789B01',
            'iban' => 'NL00TEST0123456789',
            'iban_account_name' => 'Imkerij Rekening',
        ],
        'customer' => [
            'name' => 'Wilde <Melk>',
            'address' => 'Tienboerenweg 22',
            'postal_code' => '3641 RA',
            'city' => 'Mijdrecht',
            'email' => 'klant@wildemelk.test',
        ],
        'payment_note' => 'Contant voldaan bij levering',
        'note' => 'Bedankt voor uw bestelling!',
    ]);
    InvoiceLine::factory()->for($invoice)->create(['position' => 1, 'description' => 'Zomerhoning', 'quantity' => '40.00']);
    InvoiceLine::factory()->for($invoice)->create([
        'position' => 2,
        'description' => 'Korting: Zomerhoning',
        'quantity' => '2.50',
        'total_including_vat_in_cents' => -1700,
        'total_excluding_vat_in_cents' => -1560,
    ]);
    InvoiceLine::factory()->for($invoice)->create([
        'position' => 3,
        'description' => 'Geleverd op 29-10-2026',
        'quantity' => null,
        'unit' => null,
        'vat_percentage' => null,
        'unit_price_including_vat_in_cents' => null,
        'unit_price_excluding_vat_in_cents' => null,
        'total_including_vat_in_cents' => null,
        'total_excluding_vat_in_cents' => null,
    ]);

    $html = view('invoices.pdf', ['invoice' => $invoice->load('lines')])->render();

    expect($html)
        ->toContain('2026-00005', 'Imkerij Test', 'NL00TEST0123456789', 'Imkerij Rekening', 'NL123456789B01')
        ->toContain('Wilde &lt;Melk&gt;', 'klant@wildemelk.test')
        ->not->toContain('Wilde <Melk>')
        ->toContain('29-10-2026', '29-11-2026')
        ->toContain('Prijs incl. btw', '€ 8,50', '€ 7,80')
        ->toContain('Zomerhoning', '>40<', '>2,5<', '€ -15,60', 'Geleverd op 29-10-2026')
        ->toContain('€ 311,93', '€ 28,07', '€ 340,00')
        ->toContain('Contant voldaan bij levering', 'Bedankt voor uw bestelling!')
        ->not->toContain('Telefoon:');
});
