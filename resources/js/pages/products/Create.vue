<script>
import { Head } from "@inertiajs/vue3";
import Heading from "@/components/Heading.vue";
import ProductForm from "@/components/ProductForm.vue";
import { create, index } from "@/routes/products";

export default {
    components: { Head, Heading, ProductForm },

    layout: {
        breadcrumbs: [
            { title: "Producten", href: index() },
            { title: "Nieuw product", href: create() },
        ],
    },

    props: {
        vatPercentages: { type: Array, required: true },
    },
};
</script>

<template>
    <Head title="Nieuw product" />

    <div class="mx-auto w-full max-w-7xl p-4">
        <Heading title="Nieuw product" />
        <ProductForm :vat-percentages="vatPercentages" />
    </div>
</template>
