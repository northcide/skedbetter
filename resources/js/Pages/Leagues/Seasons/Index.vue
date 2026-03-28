<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object, seasons: Array, userRole: String });
const isManager = ['superadmin', 'league_manager'].includes(props.userRole);

const deleteSeason = (season) => {
    if (confirm(`Delete season "${season.name}"? This will delete all divisions and schedules in this season.`)) {
        router.delete(route('leagues.seasons.destroy', [props.league.slug, season.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Seasons`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Seasons</h2>
                        </div>
                        <Link v-if="isManager" :href="route('leagues.seasons.create', league.slug)">
                            <PrimaryButton>Add Season</PrimaryButton>
                        </Link>
                    </div>
<FlashMessage />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="seasons.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No seasons yet. Create your first season to start organizing divisions and teams.</p>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Divisions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th v-if="isManager" class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="season in seasons" :key="season.id">
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">{{ season.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ new Date(season.start_date).toLocaleDateString() }} &ndash; {{ new Date(season.end_date).toLocaleDateString() }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ season.divisions_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span v-if="season.is_current" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Current</span>
                                </td>
                                <td v-if="isManager" class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('leagues.seasons.edit', [league.slug, season.id])" class="text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="deleteSeason(season)" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
