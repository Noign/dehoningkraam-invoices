<script>
import { Head } from "@inertiajs/vue3";
import Heading from "@/components/Heading.vue";
import OrganisationForm from "@/components/OrganisationForm.vue";
import { index } from "@/routes/organisations";

export default {
    components: { Head, Heading, OrganisationForm },

    layout: {
        breadcrumbs: [
            { title: "Organisaties", href: index() },
            { title: "Organisatie bewerken", href: index() },
        ],
    },

    props: {
        organisation: { type: Object, required: true },
    },
};
</script>

<template>
    <Head title="Organisatie bewerken" />

    <div class="mx-auto w-full max-w-7xl p-4">
        <Heading
            title="Organisatie bewerken"
            :description="organisation.name"
        />
        <OrganisationForm :organisation="organisation" />
    </div>
</template>
