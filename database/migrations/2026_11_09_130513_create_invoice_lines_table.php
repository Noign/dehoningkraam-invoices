<?php

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Invoice::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->unsignedTinyInteger('vat_percentage')->nullable();
            $table->unsignedBigInteger('unit_price_including_vat_in_cents')->nullable();
            $table->unsignedBigInteger('unit_price_excluding_vat_in_cents')->nullable();
            $table->unsignedBigInteger('total_including_vat_in_cents')->nullable();
            $table->unsignedBigInteger('total_excluding_vat_in_cents')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
