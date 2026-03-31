<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object,
    logs: Object,
    users: Array,
    filters: Object,
    userRole: String,
});

const actionFilter = ref(props.filters.action || '');
const userFilter = ref(props.filters.user_id || '');

function applyFilters() {
    const params = {};
    if (actionFilter.value) params.action = actionFilter.value;
    if (userFilter.value) params.user_id = userFilter.value;
    router.get(route('leagues.audit-log.index', props.league.slug), params, { preserveState: true });
}

function clearFilters() {
    actionFilter.value = '';
    userFilter.value = '';
    applyFilters();
}

function fmtDate(d) {
    if (!d) return '';
    const date = new Date(d);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function fmtType(type) {
    const raw = type?.replace('App\\Models\\', '') || '';
    const typeLabels = {
        ScheduleEntry: 'Schedule Entry',
        Field: 'Field',
        Team: 'Team',
        Division: 'Division',
        Location: 'Location',
        BlackoutRule: 'Blackout Rule',
        BookingWindow: 'Booking Window',
        Season: 'Season',
        LeagueInvitation: 'Invitation',
    };
    return typeLabels[raw] || raw.replace(/([A-Z])/g, ' $1').trim();
}

const actionColors = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <Head :title="`${league.name} - Audit Log`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Audit Log</h2>
        </div>

        <!-- Filters -->
        <div class="mt-2 flex items-center gap-2">
            <SearchSelect v-model="actionFilter" :options="[
                { value: 'created', label: 'Created' },
                { value: 'updated', label: 'Updated' },
                { value: 'deleted', label: 'Deleted' },
                { value: 'cancelled', label: 'Cancelled' },
            ]" placeholder="All actions" class="w-36" @update:model-value="applyFilters" />
            <SearchSelect v-model="userFilter" :options="users.map(u => ({ value: u.id, label: u.name }))" placeholder="All users" class="w-44" @update:model-value="applyFilters" />
            <button v-if="actionFilter || userFilter" @click="clearFilters" class="text-[10px] text-brand-600 hover:text-brand-700">Clear</button>
        </div>

        <!-- Log table -->
        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <div v-if="logs.data.length === 0" class="px-3 py-8 text-center text-xs text-gray-400">
                No audit log entries found.
            </div>

            <div v-else>
                <div class="grid grid-cols-[110px_60px_1fr_1fr] gap-1 px-3 py-1.5 text-[9px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                    <span>When</span>
                    <span>Action</span>
                    <span>What</span>
                    <span>Who</span>
                </div>

                <div v-for="log in logs.data" :key="log.id" class="grid grid-cols-[110px_60px_1fr_1fr] gap-1 items-start px-3 py-1.5 border-b border-gray-50 hover:bg-gray-50">
                    <span class="text-[10px] text-gray-500">{{ fmtDate(log.created_at) }}</span>

                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold inline-block w-fit" :class="actionColors[log.action] || 'bg-gray-100 text-gray-600'">
                        {{ log.action }}
                    </span>

                    <div class="min-w-0">
                        <span class="text-[11px] font-medium text-gray-900">{{ fmtType(log.auditable_type) }}</span>

                        <!-- Updated: show old → new for each changed field -->
                        <div v-if="log.action === 'updated' && log.old_values && log.new_values" class="mt-0.5 space-y-0.5">
                            <div v-for="(newVal, key) in (typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values)" :key="key" class="text-[10px] text-gray-500">
                                <strong>{{ key }}:</strong>
                                <span class="text-red-400 line-through">{{ (typeof log.old_values === 'string' ? JSON.parse(log.old_values) : log.old_values)[key] }}</span>
                                <span class="mx-0.5">&rarr;</span>
                                <span class="text-green-600">{{ newVal }}</span>
                            </div>
                        </div>

                        <!-- Created / deleted / cancelled: show values -->
                        <div v-else-if="log.new_values" class="mt-0.5 text-[10px] text-gray-500">
                            <span v-for="(val, key) in (typeof log.new_values === 'string' ? JSON.parse(log.new_values) : log.new_values)" :key="key" class="mr-2">
                                <strong>{{ key }}:</strong> {{ val }}
                            </span>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <span class="text-[11px] text-gray-700">{{ log.user?.name || 'System' }}</span>
                        <span class="text-[10px] text-gray-400 ml-1">{{ log.user?.email }}</span>
                        <div v-if="log.ip_address" class="text-[9px] text-gray-400">{{ log.ip_address }}</div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex justify-center gap-1 px-3 py-2 border-t border-gray-100">
                <Link v-for="link in logs.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                    class="rounded px-2 py-0.5 text-[10px]"
                    :class="link.active ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100'" />
            </div>
        </div>

        <p class="mt-2 text-[9px] text-gray-400">Showing {{ logs.data.length }} of {{ logs.total }} entries.</p>
    </LeagueLayout>
</template>
