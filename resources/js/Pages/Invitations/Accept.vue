<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    invitation: Object,
    league: Object,
    isLoggedIn: Boolean,
    emailMatch: Boolean,
});

const roleLabel = (role) => {
    const map = {
        division_manager: 'Division Manager',
        coach: 'Coach',
    };
    return map[role] || role;
};

const accept = () => {
    router.post(route('invitations.accept', props.invitation.token));
};
</script>

<template>
    <Head title="Accept Invitation" />

    <GuestLayout>
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900">You're Invited!</h2>
            <p class="mt-2 text-gray-600">
                You've been invited to join <strong>{{ league.name }}</strong> as a
                <strong>{{ roleLabel(invitation.role) }}</strong>.
            </p>
        </div>

        <div class="mt-6">
            <div v-if="isLoggedIn && emailMatch">
                <PrimaryButton @click="accept" class="w-full justify-center">
                    Accept Invitation
                </PrimaryButton>
            </div>

            <div v-else-if="isLoggedIn && !emailMatch" class="text-center text-sm text-gray-600">
                <p>This invitation was sent to <strong>{{ invitation.email }}</strong> but you're logged in with a different email.</p>
                <p class="mt-2">Please log out and log in with the correct email, or register a new account.</p>
            </div>

            <div v-else class="space-y-3">
                <Link :href="route('login')" class="block">
                    <PrimaryButton class="w-full justify-center">
                        Log In to Accept
                    </PrimaryButton>
                </Link>
                <Link :href="route('register')" class="block text-center text-sm text-brand-600 hover:text-brand-700">
                    Don't have an account? Register
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
