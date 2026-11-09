<script>
import { Head } from "@inertiajs/vue3";
import Heading from "@/components/Heading.vue";
import ProductForm from "@/components/ProductForm.vue";
import { index } from "@/routes/products";

export default {
    components: { Head, Heading, ProductForm },

    layout: {
        breadcrumbs: [
            { title: "Producten", href: index() },
            { title: "Product bewerken", href: index() },
        ],
    },

    props: {
        product: { type: Object, required: true },
        vatPercentages: { type: Array, required: true },
    },
};
</script>

<template>
    <Head title="Product bewerken" />

    <div class="mx-auto w-full max-w-7xl p-4">
        <Heading title="Product bewerken" :description="product.name" />
        <ProductForm :product="product" :vat-percentages="vatPercentages" />
    </div>
</template>
