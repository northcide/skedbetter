<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <Link href="/" class="inline-flex flex-col items-center gap-3">
                    <ApplicationLogo class="h-12 w-12 text-brand-300" />
                    <span class="text-2xl font-bold tracking-tight text-white">SkedBetter</span>
                </Link>
                <p class="mt-2 text-sm text-brand-300/70">Field scheduling made simple</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-black/5">
                <slot />
            </div>
        </div>
    </div>
</template>
