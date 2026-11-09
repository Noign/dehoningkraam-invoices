<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\BuildInvoiceDraft;
use App\Http\Requests\InvoiceDraftRequest;
use App\Models\CompanySetting;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class InvoicePreviewController extends Controller
{
    /**
     * Calculate a draft from the form and remember it until it is finalized.
     */
    public function store(InvoiceDraftRequest $request, BuildInvoiceDraft $buildInvoiceDraft): RedirectResponse
    {
        $request->session()->put(InvoiceController::DRAFT_SESSION_KEY, [
            'input' => $request->validated(),
            'invoice' => $buildInvoiceDraft->handle($request->validated()),
        ]);

        return to_route('invoices.preview.show');
    }

    /**
     * Show the remembered draft as it will appear once finalized.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $draft = $request->session()->get(InvoiceController::DRAFT_SESSION_KEY);

        if ($draft === null) {
            return to_route('invoices.create');
        }

        $year = Carbon::parse($draft['invoice']['invoice_date'])->year;
        $sequence = CompanySetting::current()->nextInvoiceSequenceFor($year);

        return Inertia::render('invoices/Preview', [
            'invoice' => $draft['invoice'],
            'expectedNumber' => $sequence === null ? null : Invoice::formatNumber($year, $sequence),
        ]);
    }
}
