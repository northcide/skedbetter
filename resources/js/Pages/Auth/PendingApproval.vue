<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="Pending Approval" />

        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h2 class="text-lg font-semibold text-gray-900">Account Pending</h2>

            <p class="mt-2 text-sm text-gray-600">
                Your account is pending activation. Once a league you're associated with is approved,
                or an administrator activates your account, you'll be able to sign in and use the platform.
            </p>

            <div class="mt-6">
                <Link :href="route('logout')" method="post" as="button"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700">
                    Sign Out
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
