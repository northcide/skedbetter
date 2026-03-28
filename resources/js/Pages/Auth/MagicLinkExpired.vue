<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ reason: String });

const messages = {
    invalid: 'This login link is invalid.',
    expired: 'This login link has expired.',
    no_account: 'No account found for this email.',
};
</script>

<template>
    <Head title="Link Expired" />
    <GuestLayout>
        <div class="text-center">
            <h2 class="text-xl font-bold text-gray-900">Link Not Valid</h2>
            <p class="mt-2 text-sm text-gray-500">{{ messages[reason] || 'This link is no longer valid.' }}</p>
            <p class="mt-1 text-sm text-gray-500">Request a new one from the login page.</p>
            <Link :href="route('login')" class="mt-4 inline-block">
                <PrimaryButton>Back to Login</PrimaryButton>
            </Link>
        </div>
    </GuestLayout>
</template>
