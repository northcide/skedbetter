<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ league: Object });
</script>

<template>
    <Head title="Invitation Expired" />
    <GuestLayout>
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900">Invitation Expired</h2>
            <p class="mt-2 text-gray-600">
                This invitation to join <strong>{{ league.name }}</strong> has expired.
                Please contact the league manager for a new invitation.
            </p>
        </div>
    </GuestLayout>
</template>
