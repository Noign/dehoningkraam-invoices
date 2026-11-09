<script>
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/organisations';

export default {
    components: { Button, Input, InputError, Label, Link },

    props: {
        organisation: { type: Object, default: null },
    },

    data() {
        return {
            form: useForm({
                name: this.organisation?.name ?? '',
                address: this.organisation?.address ?? '',
                postal_code: this.organisation?.postal_code ?? '',
                city: this.organisation?.city ?? '',
                email: this.organisation?.email ?? '',
            }),
        };
    },

    computed: {
        indexUrl() {
            return index.url();
        },

        submitLabel() {
            return this.organisation ? 'Opslaan' : 'Organisatie aanmaken';
        },
    },

    methods: {
        submit() {
            this.form.submit(
                this.organisation ? update(this.organisation.id) : store(),
            );
        },
    },
};
</script>

<template>
    <form class="max-w-xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Naam / bedrijf</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autocomplete="organization"
            />
            <InputError :message="form.errors.name" />
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

        <div class="grid gap-2">
            <Label for="email">E-mailadres</Label>
            <Input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
            />
            <p class="text-sm text-muted-foreground">
                Hier wordt de factuur straks naartoe gestuurd.
            </p>
            <InputError :message="form.errors.email" />
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
