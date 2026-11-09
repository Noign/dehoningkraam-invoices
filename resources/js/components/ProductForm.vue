<script>
import { Link, useForm } from '@inertiajs/vue3';
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
import { index, store, update } from '@/routes/products';

const DEFAULT_VAT_PERCENTAGE = '9';

export default {
    components: {
        Button,
        Input,
        InputError,
        Label,
        Link,
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
    },

    props: {
        product: { type: Object, default: null },
        vatPercentages: { type: Array, required: true },
    },

    data() {
        return {
            form: useForm({
                name: this.product?.name ?? '',
                description: this.product?.description ?? '',
                price: this.product
                    ? (this.product.price_including_vat_in_cents / 100).toFixed(
                          2,
                      )
                    : '',
                unit: this.product?.unit ?? 'st',
                vat_percentage: this.product
                    ? String(this.product.vat_percentage)
                    : DEFAULT_VAT_PERCENTAGE,
                image: null,
            }),
        };
    },

    computed: {
        indexUrl() {
            return index.url();
        },

        submitLabel() {
            return this.product ? 'Opslaan' : 'Product aanmaken';
        },
    },

    methods: {
        onImageSelected(event) {
            this.form.image = event.target.files[0] ?? null;
        },

        submit() {
            if (this.product) {
                // Browsers can only send multipart bodies (for the image) via POST.
                this.form
                    .transform((data) => ({ ...data, _method: 'put' }))
                    .post(update.url(this.product.id));

                return;
            }

            this.form.submit(store());
        },
    },
};
</script>

<template>
    <form class="max-w-xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Naam</Label>
            <Input id="name" v-model="form.name" required autocomplete="off" />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Omschrijving (optioneel)</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid content-start gap-2">
                <Label for="price">Prijs in euro's (incl. btw)</Label>
                <Input
                    id="price"
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                />
                <InputError :message="form.errors.price" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="vat_percentage">Btw-percentage</Label>
                <Select v-model="form.vat_percentage">
                    <SelectTrigger id="vat_percentage" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="percentage in vatPercentages"
                            :key="percentage"
                            :value="String(percentage)"
                        >
                            {{ percentage }}%
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.vat_percentage" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="unit">Eenheid</Label>
            <Input id="unit" v-model="form.unit" required class="sm:w-32" />
            <p class="text-sm text-muted-foreground">
                Komt op de factuur te staan, bijvoorbeeld "st" of "doos".
            </p>
            <InputError :message="form.errors.unit" />
        </div>

        <div class="grid gap-2">
            <Label for="image">Afbeelding (optioneel)</Label>
            <img
                v-if="product?.image_url"
                :src="product.image_url"
                alt="Huidige afbeelding"
                class="size-24 rounded-md object-cover"
            />
            <input
                id="image"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-secondary-foreground"
                @change="onImageSelected"
            />
            <p class="text-sm text-muted-foreground">
                JPG, PNG of WebP, maximaal 5 MB.
                <template v-if="product?.image_url">
                    Een nieuwe afbeelding vervangt de huidige.
                </template>
            </p>
            <InputError :message="form.errors.image" />
        </div>

        <div class="flex items-center gap-2">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
            <Button as-child variant="secondary">
                <Link :href="indexUrl">Annuleren</Link>
            </Button>
        </div>
    </form>
</template>
