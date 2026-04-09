<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    reason: String,
    email: { type: String, default: null },
});

const messages = {
    invalid: 'This login link is invalid.',
    expired: 'This login link has expired.',
    no_account: 'No account found for this email.',
};

const resendForm = useForm({ email: props.email });

const resend = () => {
    resendForm.post(route('auth.magic-link.request'));
};
</script>

<template>
    <Head title="Link Expired" />
    <GuestLayout>
        <div class="text-center">
            <h2 class="text-xl font-bold text-gray-900">Link Not Valid</h2>
            <p class="mt-2 text-sm text-gray-500">{{ messages[reason] || 'This link is no longer valid.' }}</p>

            <FlashMessage />

            <template v-if="email && reason === 'expired'">
                <p class="mt-3 text-sm text-gray-500">Want a new login link?</p>
                <button
                    @click="resend"
                    :disabled="resendForm.processing || resendForm.wasSuccessful"
                    class="mt-3 inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                >
                    {{ resendForm.wasSuccessful ? 'Link Sent!' : resendForm.processing ? 'Sending...' : 'Send New Link' }}
                </button>
            </template>

            <div class="mt-4">
                <Link :href="route('login')" class="inline-block">
                    <SecondaryButton>Back to Login</SecondaryButton>
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
