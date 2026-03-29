<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object,
    entries: Object,
    seasons: Array,
    teams: Array,
    fields: Array,
    filters: Object,
    userRole: String,
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const canSchedule = isManager || props.userRole === 'coach';

function fmt12(time24) {
    if (!time24) return '';
    const [h, m] = time24.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}

function fmtDate(dateStr) {
    if (!dateStr) return '';
    // Parse as local date to avoid timezone shift
    const [y, m, d] = dateStr.split('T')[0].split('-').map(Number);
    const date = new Date(y, m - 1, d);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

const filters = ref({
    season_id: props.filters.season_id || '',
    team_id: props.filters.team_id || '',
    field_id: props.filters.field_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    const params = {};
    Object.entries(filters.value).forEach(([k, v]) => {
        if (v) params[k] = v;
    });
    router.get(route('leagues.schedule.index', props.league.slug), params, { preserveState: true });
};

const cancelEntry = (entry) => {
    if (confirm('Delete this schedule entry?')) {
        router.delete(route('leagues.schedule.destroy', [props.league.slug, entry.id]));
    }
};

const statusBadge = (status) => {
    const map = {
        confirmed: 'bg-green-100 text-green-800',
        tentative: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`${league.name} - Schedule`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Schedule</h2>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link :href="route('leagues.schedule.calendar', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">
                                Calendar View
                            </Link>
                            <Link v-if="isManager" :href="route('leagues.schedule.bulk', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">
                                Bulk Schedule
                            </Link>
                            <Link v-if="canSchedule" :href="route('leagues.schedule.create', league.slug)">
                                <PrimaryButton>New Entry</PrimaryButton>
                            </Link>
                        </div>
                    </div>
<FlashMessage />

        <div class="mt-3">
            <div class="">
                <!-- Filters -->
                <div class="mb-6 overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
                        <select v-model="filters.season_id" @change="applyFilters" class="rounded-md border-gray-300 text-sm">
                            <option value="">All Seasons</option>
                            <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <select v-model="filters.team_id" @change="applyFilters" class="rounded-md border-gray-300 text-sm">
                            <option value="">All Teams</option>
                            <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <select v-model="filters.field_id" @change="applyFilters" class="rounded-md border-gray-300 text-sm">
                            <option value="">All Fields</option>
                            <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }} ({{ f.location?.name }})</option>
                        </select>
                        <input type="date" v-model="filters.date_from" @change="applyFilters" class="rounded-md border-gray-300 text-sm" placeholder="From" />
                        <input type="date" v-model="filters.date_to" @change="applyFilters" class="rounded-md border-gray-300 text-sm" placeholder="To" />
                    </div>
                </div>

                <!-- Entries -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div v-if="entries.data.length === 0" class="p-12 text-center text-gray-500">
                        No schedule entries found.
                    </div>

                    <!-- Mobile: card layout -->
                    <div v-else class="divide-y divide-gray-100 md:hidden">
                        <div v-for="entry in entries.data" :key="'m-' + entry.id" class="px-4 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span v-if="entry.team?.color_code" class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: entry.team.color_code }"></span>
                                    <span class="text-sm font-medium text-gray-900">{{ entry.team?.name }}</span>
                                </div>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusBadge(entry.status)">{{ entry.status }}</span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600">
                                {{ fmtDate(entry.date) }} &middot; {{ fmt12(entry.start_time) }} - {{ fmt12(entry.end_time) }}
                            </div>
                            <div class="mt-0.5 text-sm text-gray-400">
                                {{ entry.field?.name }}<span v-if="entry.field?.location"> @ {{ entry.field.location.name }}</span>
                                &middot; <span class="capitalize">{{ entry.type }}</span>
                            </div>
                            <div v-if="isManager" class="mt-2 flex gap-4">
                                <Link :href="route('leagues.schedule.edit', [league.slug, entry.id])" class="py-1 text-sm font-medium text-brand-600">Edit</Link>
                                <button v-if="entry.status !== 'cancelled'" @click="cancelEntry(entry)" class="py-1 text-sm font-medium text-red-600">Delete</button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop: table -->
                    <table v-if="entries.data.length" class="hidden md:table min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Team</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Field</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th v-if="isManager" class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="entry in entries.data" :key="entry.id">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ fmtDate(entry.date) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                    {{ fmt12(entry.start_time) }} - {{ fmt12(entry.end_time) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    <div class="flex items-center gap-1">
                                        <span v-if="entry.team?.color_code" class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: entry.team.color_code }"></span>
                                        {{ entry.team?.name }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                    {{ entry.field?.name }}
                                    <span v-if="entry.field?.location" class="text-gray-400"> @ {{ entry.field.location.name }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 capitalize">{{ entry.type }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5" :class="statusBadge(entry.status)">
                                        {{ entry.status }}
                                    </span>
                                </td>
                                <td v-if="isManager" class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <Link :href="route('leagues.schedule.edit', [league.slug, entry.id])" class="text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button v-if="entry.status !== 'cancelled'" @click="cancelEntry(entry)" class="ml-2 text-red-600 hover:text-red-900">Cancel</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="entries.links && entries.last_page > 1" class="mt-4 flex justify-center gap-1">
                    <Link
                        v-for="link in entries.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="flex items-center justify-center rounded px-3 py-2 text-sm min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 sm:py-1"
                        :class="link.active ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    />
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
