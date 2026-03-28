<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ notifications: Array });

const markAllRead = () => {
    router.post(route('notifications.mark-all-read'));
};

const markRead = (id) => {
    router.patch(route('notifications.mark-read', id));
};
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifications</h2>
                <PrimaryButton @click="markAllRead" v-if="notifications.some(n => !n.read)">
                    Mark All Read
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div v-if="notifications.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No notifications yet.</p>
                </div>

                <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="n in notifications"
                            :key="n.id"
                            class="flex items-center justify-between px-6 py-4"
                            :class="n.read ? 'bg-white' : 'bg-blue-50'"
                        >
                            <div>
                                <p class="text-sm text-gray-900" :class="{ 'font-medium': !n.read }">
                                    {{ n.message }}
                                </p>
                                <p class="text-xs text-gray-500">{{ n.created_at }}</p>
                            </div>
                            <button
                                v-if="!n.read"
                                @click="markRead(n.id)"
                                class="text-xs text-brand-600 hover:text-brand-700"
                            >
                                Mark read
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
