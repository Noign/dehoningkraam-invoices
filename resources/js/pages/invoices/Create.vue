<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCents } from '@/lib/money';
import { edit as editCompanySettings } from '@/routes/company-settings';
import { create, index } from '@/routes/invoices';
import { store as storePreview } from '@/routes/invoices/preview';
import { index as organisationIndex } from '@/routes/organisations';
import { index as productIndex } from '@/routes/products';

let nextLineKey = 1;

function makeLine(line) {
    return {
        key: nextLineKey++,
        type: line.type,
        product_id: line.product_id ? String(line.product_id) : '',
        description: line.description ?? '',
        quantity: line.quantity ?? '',
        unit_price: line.unit_price ?? '',
    };
}

export default {
    components: {
        Button,
        Head,
        Heading,
        Input,
        InputError,
        Label,
        Link,
        Plus,
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
        Trash2,
    },

    layout: {
        breadcrumbs: [
            { title: 'Facturen', href: index() },
            { title: 'Nieuwe factuur', href: create() },
        ],
    },

    props: {
        organisations: { type: Array, required: true },
        products: { type: Array, required: true },
        hasCompanySettings: { type: Boolean, required: true },
        defaultNote: { type: String, default: null },
        paymentTermMonths: { type: Number, default: null },
        today: { type: String, required: true },
        draftInput: { type: Object, default: null },
    },

    data() {
        const draft = this.draftInput;

        return {
            form: useForm({
                organisation_id: draft ? String(draft.organisation_id) : '',
                invoice_date: draft?.invoice_date ?? this.today,
                due_date: draft?.due_date ?? '',
                payment_note: draft?.payment_note ?? '',
                note: draft ? (draft.note ?? '') : (this.defaultNote ?? ''),
                lines: (draft?.lines ?? [{ type: 'product' }]).map(makeLine),
            }),
        };
    },

    computed: {
        companySettingsUrl() {
            return editCompanySettings.url();
        },

        organisationIndexUrl() {
            return organisationIndex.url();
        },

        productIndexUrl() {
            return productIndex.url();
        },

        canCreateInvoice() {
            return (
                this.hasCompanySettings &&
                this.organisations.length > 0 &&
                this.products.length > 0
            );
        },

        dueDateHint() {
            const unit = this.paymentTermMonths === 1 ? 'maand' : 'maanden';

            return `Leeg laten voor ${this.paymentTermMonths} ${unit} na de factuurdatum.`;
        },
    },

    methods: {
        formatCents,

        addLine(type) {
            this.form.lines.push(makeLine({ type }));
        },

        removeLine(line) {
            this.form.lines = this.form.lines.filter(
                (otherLine) => otherLine.key !== line.key,
            );
        },

        lineError(index, field) {
            return this.form.errors[`lines.${index}.${field}`];
        },

        findProduct(line) {
            return this.products.find(
                (candidate) => String(candidate.id) === line.product_id,
            );
        },

        descriptionPlaceholder(line) {
            if (line.type === 'info') {
                return 'Bijvoorbeeld: Geleverd op 29-10-2026';
            }

            const name = this.findProduct(line)?.name ?? 'Naam van het product';

            return line.type === 'discount' ? `Korting: ${name}` : name;
        },

        standardPriceInEuros(line) {
            const product = this.findProduct(line);

            return product
                ? (product.price_including_vat_in_cents / 100).toFixed(2)
                : '';
        },

        standardPriceHint(line) {
            const product = this.findProduct(line);

            return product
                ? `Standaard ${formatCents(product.price_including_vat_in_cents)}`
                : '';
        },

        submit() {
            this.form.submit(storePreview());
        },
    },
};
</script>

