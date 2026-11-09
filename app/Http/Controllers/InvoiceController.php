<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\FinalizeInvoice;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * The session key under which the unfinished draft is remembered.
     */
    public const string DRAFT_SESSION_KEY = 'invoice_draft';

    /**
     * Show the overview of all invoices, newest first.
     */
    public function index(): Response
    {
        return Inertia::render('invoices/Index', [
            'invoices' => Invoice::query()->orderByDesc('number')->get(),
        ]);
    }

    /**
     * Show the form for a new invoice, restoring the draft when returning from the preview.
     */
    public function create(Request $request): Response
    {
        $settings = CompanySetting::query()->first();

        return Inertia::render('invoices/Create', [
            'organisations' => Organisation::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(),
            'hasCompanySettings' => $settings !== null,
            'defaultNote' => $settings?->invoice_note,
            'paymentTermMonths' => $settings?->payment_term_months,
            'today' => now()->toDateString(),
            'draftInput' => $request->session()->get(self::DRAFT_SESSION_KEY.'.input'),
        ]);
    }

    /**
     * Finalize the draft that was shown in the preview.
     */
    public function store(Request $request, FinalizeInvoice $finalizeInvoice): RedirectResponse
    {
        $draft = $request->session()->get(self::DRAFT_SESSION_KEY);

        if ($draft === null) {
            return to_route('invoices.index');
        }

        $invoice = $finalizeInvoice->handle($draft['invoice']);

        $request->session()->forget(self::DRAFT_SESSION_KEY);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Factuur {$invoice->number} is definitief."]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Show a finalized invoice.
     */
    public function show(Invoice $invoice): Response
    {
        return Inertia::render('invoices/Show', [
            'invoice' => $invoice->load('lines'),
        ]);
    }
}
