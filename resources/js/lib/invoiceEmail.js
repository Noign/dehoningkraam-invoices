import { formatDate } from '@/lib/format';
import { formatCents } from '@/lib/money';

export function buildInvoiceMailtoUrl(invoice) {
    const subject = `Factuur ${invoice.number} van ${invoice.seller.company_name}`;
    const body = [
        'Beste,',
        '',
        `Hierbij ontvangt u factuur ${invoice.number} ter waarde van ${formatCents(invoice.total_in_cents)}.`,
        `Wij verzoeken u het bedrag vóór ${formatDate(invoice.due_date)} over te maken op ${invoice.seller.iban} ten name van ${invoice.seller.iban_account_name}, onder vermelding van het factuurnummer.`,
        '',
        'Met vriendelijke groet,',
        invoice.seller.company_name,
    ].join('\n');

    return `mailto:${encodeURIComponent(invoice.customer.email)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}
