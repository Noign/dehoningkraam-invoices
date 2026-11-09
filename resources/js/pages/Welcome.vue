<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
</script>

<template>
    <Head title="Welkom" />

    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b border-border bg-background">
            <div
                class="mx-auto flex h-20 w-full max-w-7xl items-center gap-3 px-6"
            >
                <AppLogoIcon class="size-9" />
                <span class="font-heading text-xl font-semibold">
                    {{ $page.props.name }}
                </span>
            </div>
        </header>

        <main
            class="flex flex-1 items-center justify-center stripes-dark px-6 py-20 text-center"
        >
            <div class="flex max-w-2xl flex-col items-center">
                <p
                    class="mb-4 text-sm font-semibold tracking-widest text-gold uppercase"
                >
                    Facturatie
                </p>
                <h1
                    class="mb-6 text-4xl leading-tight font-semibold text-cream md:text-6xl"
                >
                    Facturen, overzichtelijk geregeld.
                </h1>
                <p class="mb-8 text-lg text-on-dark">
                    Maak en beheer hier de facturen van De honingkraam.
                </p>
                <Button as-child size="lg" class="font-semibold">
                    <Link :href="$page.props.auth.user ? dashboard() : login()">
                        {{
                            $page.props.auth.user
                                ? 'Naar het dashboard'
                                : 'Inloggen'
                        }}
                    </Link>
                </Button>
            </div>
        </main>
    </div>
</template>
