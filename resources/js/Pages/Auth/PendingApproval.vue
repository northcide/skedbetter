<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
const emailVerified = !!user?.email_verified_at;
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

            <div v-if="!emailVerified" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-left">
                <p class="text-sm font-medium text-amber-800">Please verify your email</p>
                <p class="mt-1 text-xs text-amber-600">
                    We sent a verification link to <strong>{{ user?.email }}</strong>.
                    Click the link in that email to verify your address. Once verified, an administrator will be able to activate your account.
                </p>
            </div>

            <div v-else class="mt-3">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Email verified
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Your email has been verified. An administrator will activate your account shortly.
                </p>
            </div>

            <div class="mt-6">
                <Link :href="route('logout')" method="post" as="button"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700">
                    Sign Out
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
