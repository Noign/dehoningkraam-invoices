<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $unit
 * @property int $price_including_vat_in_cents
 * @property int $vat_percentage
 * @property string|null $image_path
 * @property-read string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['image_url'])]
#[Fillable(['name', 'description', 'unit', 'price_including_vat_in_cents', 'vat_percentage', 'image_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The Dutch VAT rates a product can be sold under.
     *
     * @var list<int>
     */
    public const array VAT_PERCENTAGES = [9, 21];

    /**
     * Get the public URL of the product image.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path === null
            ? null
            : Storage::disk('public')->url($this->image_path));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_including_vat_in_cents' => 'integer',
            'vat_percentage' => 'integer',
        ];
    }
}
