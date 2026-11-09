<script>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Building2, Pencil, Plus, Trash2 } from "@lucide/vue";
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
import { create, destroy, edit, index } from "@/routes/organisations";

export default {
    components: {
        Building2,
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
        Pencil,
        Plus,
        Trash2,
    },

    layout: {
        breadcrumbs: [{ title: "Organisaties", href: index() }],
    },

    props: {
        organisations: { type: Array, required: true },
    },

    data() {
        return {
            organisationPendingDeletion: null,
            lastOrganisationNameForDeletion: "",
            deleteForm: useForm({}),
        };
    },

    computed: {
        createUrl() {
            return create.url();
        },

        isDeleteDialogOpen() {
            return this.organisationPendingDeletion !== null;
        },
    },

    methods: {
        editUrl(organisation) {
            return edit.url(organisation.id);
        },

        confirmDeletion(organisation) {
            this.organisationPendingDeletion = organisation;
            this.lastOrganisationNameForDeletion = organisation.name;
        },

        closeDeleteDialog() {
            this.organisationPendingDeletion = null;
        },

        onDeleteDialogOpenChange(isOpen) {
            if (!isOpen) {
                this.closeDeleteDialog();
            }
        },

        deleteOrganisation() {
            this.deleteForm.submit(
                destroy(this.organisationPendingDeletion.id),
                {
                    preserveScroll: true,
                    onSuccess: () => this.closeDeleteDialog(),
                },
            );
        },
    },
};
</script>

<template>
    <Head title="Organisaties" />

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                class="mb-0!"
                title="Organisaties"
                description="Beheer de klanten waarvoor je facturen maakt."
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus />
                    Nieuwe organisatie
                </Link>
            </Button>
        </div>

        <div
            v-if="organisations.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border px-6 py-16 text-center"
        >
            <Building2 class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">Nog geen organisaties</p>
                <p class="text-sm text-muted-foreground">
                    Voeg je eerste klant toe om er een factuur voor te kunnen
                    maken.
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
                        <th scope="col" class="px-4 py-3 font-medium">Naam</th>
                        <th scope="col" class="px-4 py-3 font-medium">Adres</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            E-mailadres
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Acties</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="organisation in organisations"
                        :key="organisation.id"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ organisation.name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ organisation.address }},
                            {{ organisation.postal_code }}
                            {{ organisation.city }}
                        </td>
                        <td class="px-4 py-3">
                            {{ organisation.email }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button as-child variant="ghost" size="icon-sm">
                                    <Link
                                        :href="editUrl(organisation)"
                                        :aria-label="`${organisation.name} bewerken`"
                                    >
                                        <Pencil />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`${organisation.name} verwijderen`"
                                    @click="confirmDeletion(organisation)"
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
                    <DialogTitle>Organisatie verwijderen?</DialogTitle>
                    <DialogDescription>
                        Weet je zeker dat je
                        {{ lastOrganisationNameForDeletion }} wilt verwijderen?
                        Dit kun je niet ongedaan maken.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="closeDeleteDialog">
                        Annuleren
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="deleteForm.processing"
                        @click="deleteOrganisation"
                    >
                        Verwijderen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
