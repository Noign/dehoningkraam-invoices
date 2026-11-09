<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A finalized invoice. Records are immutable: only drafts, which live in the session, can change.
 *
 * @property int $id
 * @property string $number
 * @property int|null $organisation_id
 * @property Carbon $invoice_date
 * @property Carbon $due_date
 * @property array<string, mixed> $seller
 * @property array<string, mixed> $customer
 * @property list<array{percentage: int, excluding_vat_in_cents: int, vat_in_cents: int, including_vat_in_cents: int}> $vat_breakdown
 * @property int $subtotal_in_cents
 * @property int $vat_in_cents
 * @property int $total_in_cents
 * @property string|null $payment_note
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number',
    'organisation_id',
    'invoice_date',
    'due_date',
    'seller',
    'customer',
    'vat_breakdown',
    'subtotal_in_cents',
    'vat_in_cents',
    'total_in_cents',
    'payment_note',
    'note',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * Format a year and sequence as an invoice number, for example 2026-00005.
     */
    public static function formatNumber(int $year, int $sequence): string
    {
        return sprintf('%d-%05d', $year, $sequence);
    }

    /**
     * Get the lines of the invoice in their original order.
     *
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    /**
     * Get the organisation the invoice was made for, if it still exists.
     *
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Prevent finalized invoices from being changed or removed.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('A finalized invoice cannot be changed.');
        });

        static::deleting(function (): void {
            throw new LogicException('A finalized invoice cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'seller' => 'array',
            'customer' => 'array',
            'vat_breakdown' => 'array',
            'subtotal_in_cents' => 'integer',
            'vat_in_cents' => 'integer',
            'total_in_cents' => 'integer',
        ];
    }
}
