<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\InvoicePreviewController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('products', ProductsController::class)->except('show');

    Route::resource('organisations', OrganisationController::class)->except('show');

    Route::post('invoices/preview', [InvoicePreviewController::class, 'store'])->name('invoices.preview.store');
    Route::get('invoices/preview', [InvoicePreviewController::class, 'show'])->name('invoices.preview.show');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('invoices/{invoice}/pdf', [InvoicePdfController::class, 'show'])->name('invoices.pdf.show');
});

require __DIR__.'/settings.php';
