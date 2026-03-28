<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object, teams: Array, userRole: String });
const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);

const deleteTeam = (team) => {
    if (confirm(`Delete team "${team.name}"?`)) {
        router.delete(route('leagues.teams.destroy', [props.league.slug, team.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Teams`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Teams</h2>
                        </div>
                        <div v-if="isManager" class="flex items-center gap-3">
                            <Link :href="route('leagues.teams.import', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Import CSV</Link>
                            <Link :href="route('leagues.teams.create', league.slug)">
                                <PrimaryButton>Add Team</PrimaryButton>
                            </Link>
                        </div>
                    </div>
<FlashMessage />

        <div class="mt-4">
            <div class="">
                <div v-if="teams.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No teams yet. Create divisions first, then add teams.</p>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Team</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Division</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contact</th>
                                <th v-if="isManager" class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="team in teams" :key="team.id">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span v-if="team.color_code" class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: team.color_code }"></span>
                                        <Link :href="route('leagues.teams.show', [league.slug, team.id])" class="font-medium text-brand-600 hover:text-brand-700">
                                            {{ team.name }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ team.division?.name }}
                                    <span v-if="team.division?.season" class="text-gray-400"> ({{ team.division.season.name }})</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ team.contact_name || '&mdash;' }}</td>
                                <td v-if="isManager" class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link :href="route('leagues.teams.edit', [league.slug, team.id])" class="text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="deleteTeam(team)" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
