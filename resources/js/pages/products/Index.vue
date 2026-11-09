<script>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Package, Pencil, Plus, Trash2 } from "@lucide/vue";
import Heading from "@/components/Heading.vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { formatCents } from "@/lib/money";
import { create, destroy, edit, index } from "@/routes/products";

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
        Heading,
        Link,
        Package,
        Pencil,
        Plus,
        Trash2,
    },

    layout: {
        breadcrumbs: [{ title: "Producten", href: index() }],
    },

    props: {
        products: { type: Array, required: true },
    },

    data() {
        return {
            productPendingDeletion: null,
            lastProductNameForDeletion: "",
            deleteForm: useForm({}),
        };
    },

    computed: {
        createUrl() {
            return create.url();
        },

        isDeleteDialogOpen() {
            return this.productPendingDeletion !== null;
        },
    },

    methods: {
        formatCents,

        editUrl(product) {
            return edit.url(product.id);
        },

        confirmDeletion(product) {
            this.productPendingDeletion = product;
            this.lastProductNameForDeletion = product.name;
        },

        closeDeleteDialog() {
            this.productPendingDeletion = null;
        },

        onDeleteDialogOpenChange(isOpen) {
            if (!isOpen) {
                this.closeDeleteDialog();
            }
        },

        deleteProduct() {
            this.deleteForm.submit(destroy(this.productPendingDeletion.id), {
                preserveScroll: true,
                onSuccess: () => this.closeDeleteDialog(),
            });
        },
    },
};
</script>

<template>
    <Head title="Producten" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                class="mb-0!"
                title="Producten"
                description="Beheer de producten die je op een factuur kunt zetten."
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus />
                    Nieuw product
                </Link>
            </Button>
        </div>

        <div
            v-if="products.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border px-6 py-16 text-center"
        >
            <Package class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">Nog geen producten</p>
                <p class="text-sm text-muted-foreground">
                    Maak je eerste product aan om het op een factuur te kunnen
                    zetten.
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
                            Product
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium whitespace-nowrap"
                        >
                            Prijs (incl. btw)
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Btw
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Acties</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="product in products" :key="product.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    alt=""
                                    class="size-10 shrink-0 rounded-md object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                                >
                                    <Package class="size-4" />
                                </div>
                                <div class="min-w-48">
                                    <p class="font-medium">
                                        {{ product.name }}
                                    </p>
                                    <p
                                        v-if="product.description"
                                        class="line-clamp-1 text-muted-foreground"
                                    >
                                        {{ product.description }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{
                                formatCents(
                                    product.price_including_vat_in_cents,
                                )
                            }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ product.vat_percentage }}%
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link
                                        :href="editUrl(product)"
                                        :aria-label="`${product.name} bewerken`"
                                    >
                                        <Pencil />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`${product.name} verwijderen`"
                                    @click="confirmDeletion(product)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog
            :open="isDeleteDialogOpen"
            @update:open="onDeleteDialogOpenChange"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Product verwijderen?</DialogTitle>
                    <DialogDescription>
                        Weet je zeker dat je
                        {{ lastProductNameForDeletion }} wilt verwijderen? Dit
                        kun je niet ongedaan maken.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="closeDeleteDialog">
                        Annuleren
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="deleteForm.processing"
                        @click="deleteProduct"
                    >
                        Verwijderen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
