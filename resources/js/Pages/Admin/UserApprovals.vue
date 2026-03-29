<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pending: Array,
    recentlyApproved: Array,
});

const processing = ref({});

function approve(user) {
    processing.value[user.id] = 'approving';
    router.post(route('admin.approvals.approve', user.id), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value[user.id] = null; },
    });
}

function reject(user) {
    if (!confirm(`Reject and delete registration for "${user.name}" (${user.email})?`)) return;
    processing.value[user.id] = 'rejecting';
    router.delete(route('admin.approvals.reject', user.id), {
        preserveScroll: true,
        onFinish: () => { processing.value[user.id] = null; },
    });
}

function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}
</script>

<template>
    <Head title="User Approvals" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-900">User Approvals</h2>
        </template>

        <div class="mx-auto max-w-3xl px-3 py-4 sm:px-4 lg:px-6">
            <FlashMessage />

            <!-- Pending -->
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Pending Approval</h3>
                    <p class="text-xs text-gray-500">These users registered and are waiting for access.</p>
                </div>

                <div v-if="pending.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                    No pending registrations.
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <div v-for="user in pending" :key="user.id" class="flex items-center justify-between px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                            <p class="text-[10px] text-gray-400">Registered {{ fmtDate(user.created_at) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="reject(user)" :disabled="processing[user.id]"
                                class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50">
                                {{ processing[user.id] === 'rejecting' ? '...' : 'Reject' }}
                            </button>
                            <PrimaryButton @click="approve(user)" :disabled="processing[user.id]" size="sm">
                                {{ processing[user.id] === 'approving' ? '...' : 'Approve' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recently Approved -->
            <div v-if="recentlyApproved.length" class="mt-4 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Recently Approved</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="user in recentlyApproved" :key="user.id" class="flex items-center justify-between px-4 py-2">
                        <div>
                            <p class="text-sm text-gray-900">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                        <span class="text-[10px] text-gray-400">Approved {{ fmtDate(user.approved_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
