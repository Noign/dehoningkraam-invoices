<script>
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, update } from '@/routes/company-settings';

export default {
    components: { Button, Head, Heading, Input, InputError, Label },

    layout: {
        breadcrumbs: [{ title: 'Bedrijfsgegevens', href: edit() }],
    },

    props: {
        settings: { type: Object, required: true },
        invoiceNumberYear: { type: Number, required: true },
    },

    data() {
        return {
            form: useForm({
                company_name: this.settings.company_name ?? '',
                address: this.settings.address ?? '',
                postal_code: this.settings.postal_code ?? '',
                city: this.settings.city ?? '',
                email: this.settings.email ?? '',
                phone: this.settings.phone ?? '',
                kvk_number: this.settings.kvk_number ?? '',
                vat_number: this.settings.vat_number ?? '',
                iban: this.settings.iban ?? '',
                iban_account_name: this.settings.iban_account_name ?? '',
                payment_term_months: this.settings.payment_term_months,
                invoice_note: this.settings.invoice_note ?? '',
                next_invoice_number: this.settings.next_invoice_number,
            }),
        };
    },

    computed: {
        nextInvoiceNumberPreview() {
            const sequence = String(this.form.next_invoice_number ?? '');

            return `${this.invoiceNumberYear}-${sequence.padStart(5, '0')}`;
        },
    },

    methods: {
        submit() {
            this.form.submit(update(), {
                preserveScroll: true,
                onSuccess: (page) => {
                    this.form.iban = page.props.settings.iban;
                    this.form.vat_number = page.props.settings.vat_number;
                },
            });
        },
    },
};
</script>

<template>
    <Head title="Bedrijfsgegevens" />

    <h1 class="sr-only">Bedrijfsgegevens</h1>

    <form class="space-y-10" @submit.prevent="submit">
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Bedrijfsgegevens"
                description="Deze gegevens komen op elke factuur te staan."
            />

            <div class="grid gap-2">
                <Label for="company_name">Bedrijfsnaam</Label>
                <Input
                    id="company_name"
                    v-model="form.company_name"
                    required
                    autocomplete="organization"
                />
                <InputError :message="form.errors.company_name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Adres</Label>
                <Input
                    id="address"
                    v-model="form.address"
                    required
                    autocomplete="street-address"
                />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="postal_code">Postcode</Label>
                    <Input
                        id="postal_code"
                        v-model="form.postal_code"
                        required
                        autocomplete="postal-code"
                    />
                    <InputError :message="form.errors.postal_code" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="city">Plaats</Label>
                    <Input
                        id="city"
                        v-model="form.city"
                        required
                        autocomplete="address-level2"
                    />
                    <InputError :message="form.errors.city" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="email">E-mailadres</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="phone">Telefoon (optioneel)</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        autocomplete="tel"
                    />
                    <InputError :message="form.errors.phone" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="kvk_number">KvK-nummer</Label>
                    <Input
                        id="kvk_number"
                        v-model="form.kvk_number"
                        required
                        inputmode="numeric"
                    />
                    <InputError :message="form.errors.kvk_number" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="vat_number">Btw-id</Label>
                    <Input id="vat_number" v-model="form.vat_number" required />
                    <InputError :message="form.errors.vat_number" />
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <Heading
                variant="small"
                title="Betaalgegevens"
                description="Hiermee weet de klant hoe en wanneer de factuur betaald moet worden."
            />

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="iban">IBAN</Label>
                    <Input id="iban" v-model="form.iban" required />
                    <InputError :message="form.errors.iban" />
                </div>

                <div class="grid content-start gap-2">
                    <Label for="iban_account_name">Ten name van</Label>
                    <Input
                        id="iban_account_name"
                        v-model="form.iban_account_name"
                        required
                    />
                    <InputError :message="form.errors.iban_account_name" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="payment_term_months">
                    Betaaltermijn in maanden
                </Label>
                <Input
                    id="payment_term_months"
                    v-model="form.payment_term_months"
                    type="number"
                    min="1"
                    max="12"
                    step="1"
                    required
                    class="sm:w-32"
                />
                <p class="text-sm text-muted-foreground">
                    Hiermee wordt de vervaldatum van een factuur berekend.
                </p>
                <InputError :message="form.errors.payment_term_months" />
            </div>

            <div class="grid gap-2">
                <Label for="invoice_note"
                    >Opmerking op factuur (optioneel)</Label
                >
                <Input id="invoice_note" v-model="form.invoice_note" />
                <InputError :message="form.errors.invoice_note" />
            </div>
        </div>

        <div class="space-y-6">
            <Heading
                variant="small"
                title="Factuurnummering"
                description="Elke definitieve factuur krijgt automatisch het volgende nummer. In een nieuw jaar begint de nummering weer bij 1."
            />

            <div class="grid gap-2">
                <Label for="next_invoice_number">Volgend factuurnummer</Label>
                <Input
                    id="next_invoice_number"
                    v-model="form.next_invoice_number"
                    type="number"
                    min="1"
                    max="99999"
                    step="1"
                    required
                    class="sm:w-32"
                />
                <p class="text-sm text-muted-foreground">
                    De volgende factuur krijgt nummer
                    {{ nextInvoiceNumberPreview }}.
                </p>
                <InputError :message="form.errors.next_invoice_number" />
            </div>
        </div>

        <Button type="submit" :disabled="form.processing">Opslaan</Button>
    </form>
</template>
