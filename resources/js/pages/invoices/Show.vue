<script>
import { Head } from '@inertiajs/vue3';
import { Download, Mail } from '@lucide/vue';
import InvoiceDocument from '@/components/InvoiceDocument.vue';
import { Button } from '@/components/ui/button';
import { buildInvoiceMailtoUrl } from '@/lib/invoiceEmail';
import { index } from '@/routes/invoices';
import { show as showPdf } from '@/routes/invoices/pdf';

const MAIL_DELAY_AFTER_DOWNLOAD_IN_MS = 500;

export default {
    components: { Button, Download, Head, InvoiceDocument, Mail },

    layout: {
        breadcrumbs: [
            { title: 'Facturen', href: index() },
            { title: 'Factuur', href: index() },
        ],
    },

    props: {
        invoice: { type: Object, required: true },
    },

    computed: {
        pdfUrl() {
            return showPdf.url(this.invoice.id);
        },
    },

    methods: {
        openEmailDraft() {
            const downloadLink = document.createElement('a');
            downloadLink.href = this.pdfUrl;
            downloadLink.download = '';
            downloadLink.click();

            // Browsers cannot attach files to a mailto link, so the PDF is downloaded alongside it.
            // The delay keeps the navigation to the mail client from cancelling the download.
            window.setTimeout(() => {
                window.location.href = buildInvoiceMailtoUrl(this.invoice);
            }, MAIL_DELAY_AFTER_DOWNLOAD_IN_MS);
        },
    },
};
</script>

<template>
    <Head :title="`Factuur ${invoice.number}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="max-w-md text-sm text-muted-foreground">
                Mail opstellen downloadt de pdf en opent je mailprogramma met de
                mail al ingevuld. Voeg daar de pdf toe als bijlage.
            </p>
            <div class="flex items-center gap-2">
                <Button as-child variant="secondary">
                    <a :href="pdfUrl" download>
                        <Download />
                        Download PDF
                    </a>
                </Button>
                <Button @click="openEmailDraft">
                    <Mail />
                    Mail opstellen
                </Button>
            </div>
        </div>

        <InvoiceDocument :invoice="invoice" :number="invoice.number" />
    </div>
</template>
