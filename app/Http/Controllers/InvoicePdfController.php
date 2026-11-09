<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    /**
     * Download a finalized invoice as a PDF.
     */
    public function show(Invoice $invoice): Response
    {
        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice->load('lines')])
            ->setOption('isFontSubsettingEnabled', true)
            ->download("Factuur-{$invoice->number}.pdf");
    }
}
