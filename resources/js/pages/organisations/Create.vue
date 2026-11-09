<script>
import { Head } from "@inertiajs/vue3";
import Heading from "@/components/Heading.vue";
import OrganisationForm from "@/components/OrganisationForm.vue";
import { create, index } from "@/routes/organisations";

export default {
    components: { Head, Heading, OrganisationForm },

    layout: {
        breadcrumbs: [
            { title: "Organisaties", href: index() },
            { title: "Nieuwe organisatie", href: create() },
        ],
    },
};
</script>

<template>
    <Head title="Nieuwe organisatie" />

    <div class="mx-auto w-full max-w-7xl p-4">
        <Heading title="Nieuwe organisatie" />
        <OrganisationForm />
    </div>
</template>
