<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object, divisions: Array, userRole: String });
const isManager = ['superadmin', 'league_manager'].includes(props.userRole);

const deleteDivision = (division) => {
    if (confirm(`Delete division "${division.name}"? This will delete all teams in this division.`)) {
        router.delete(route('leagues.divisions.destroy', [props.league.slug, division.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Divisions`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Divisions</h2>
                        </div>
                        <Link v-if="isManager" :href="route('leagues.divisions.create', league.slug)">
                            <PrimaryButton>Add Division</PrimaryButton>
                        </Link>
                    </div>
<FlashMessage />

        <div class="mt-4">
            <div class="">
                <div v-if="divisions.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No divisions yet. Create a season first, then add divisions.</p>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Season</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Age Group</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Teams</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Max Duration</th>
                                <th v-if="isManager" class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="division in divisions" :key="division.id">
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">{{ division.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ division.season?.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ division.age_group || '&mdash;' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ division.teams_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span v-if="division.max_event_minutes">{{ division.max_event_minutes }} min</span>
                                    <span v-else class="text-gray-300">No limit</span>
                                </td>
                                <td v-if="isManager" class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('leagues.divisions.edit', [league.slug, division.id])" class="text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="deleteDivision(division)" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
