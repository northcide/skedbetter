<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object });

const retry = () => {
    router.visit(route('checkout.retry', props.league.slug));
};
</script>

<template>
    <GuestLayout>
        <Head title="Payment Not Completed" />

        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h2 class="text-lg font-semibold text-gray-900">Payment Not Completed</h2>
            <p class="mt-2 text-sm text-gray-500">
                No worries — your account has been created. You can complete payment whenever you're ready.
            </p>

            <div class="mt-6 space-y-3">
                <PrimaryButton class="w-full justify-center" @click="retry">
                    Complete Payment
                </PrimaryButton>
                <Link :href="route('logout')" method="post" as="button"
                    class="block w-full text-center text-sm text-gray-500 hover:text-gray-700">
                    Sign Out
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
