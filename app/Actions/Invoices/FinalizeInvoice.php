<?php

namespace App\Actions\Invoices;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeInvoice
{
    /**
     * Assign the next invoice number to a draft and store it as a final invoice.
     *
     * @param  array{lines: list<array<string, mixed>>, invoice_date: string, organisation_id: int|string}  $draft
     *
     * @throws ValidationException
     */
    public function handle(array $draft): Invoice
    {
        return DB::transaction(function () use ($draft): Invoice {
            $settings = CompanySetting::query()->lockForUpdate()->firstOrFail();
            $year = Carbon::parse($draft['invoice_date'])->year;
            $sequence = $settings->nextInvoiceSequenceFor($year);

            if ($sequence === null) {
                throw ValidationException::withMessages([
                    'invoice_date' => 'De factuurdatum mag niet in een eerder jaar liggen dan de laatste factuur.',
                ]);
            }

            $number = Invoice::formatNumber($year, $sequence);

            if (Invoice::query()->where('number', $number)->exists()) {
                throw ValidationException::withMessages([
                    'number' => "Factuurnummer {$number} is al gebruikt. Pas het volgende factuurnummer aan bij de bedrijfsgegevens.",
                ]);
            }

            $invoice = Invoice::create([
                ...Arr::except($draft, 'lines'),
                'number' => $number,
                'organisation_id' => Organisation::query()->whereKey($draft['organisation_id'])->value('id'),
            ]);

            $existingProductIds = Product::query()
                ->whereKey(collect($draft['lines'])->pluck('product_id')->filter())
                ->pluck('id');

            $invoice->lines()->createMany(collect($draft['lines'])->map(fn (array $line): array => [
                ...$line,
                'product_id' => $existingProductIds->contains($line['product_id']) ? $line['product_id'] : null,
            ])->all());

            $settings->update([
                'next_invoice_number' => $sequence + 1,
                'next_invoice_number_year' => $year,
            ]);

            return $invoice;
        });
    }
}
