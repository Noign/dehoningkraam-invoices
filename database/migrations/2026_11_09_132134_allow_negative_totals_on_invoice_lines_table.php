<?php

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
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->bigInteger('total_including_vat_in_cents')->nullable()->change();
            $table->bigInteger('total_excluding_vat_in_cents')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('total_including_vat_in_cents')->nullable()->change();
            $table->unsignedBigInteger('total_excluding_vat_in_cents')->nullable()->change();
        });
    }
};
