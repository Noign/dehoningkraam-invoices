<?php

namespace App\Models;

use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $company_name
 * @property string $address
 * @property string $postal_code
 * @property string $city
 * @property string $email
 * @property string|null $phone
 * @property string $kvk_number
 * @property string $vat_number
 * @property string $iban
 * @property string $iban_account_name
 * @property int $payment_term_months
 * @property string|null $invoice_note
 * @property int $next_invoice_number
 * @property int|null $next_invoice_number_year
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
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
    'invoice_note',
    'next_invoice_number',
    'next_invoice_number_year',
])]
class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use HasFactory;

    /**
     * The defaults shown until the settings have been saved for the first time.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'payment_term_months' => 1,
        'invoice_note' => 'Bedankt voor uw bestelling!',
        'next_invoice_number' => 5,
    ];

    /**
     * Get the application's single settings record, or an unsaved one with the defaults.
     */
    public static function current(): self
    {
        return self::query()->firstOrNew();
    }

    /**
     * Get the sequence of the next invoice in a year, or null when that year has been closed.
     *
     * Numbering restarts at 1 when the first invoice of a new year is made.
     */
    public function nextInvoiceSequenceFor(int $year): ?int
    {
        return match ($year <=> ($this->next_invoice_number_year ?? $year)) {
            -1 => null,
            0 => $this->next_invoice_number,
            1 => 1,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_term_months' => 'integer',
            'next_invoice_number' => 'integer',
            'next_invoice_number_year' => 'integer',
        ];
    }
}
