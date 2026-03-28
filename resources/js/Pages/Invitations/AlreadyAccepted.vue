<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ league: Object });
</script>

<template>
    <Head title="Invitation Already Accepted" />
    <GuestLayout>
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900">Already Accepted</h2>
            <p class="mt-2 text-gray-600">
                This invitation to <strong>{{ league.name }}</strong> has already been accepted.
            </p>
            <Link :href="route('leagues.show', league.slug)" class="mt-4 inline-block text-brand-600 hover:text-brand-700">
                Go to {{ league.name }}
            </Link>
        </div>
    </GuestLayout>
</template>
