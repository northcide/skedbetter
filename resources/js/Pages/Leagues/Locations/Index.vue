<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BulkAddModal from '@/Components/BulkAddModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object,
    locations: Array,
    userRole: String,
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const expanded = ref({});
const showBulkAdd = ref(false);

const bulkLocationFields = [
    { key: 'name', label: 'Name', required: true, placeholder: 'Location name' },
    { key: 'address', label: 'Address', required: false, placeholder: 'Address' },
    { key: 'city', label: 'City', required: false, placeholder: 'City' },
    { key: 'state', label: 'ST', required: false, placeholder: 'ST' },
];

const submitBulkLocations = (rows, done) => {
    axios.post(route('leagues.locations.bulk', props.league.slug), { locations: rows })
        .then(() => { showBulkAdd.value = false; router.reload(); })
        .catch(() => {})
        .finally(() => done());
};

const toggle = (id) => {
    expanded.value[id] = !expanded.value[id];
};


const deleteLocation = (location) => {
    if (confirm(`Delete "${location.name}"? This will also delete all fields at this location.`)) {
        router.delete(route('leagues.locations.destroy', [props.league.slug, location.id]));
    }
};

const deleteField = (field) => {
    if (confirm(`Delete field "${field.name}"?`)) {
        router.delete(route('leagues.fields.destroy', [props.league.slug, field.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Locations & Fields`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Locations & Fields</h2>
            <div v-if="isManager" class="flex items-center gap-2">
                <button @click="showBulkAdd = true" class="text-[10px] text-brand-600 hover:text-brand-700">Bulk Add</button>
                <Link :href="route('leagues.locations.create', league.slug)">
                    <PrimaryButton>Add Location</PrimaryButton>
                </Link>
            </div>
        </div>

        <FlashMessage />

        <div v-if="locations.length === 0" class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-sm text-gray-500">No locations yet. Add your first location to start managing fields.</p>
        </div>

        <div v-else class="mt-6 space-y-4">
            <div v-for="location in locations" :key="location.id" class="rounded-xl border border-gray-200 bg-white">
                <!-- Location Header -->
                <div class="flex items-center justify-between px-3 py-2">
                    <button @click="toggle(location.id)" class="flex items-center gap-3 text-left">
                        <svg
                            class="h-4 w-4 text-gray-400 transition-transform"
                            :class="{ 'rotate-90': expanded[location.id] }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ location.name }}</h3>
                            <p v-if="location.address" class="text-xs text-gray-500">
                                {{ location.address }}{{ location.city ? `, ${location.city}` : '' }}{{ location.state ? ` ${location.state}` : '' }} {{ location.zip }}
                            </p>
                        </div>
                    </button>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ location.fields?.length || 0 }} field{{ (location.fields?.length || 0) !== 1 ? 's' : '' }}
                        </span>
                        <div v-if="isManager" class="flex items-center gap-2">
                            <Link :href="route('leagues.locations.edit', [league.slug, location.id])" class="text-xs text-brand-600 hover:text-brand-700">Edit</Link>
                            <button @click="deleteLocation(location)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    </div>
                </div>

                <!-- Fields (expandable) -->
                <div v-if="expanded[location.id]" class="border-t border-gray-100">
                    <div v-if="!location.fields || location.fields.length === 0" class="px-5 py-6 text-center text-sm text-gray-400">
                        No fields at this location.
                        <Link v-if="isManager" :href="route('leagues.locations.fields.create', [league.slug, location.id])" class="ml-1 text-brand-600 hover:text-brand-700">Add one</Link>
                    </div>

                    <div v-else>
                        <div
                            v-for="field in location.fields"
                            :key="field.id"
                            class="flex items-center justify-between border-t border-gray-50 px-3 py-2 first:border-t-0"
                        >
                            <div class="flex items-center gap-3 pl-7">
                                <!-- Field icon -->
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="field.is_active ? 'bg-field-100 text-field-600' : 'bg-gray-100 text-gray-400'">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" />
                                        <line x1="12" y1="3" x2="12" y2="21" />
                                        <line x1="3" y1="12" x2="21" y2="12" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ field.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span v-if="field.surface_type" class="text-xs text-gray-400">{{ field.surface_type }}</span>
                                        <span v-if="field.is_lighted" class="text-xs text-amber-500">Lighted</span>
                                        <span v-if="!field.is_active" class="text-xs text-red-400">Inactive</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                <template v-if="field.allowed_divisions && field.allowed_divisions.length > 0">
                                    <span v-for="d in field.allowed_divisions" :key="d.id"
                                        class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">{{ d.name }}</span>
                                </template>
                                <span v-else class="rounded bg-green-50 px-1.5 py-0.5 text-[10px] font-medium text-green-700">All divisions</span>

                                <div v-if="isManager" class="flex items-center gap-2 ml-1">
                                    <Link :href="route('leagues.fields.edit', [league.slug, field.id])" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="deleteField(field)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Field button -->
                    <div v-if="isManager" class="border-t border-gray-100 px-3 py-2">
                        <Link :href="route('leagues.locations.fields.create', [league.slug, location.id])" class="flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Field
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <BulkAddModal :show="showBulkAdd" title="Bulk Add Locations" :fields="bulkLocationFields" @close="showBulkAdd = false" @submit="submitBulkLocations" />
    </LeagueLayout>
</template>
