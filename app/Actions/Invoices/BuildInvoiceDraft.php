<?php

namespace App\Actions\Invoices;

use App\Models\CompanySetting;
use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BuildInvoiceDraft
{
    /**
     * Calculate a complete invoice from validated input, without storing anything.
     *
     * Prices include VAT, so each line's amount excluding VAT is calculated backwards from its
     * total. VAT is the remainder, which guarantees the invoice adds up to the agreed total.
     * Discount lines have negative totals at the VAT rate of their product, so they lower the VAT too.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function handle(array $input): array
    {
        $settings = CompanySetting::query()->first();

        if ($settings === null) {
            throw ValidationException::withMessages([
                'organisation_id' => 'Vul eerst de bedrijfsgegevens in bij de instellingen.',
            ]);
        }

        $invoiceDate = Carbon::parse($input['invoice_date']);

        if ($settings->nextInvoiceSequenceFor($invoiceDate->year) === null) {
            throw ValidationException::withMessages([
                'invoice_date' => 'De factuurdatum mag niet in een eerder jaar liggen dan de laatste factuur.',
            ]);
        }

        $lines = collect($this->buildLines($input['lines']));
        $pricedLines = $lines->whereNotNull('vat_percentage');
        $vatBreakdown = $this->buildVatBreakdown($pricedLines);

        if (collect($vatBreakdown)->contains(fn (array $rate): bool => $rate['including_vat_in_cents'] < 0)) {
            throw ValidationException::withMessages([
                'lines' => 'De korting mag niet hoger zijn dan het bedrag van de producten met hetzelfde btw-tarief.',
            ]);
        }

        $subtotal = $pricedLines->sum('total_excluding_vat_in_cents');
        $total = $pricedLines->sum('total_including_vat_in_cents');

        return [
            'organisation_id' => $input['organisation_id'],
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => isset($input['due_date'])
                ? Carbon::parse($input['due_date'])->toDateString()
                : $invoiceDate->copy()->addMonthsNoOverflow($settings->payment_term_months)->toDateString(),
            'seller' => $settings->only([
                'company_name',
                'address',
                'postal_code',
                'city',
                'email',
                'phone',
                'kvk_number',
                'vat_number',
                'iban',
                'iban_account_name',
                'payment_term_months',
            ]),
            'customer' => Organisation::query()
                ->findOrFail($input['organisation_id'])
                ->only(['name', 'address', 'postal_code', 'city', 'email']),
            'vat_breakdown' => $vatBreakdown,
            'subtotal_in_cents' => $subtotal,
            'vat_in_cents' => $total - $subtotal,
            'total_in_cents' => $total,
            'payment_note' => $input['payment_note'] ?? null,
            'note' => $input['note'] ?? null,
            'lines' => $lines->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $inputLines
     * @return array<int, array<string, mixed>>
     */
    private function buildLines(array $inputLines): array
    {
        $products = Product::query()
            ->findMany(collect($inputLines)->pluck('product_id')->filter())
            ->keyBy('id');

        return collect($inputLines)->values()->map(function (array $line, int $index) use ($products): array {
            $position = $index + 1;
            $quantity = isset($line['quantity']) ? (float) $line['quantity'] : null;

            if ($line['type'] === 'info') {
                return [
                    'position' => $position,
                    'product_id' => null,
                    'description' => $line['description'],
                    'quantity' => $quantity,
                    'unit' => null,
                    'vat_percentage' => null,
                    'unit_price_including_vat_in_cents' => null,
                    'unit_price_excluding_vat_in_cents' => null,
                    'total_including_vat_in_cents' => null,
                    'total_excluding_vat_in_cents' => null,
                ];
            }

            $product = $products->get($line['product_id']);
            $isDiscount = $line['type'] === 'discount';
            $vatDivisor = (100 + $product->vat_percentage) / 100;
            $unitPrice = isset($line['unit_price'])
                ? (int) round((float) $line['unit_price'] * 100)
                : $product->price_including_vat_in_cents;
            $totalIncludingVat = (int) round($quantity * $unitPrice) * ($isDiscount ? -1 : 1);

            return [
                'position' => $position,
                'product_id' => $product->id,
                'description' => $line['description'] ?? ($isDiscount ? "Korting: {$product->name}" : $product->name),
                'quantity' => $quantity,
                'unit' => $product->unit,
                'vat_percentage' => $product->vat_percentage,
                'unit_price_including_vat_in_cents' => $unitPrice,
                'unit_price_excluding_vat_in_cents' => (int) round($unitPrice / $vatDivisor),
                'total_including_vat_in_cents' => $totalIncludingVat,
                'total_excluding_vat_in_cents' => (int) round($totalIncludingVat / $vatDivisor),
            ];
        })->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $pricedLines
     * @return array<int, array{percentage: int, excluding_vat_in_cents: int, vat_in_cents: int, including_vat_in_cents: int}>
     */
    private function buildVatBreakdown(Collection $pricedLines): array
    {
        return $pricedLines
            ->groupBy('vat_percentage')
            ->sortKeys()
            ->map(function (Collection $lines, int $percentage): array {
                $excludingVat = (int) $lines->sum('total_excluding_vat_in_cents');
                $includingVat = (int) $lines->sum('total_including_vat_in_cents');

                return [
                    'percentage' => $percentage,
                    'excluding_vat_in_cents' => $excludingVat,
                    'vat_in_cents' => $includingVat - $excludingVat,
                    'including_vat_in_cents' => $includingVat,
                ];
            })
            ->values()
            ->all();
    }
}
