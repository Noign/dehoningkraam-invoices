@php
    $money = fn (int $cents): string => '€ '.number_format($cents / 100, 2, ',', '.');
    $quantity = fn (?string $value): string => $value === null ? '' : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Factuur {{ $invoice->number }}</title>
    <style>
        @page { margin: 36px 42px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; line-height: 1.5; color: #241f16; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0; vertical-align: top; text-align: left; }
        .right { text-align: right; }
        .banner { background: #3b2f20; color: #ffffff; padding: 10px 14px; font-size: 18px; font-weight: bold; }
        .document-title { margin: 8px 0 22px; text-align: right; font-size: 15px; font-weight: bold; }
        .block { margin-bottom: 22px; }
        .label { width: 110px; }
        .section-title { background: #3b2f20; color: #ffffff; padding: 3px 6px; margin-bottom: 5px; font-weight: bold; }
        .section-title-light { background: #f3e8c9; padding: 3px 6px; margin-bottom: 5px; font-weight: bold; }
        .lines th { background: #dca33a; padding: 5px 6px; font-weight: bold; }
        .lines td { padding: 5px 6px; border-bottom: 1px solid #e2d9c3; }
        .nowrap { white-space: nowrap; }
        .totals { width: 280px; margin-left: auto; }
        .totals td { padding: 3px 6px; }
        .grand-total td { background: #f3e8c9; font-size: 12px; font-weight: bold; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="banner">{{ $invoice->seller['company_name'] }}</div>
    <div class="document-title">FACTUUR</div>

    <table class="block">
        <tr>
            <td style="width: 58%">
                <div class="section-title-light" style="width: 85%">Bedrijfsgegevens</div>
                <table>
                    <tr><td class="label">Adres:</td><td>{{ $invoice->seller['address'] }}</td></tr>
                    <tr><td class="label">Postcode / plaats:</td><td>{{ $invoice->seller['postal_code'] }} {{ $invoice->seller['city'] }}</td></tr>
                    <tr><td class="label">E-mail:</td><td>{{ $invoice->seller['email'] }}</td></tr>
                    @if ($invoice->seller['phone'] ?? null)
                        <tr><td class="label">Telefoon:</td><td>{{ $invoice->seller['phone'] }}</td></tr>
                    @endif
                    <tr><td class="label">KvK:</td><td>{{ $invoice->seller['kvk_number'] }}</td></tr>
                    <tr><td class="label">Btw-id:</td><td>{{ $invoice->seller['vat_number'] }}</td></tr>
                </table>
            </td>
            <td>
                <table>
                    <tr><td class="bold">Factuurnummer:</td><td class="right">{{ $invoice->number }}</td></tr>
                    <tr><td class="bold">Factuurdatum:</td><td class="right">{{ $invoice->invoice_date->format('d-m-Y') }}</td></tr>
                    <tr><td class="bold">Vervaldatum:</td><td class="right">{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="block">
        <div class="section-title">Factureren aan</div>
        <table>
            <tr><td class="label">Naam / bedrijf:</td><td>{{ $invoice->customer['name'] }}</td></tr>
            <tr><td class="label">Adres:</td><td>{{ $invoice->customer['address'] }}</td></tr>
            <tr><td class="label">Postcode / plaats:</td><td>{{ $invoice->customer['postal_code'] }} {{ $invoice->customer['city'] }}</td></tr>
            <tr><td class="label">E-mail:</td><td>{{ $invoice->customer['email'] }}</td></tr>
        </table>
    </div>

    <table class="lines block">
        <thead>
            <tr>
                <th>Omschrijving</th>
                <th class="right">Aantal</th>
                <th>Eenheid</th>
                <th class="right nowrap">Prijs incl. btw</th>
                <th class="right nowrap">Prijs excl. btw</th>
                <th class="right">Btw</th>
                <th class="right nowrap">Bedrag excl. btw</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ $quantity($line->quantity) }}</td>
                    <td>{{ $line->unit }}</td>
                    @if ($line->vat_percentage !== null)
                        <td class="right nowrap">{{ $money($line->unit_price_including_vat_in_cents) }}</td>
                        <td class="right nowrap">{{ $money($line->unit_price_excluding_vat_in_cents) }}</td>
                        <td class="right">{{ $line->vat_percentage }}%</td>
                        <td class="right nowrap">{{ $money($line->total_excluding_vat_in_cents) }}</td>
                    @else
                        <td colspan="4"></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="block">
        <tr>
            <td>{{ $invoice->payment_note }}</td>
            <td style="width: 280px">
                <table class="totals">
                    <tr class="bold">
                        <td>Subtotaal</td>
                        <td class="right nowrap">{{ $money($invoice->subtotal_in_cents) }}</td>
                    </tr>
                    @foreach ($invoice->vat_breakdown as $rate)
                        <tr>
                            <td>Btw {{ $rate['percentage'] }}% over {{ $money($rate['excluding_vat_in_cents']) }}</td>
                            <td class="right nowrap">{{ $money($rate['vat_in_cents']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="grand-total">
                        <td>Totaal</td>
                        <td class="right nowrap">{{ $money($invoice->total_in_cents) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div>
        <div class="section-title">Betalingsgegevens</div>
        <table>
            <tr><td class="label">IBAN:</td><td>{{ $invoice->seller['iban'] }}</td></tr>
            <tr><td class="label">Ten name van:</td><td>{{ $invoice->seller['iban_account_name'] }}</td></tr>
            <tr><td class="label">Betalen vóór:</td><td>{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
            @if ($invoice->note)
                <tr><td class="label">Opmerking:</td><td>{{ $invoice->note }}</td></tr>
            @endif
        </table>
    </div>
</body>
</html>
