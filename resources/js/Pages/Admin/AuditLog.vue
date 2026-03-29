<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: Object,
    actions: Array,
    filters: Object,
});

const filterAction = ref(props.filters.action || '');

function applyFilter() {
    const params = {};
    if (filterAction.value) params.action = filterAction.value;
    router.get(route('admin.audit-log'), params, { preserveState: true });
}

function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

const actionLabel = (action) => ({
    login: 'Login',
    login_failed: 'Login Failed',
    registration: 'Registration',
    league_requested: 'League Requested',
    league_approved: 'League Approved',
    league_rejected: 'League Rejected',
}[action] || action);

const actionBadge = (action) => ({
    login: 'bg-green-100 text-green-700',
    login_failed: 'bg-red-100 text-red-700',
    registration: 'bg-blue-100 text-blue-700',
    league_requested: 'bg-amber-100 text-amber-700',
    league_approved: 'bg-green-100 text-green-700',
    league_rejected: 'bg-red-100 text-red-700',
}[action] || 'bg-gray-100 text-gray-600');
</script>

<template>
    <Head title="Admin - Audit Log" />

    <AdminLayout>
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Platform Audit Log</h2>
            <select v-model="filterAction" @change="applyFilter" class="w-40">
                <option value="">All Events</option>
                <option v-for="a in actions" :key="a" :value="a">{{ actionLabel(a) }}</option>
            </select>
        </div>

        <div class="mt-3 rounded-lg border border-gray-200 bg-white divide-y divide-gray-50">
            <div v-if="logs.data.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">No events found.</div>

            <div v-for="log in logs.data" :key="log.id" class="px-4 py-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-semibold" :class="actionBadge(log.action)">{{ actionLabel(log.action) }}</span>
                        <span v-if="log.user" class="text-xs text-gray-900">{{ log.user.name }}</span>
                        <span v-else class="text-xs text-gray-400">Unknown</span>
                    </div>
                    <span class="text-[10px] text-gray-400">{{ fmtDate(log.created_at) }}</span>
                </div>
                <div v-if="log.new_values" class="mt-0.5 text-[10px] text-gray-500">
                    <span v-if="log.new_values.email">{{ log.new_values.email }}</span>
                    <span v-if="log.new_values.method"> via {{ log.new_values.method }}</span>
                    <span v-if="log.new_values.reason"> ({{ log.new_values.reason }})</span>
                    <span v-if="log.new_values.league"> &middot; {{ log.new_values.league }}</span>
                </div>
                <div v-if="log.ip_address" class="text-[9px] text-gray-300">{{ log.ip_address }}</div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="logs.last_page > 1" class="mt-3 flex justify-center gap-1">
            <Link v-for="link in logs.links" :key="link.label"
                :href="link.url || '#'" v-html="link.label"
                class="rounded px-3 py-1 text-sm"
                :class="link.active ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
        </div>
    </AdminLayout>
</template>
