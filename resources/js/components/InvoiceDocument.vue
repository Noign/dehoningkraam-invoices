<script>
import { formatDate, formatQuantity } from '@/lib/format';
import { formatCents } from '@/lib/money';

export default {
    props: {
        invoice: { type: Object, required: true },
        number: { type: String, default: null },
    },

    methods: {
        formatCents,
        formatDate,
        formatQuantity,

        hasPrice(line) {
            return line.vat_percentage !== null;
        },
    },
};
</script>

<template>
    <article
        class="space-y-8 rounded-xl border border-border bg-card p-6 text-sm text-card-foreground shadow-card md:p-10"
    >
        <header class="flex flex-wrap items-start justify-between gap-6">
            <h2 class="font-heading text-2xl font-semibold">
                {{ invoice.seller.company_name }}
            </h2>
            <p class="font-heading text-2xl font-semibold">Factuur</p>
        </header>

        <div class="grid gap-8 md:grid-cols-2">
            <section>
                <h3 class="mb-2 font-semibold">Bedrijfsgegevens</h3>
                <p>{{ invoice.seller.address }}</p>
                <p>
                    {{ invoice.seller.postal_code }} {{ invoice.seller.city }}
                </p>
                <p>{{ invoice.seller.email }}</p>
                <p v-if="invoice.seller.phone">{{ invoice.seller.phone }}</p>
                <p>KvK: {{ invoice.seller.kvk_number }}</p>
                <p>Btw-id: {{ invoice.seller.vat_number }}</p>
            </section>

            <dl
                class="grid grid-cols-[auto_1fr] content-start gap-x-6 gap-y-1 md:justify-self-end"
            >
                <dt class="font-semibold">Factuurnummer</dt>
                <dd class="md:text-right">{{ number ?? 'Nog niet bekend' }}</dd>
                <dt class="font-semibold">Factuurdatum</dt>
                <dd class="md:text-right">
                    {{ formatDate(invoice.invoice_date) }}
                </dd>
                <dt class="font-semibold">Vervaldatum</dt>
                <dd class="md:text-right">
                    {{ formatDate(invoice.due_date) }}
                </dd>
            </dl>
        </div>

        <section>
            <h3 class="mb-2 font-semibold">Factureren aan</h3>
            <p>{{ invoice.customer.name }}</p>
            <p>{{ invoice.customer.address }}</p>
            <p>
                {{ invoice.customer.postal_code }} {{ invoice.customer.city }}
            </p>
            <p>{{ invoice.customer.email }}</p>
        </section>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-border">
                    <tr>
                        <th scope="col" class="py-2 pr-4 font-semibold">
                            Omschrijving
                        </th>
                        <th
                            scope="col"
                            class="py-2 pr-4 text-right font-semibold"
                        >
                            Aantal
                        </th>
                        <th scope="col" class="py-2 pr-4 font-semibold">
                            Eenheid
                        </th>
                        <th
                            scope="col"
                            class="py-2 pr-4 text-right font-semibold whitespace-nowrap"
                        >
                            Prijs incl. btw
                        </th>
                        <th
                            scope="col"
                            class="py-2 pr-4 text-right font-semibold whitespace-nowrap"
                        >
                            Prijs excl. btw
                        </th>
                        <th
                            scope="col"
                            class="py-2 pr-4 text-right font-semibold"
                        >
                            Btw
                        </th>
                        <th
                            scope="col"
                            class="py-2 text-right font-semibold whitespace-nowrap"
                        >
                            Bedrag excl. btw
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="line in invoice.lines" :key="line.position">
                        <td class="py-2 pr-4">{{ line.description }}</td>
                        <td class="py-2 pr-4 text-right tabular-nums">
                            {{ formatQuantity(line.quantity) }}
                        </td>
                        <td class="py-2 pr-4">{{ line.unit }}</td>
                        <template v-if="hasPrice(line)">
                            <td class="py-2 pr-4 text-right tabular-nums">
                                {{
                                    formatCents(
                                        line.unit_price_including_vat_in_cents,
                                    )
                                }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">
                                {{
                                    formatCents(
                                        line.unit_price_excluding_vat_in_cents,
                                    )
                                }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">
                                {{ line.vat_percentage }}%
                            </td>
                            <td class="py-2 text-right tabular-nums">
                                {{
                                    formatCents(
                                        line.total_excluding_vat_in_cents,
                                    )
                                }}
                            </td>
                        </template>
                        <td v-else colspan="4"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-8">
            <p v-if="invoice.payment_note" class="max-w-sm">
                {{ invoice.payment_note }}
            </p>

            <dl
                class="ml-auto grid w-full max-w-xs grid-cols-[1fr_auto] gap-x-6 gap-y-1"
            >
                <dt class="font-semibold">Subtotaal</dt>
                <dd class="text-right font-semibold tabular-nums">
                    {{ formatCents(invoice.subtotal_in_cents) }}
                </dd>
                <template
                    v-for="rate in invoice.vat_breakdown"
                    :key="rate.percentage"
                >
                    <dt>
                        Btw {{ rate.percentage }}% over
                        {{ formatCents(rate.excluding_vat_in_cents) }}
                    </dt>
                    <dd class="text-right tabular-nums">
                        {{ formatCents(rate.vat_in_cents) }}
                    </dd>
                </template>
                <dt
                    class="mt-1 border-t border-border pt-2 text-base font-semibold"
                >
                    Totaal
                </dt>
                <dd
                    class="mt-1 border-t border-border pt-2 text-right text-base font-semibold tabular-nums"
                >
                    {{ formatCents(invoice.total_in_cents) }}
                </dd>
            </dl>
        </div>

        <section>
            <h3 class="mb-2 font-semibold">Betalingsgegevens</h3>
            <p>IBAN: {{ invoice.seller.iban }}</p>
            <p>Ten name van: {{ invoice.seller.iban_account_name }}</p>
            <p>Graag betalen vóór {{ formatDate(invoice.due_date) }}</p>
            <p v-if="invoice.note" class="mt-2">{{ invoice.note }}</p>
        </section>
    </article>
</template>
