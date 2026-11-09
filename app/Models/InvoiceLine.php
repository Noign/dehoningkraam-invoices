<?php

namespace App\Models;

use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A line of a finalized invoice. Lines without a price are informational, such as a delivery date.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int|null $product_id
 * @property int $position
 * @property string $description
 * @property string|null $quantity
 * @property string|null $unit
 * @property int|null $vat_percentage
 * @property int|null $unit_price_including_vat_in_cents
 * @property int|null $unit_price_excluding_vat_in_cents
 * @property int|null $total_including_vat_in_cents
 * @property int|null $total_excluding_vat_in_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'product_id',
    'position',
    'description',
    'quantity',
    'unit',
    'vat_percentage',
    'unit_price_including_vat_in_cents',
    'unit_price_excluding_vat_in_cents',
    'total_including_vat_in_cents',
    'total_excluding_vat_in_cents',
])]
class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    /**
     * Get the invoice the line belongs to.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Prevent lines of finalized invoices from being changed or removed.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('A line of a finalized invoice cannot be changed.');
        });

        static::deleting(function (): void {
            throw new LogicException('A line of a finalized invoice cannot be deleted.');
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
            'quantity' => 'decimal:2',
            'vat_percentage' => 'integer',
            'unit_price_including_vat_in_cents' => 'integer',
            'unit_price_excluding_vat_in_cents' => 'integer',
            'total_including_vat_in_cents' => 'integer',
            'total_excluding_vat_in_cents' => 'integer',
        ];
    }
}
