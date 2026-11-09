<script>
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { formatCents } from '@/lib/money';
import { create, index, show } from '@/routes/invoices';

export default {
    components: { Button, FileText, Head, Heading, Link, Plus },

    layout: {
        breadcrumbs: [{ title: 'Facturen', href: index() }],
    },

    props: {
        invoices: { type: Array, required: true },
    },

    computed: {
        createUrl() {
            return create.url();
        },
    },

    methods: {
        formatCents,
        formatDate,

        showUrl(invoice) {
            return show.url(invoice.id);
        },
    },
};
</script>

<template>
    <Head title="Facturen" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                class="mb-0!"
                title="Facturen"
                description="Bekijk je definitieve facturen of maak een nieuwe aan."
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus />
                    Nieuwe factuur
                </Link>
            </Button>
        </div>

        <div
            v-if="invoices.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border px-6 py-16 text-center"
        >
            <FileText class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">Nog geen facturen</p>
                <p class="text-sm text-muted-foreground">
                    Maak je eerste factuur aan voor een van je organisaties.
                </p>
            </div>
        </div>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-border bg-card"
        >
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Factuurnummer
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Klant</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Factuurdatum
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Vervaldatum
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Totaal
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="invoice in invoices" :key="invoice.id">
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            <Link
                                :href="showUrl(invoice)"
                                class="text-link underline-offset-4 hover:underline"
                            >
                                {{ invoice.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ invoice.customer.name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(invoice.invoice_date) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(invoice.due_date) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatCents(invoice.total_in_cents) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
