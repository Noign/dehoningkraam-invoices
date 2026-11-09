<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import InvoiceDocument from '@/components/InvoiceDocument.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { create, index, store } from '@/routes/invoices';

export default {
    components: {
        Button,
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        Head,
        InputError,
        InvoiceDocument,
        Link,
    },

    layout: {
        breadcrumbs: [
            { title: 'Facturen', href: index() },
            { title: 'Nieuwe factuur', href: create() },
            { title: 'Preview', href: create() },
        ],
    },

    props: {
        invoice: { type: Object, required: true },
        expectedNumber: { type: String, default: null },
    },

    data() {
        return {
            isConfirmationOpen: false,
            form: useForm({}),
        };
    },

    computed: {
        editUrl() {
            return create.url();
        },
    },

    methods: {
        finalize() {
            this.form.submit(store(), {
                onError: () => {
                    this.isConfirmationOpen = false;
                },
            });
        },
    },
};
</script>

<template>
    <Head title="Preview factuur" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <div
            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gold bg-accent p-4"
        >
            <div>
                <p class="font-medium">Dit is een preview</p>
                <p class="text-sm text-muted-foreground">
                    De factuur is nog niet definitief. Controleer alles goed:
                    daarna kun je niets meer wijzigen.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="editUrl">Terug en aanpassen</Link>
                </Button>
                <Button @click="isConfirmationOpen = true">
                    Definitief maken
                </Button>
            </div>
        </div>

        <InputError :message="form.errors.number" />
        <InputError :message="form.errors.invoice_date" />

        <InvoiceDocument :invoice="invoice" :number="expectedNumber" />

        <Dialog v-model:open="isConfirmationOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Factuur definitief maken?</DialogTitle>
                    <DialogDescription>
                        De factuur krijgt nummer {{ expectedNumber }}. Hierna
                        kun je de factuur niet meer wijzigen of verwijderen.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <Button
                        variant="secondary"
                        @click="isConfirmationOpen = false"
                    >
                        Annuleren
                    </Button>
                    <Button :disabled="form.processing" @click="finalize">
                        Definitief maken
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
