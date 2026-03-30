<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({ league: Object });
const approved = ref(false);
let pollInterval = null;

onMounted(() => {
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch(route('checkout.status', props.league.slug));
            const data = await res.json();
            if (data.approved) {
                approved.value = true;
                clearInterval(pollInterval);
                setTimeout(() => {
                    router.visit(route('leagues.onboarding', props.league.slug));
                }, 1500);
            }
        } catch (e) {
            // Retry silently
        }
    }, 2000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <GuestLayout>
        <Head title="Setting Up Your League" />

        <div class="text-center">
            <div v-if="!approved" class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-100">
                <svg class="h-6 w-6 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <div v-else class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h2 class="text-lg font-semibold text-gray-900">
                {{ approved ? 'You\'re all set!' : 'Setting up your league...' }}
            </h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ approved ? 'Redirecting to league setup...' : 'This usually takes just a moment.' }}
            </p>
        </div>
    </GuestLayout>
</template>