<template>
    <Head title="Nieuwe factuur" />

    <div class="mx-auto w-full max-w-7xl p-4">
        <Heading title="Nieuwe factuur" />

        <div v-if="!canCreateInvoice" class="max-w-xl space-y-3">
            <p class="font-medium">Je kunt nog geen factuur maken</p>
            <ul class="list-inside list-disc space-y-1 text-sm">
                <li v-if="!hasCompanySettings">
                    Vul eerst de
                    <Link :href="companySettingsUrl" class="text-link underline"
                        >bedrijfsgegevens</Link
                    >
                    in.
                </li>
                <li v-if="organisations.length === 0">
                    Voeg minimaal één
                    <Link
                        :href="organisationIndexUrl"
                        class="text-link underline"
                        >organisatie</Link
                    >
                    toe.
                </li>
                <li v-if="products.length === 0">
                    Voeg minimaal één
                    <Link :href="productIndexUrl" class="text-link underline"
                        >product</Link
                    >
                    toe.
                </li>
            </ul>
        </div>

        <form v-else class="max-w-5xl space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid content-start gap-2">
                    <Label for="organisation_id">Organisatie</Label>
                    <Select v-model="form.organisation_id">
                        <SelectTrigger id="organisation_id" class="w-full">
                            <SelectValue placeholder="Kies een organisatie" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="organisation in organisations"
                                :key="organisation.id"
                                :value="String(organisation.id)"
                            >
                                {{ organisation.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.organisation_id" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="invoice_date">Factuurdatum</Label>
                    <Input
                        id="invoice_date"
                        v-model="form.invoice_date"
                        type="date"
                        required
                    />
                    <InputError :message="form.errors.invoice_date" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="due_date">Vervaldatum (optioneel)</Label>
                    <Input id="due_date" v-model="form.due_date" type="date" />
                    <p class="text-sm text-muted-foreground">
                        {{ dueDateHint }}
                    </p>
                    <InputError :message="form.errors.due_date" />
                </div>
            </div>

            <section class="space-y-4">
                <Heading
                    variant="small"
                    title="Factuurregels"
                    description="Laat de prijs leeg voor de standaardprijs. Een korting gaat van het totaal af, bijvoorbeeld 2 potten gratis of een vast bedrag. Informatieregels, zoals een leverdatum, tellen niet mee."
                />

                <div
                    v-for="(line, index) in form.lines"
                    :key="line.key"
                    class="grid items-start gap-4 rounded-xl border border-border bg-card p-4 md:grid-cols-[1fr_1fr_6rem_8rem_auto]"
                >
                    <div
                        v-if="line.type !== 'info'"
                        class="grid content-start gap-2"
                    >
                        <Label :for="`line-product-${line.key}`">
                            {{
                                line.type === 'discount'
                                    ? 'Korting op product'
                                    : 'Product'
                            }}
                        </Label>
                        <Select v-model="line.product_id">
                            <SelectTrigger
                                :id="`line-product-${line.key}`"
                                class="w-full"
                            >
                                <SelectValue placeholder="Kies een product" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="String(product.id)"
                                >
                                    {{ product.name }} ·
                                    {{
                                        formatCents(
                                            product.price_including_vat_in_cents,
                                        )
                                    }}
                                    incl. btw
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="lineError(index, 'product_id')" />
                    </div>

                    <div
                        class="grid content-start gap-2"
                        :class="{ 'md:col-span-2': line.type === 'info' }"
                    >
                        <Label :for="`line-description-${line.key}`">
                            {{
                                line.type === 'info'
                                    ? 'Informatie'
                                    : 'Omschrijving (optioneel)'
                            }}
                        </Label>
                        <Input
                            :id="`line-description-${line.key}`"
                            v-model="line.description"
                            :placeholder="descriptionPlaceholder(line)"
                        />
                        <InputError
                            :message="lineError(index, 'description')"
                        />
                    </div>

                    <div class="grid content-start gap-2">
                        <Label :for="`line-quantity-${line.key}`">
                            {{
                                line.type === 'info'
                                    ? 'Aantal (opt.)'
                                    : 'Aantal'
                            }}
                        </Label>
                        <Input
                            :id="`line-quantity-${line.key}`"
                            v-model="line.quantity"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="lineError(index, 'quantity')" />
                    </div>

                    <div
                        v-if="line.type !== 'info'"
                        class="grid content-start gap-2"
                    >
                        <Label :for="`line-price-${line.key}`">
                            Prijs incl. btw
                        </Label>
                        <Input
                            :id="`line-price-${line.key}`"
                            v-model="line.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="standardPriceInEuros(line)"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ standardPriceHint(line) }}
                        </p>
                        <InputError :message="lineError(index, 'unit_price')" />
                    </div>
                    <div v-else class="hidden md:block"></div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="md:mt-5.5"
                        aria-label="Regel verwijderen"
                        @click="removeLine(line)"
                    >
                        <Trash2 />
                    </Button>
                </div>

                <InputError :message="form.errors.lines" />

                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="addLine('product')"
                    >
                        <Plus />
                        Product toevoegen
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="addLine('discount')"
                    >
                        <Plus />
                        Korting toevoegen
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        @click="addLine('info')"
                    >
                        <Plus />
                        Informatieregel toevoegen
                    </Button>
                </div>
            </section>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="payment_note">Betaalnotitie (optioneel)</Label>
                    <Input
                        id="payment_note"
                        v-model="form.payment_note"
                        placeholder="Bijvoorbeeld: Contant voldaan bij levering"
                    />
                    <InputError :message="form.errors.payment_note" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="note">Opmerking (optioneel)</Label>
                    <Input id="note" v-model="form.note" />
                    <InputError :message="form.errors.note" />
                </div>
            </div>

            <Button type="submit" :disabled="form.processing">
                Preview bekijken
            </Button>
        </form>
    </div>
</template>
