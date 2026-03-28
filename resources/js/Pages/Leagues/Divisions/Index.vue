<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import BulkAddModal from '@/Components/BulkAddModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ league: Object, divisions: Array, userRole: String });
const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);

const expanded = ref({});
const toggle = (id) => { expanded.value[id] = !expanded.value[id]; };

// Inline team add
const newTeamName = ref({});
const addTeam = (divId) => {
    const name = newTeamName.value[divId];
    if (!name) return;
    axios.post(route('leagues.teams.store', props.league.slug), {
        name, division_id: divId,
    }).then(() => {
        newTeamName.value[divId] = '';
        router.reload({ only: ['divisions'] });
    });
};

// Inline team edit
const editingTeamId = ref(null);
const editTeamName = ref('');
const startEditTeam = (team) => { editingTeamId.value = team.id; editTeamName.value = team.name; };
const cancelEditTeam = () => { editingTeamId.value = null; };
const saveTeam = (team) => {
    axios.put(route('leagues.teams.update', [props.league.slug, team.id]), {
        name: editTeamName.value, division_id: team.division_id,
    }).then(() => {
        editingTeamId.value = null;
        router.reload({ only: ['divisions'] });
    });
};

const deleteTeam = (team) => {
    if (confirm(`Delete team "${team.name}"?`)) {
        axios.delete(route('leagues.teams.destroy', [props.league.slug, team.id]))
            .then(() => router.reload({ only: ['divisions'] }));
    }
};

const deleteDivision = (div) => {
    if (confirm(`Delete "${div.name}" and all its teams?`)) {
        router.delete(route('leagues.divisions.destroy', [props.league.slug, div.id]));
    }
};

// Bulk add divisions
const showBulkDivisions = ref(false);
const bulkDivFields = [
    { key: 'name', label: 'Division Name', required: true, placeholder: 'e.g. U10 Boys' },
    { key: 'age_group', label: 'Age Group', required: false, placeholder: 'e.g. Under 10' },
];
const submitBulkDivisions = (rows, done) => {
    axios.post(route('leagues.divisions.bulk', props.league.slug), { divisions: rows })
        .then(() => { showBulkDivisions.value = false; router.reload({ only: ['divisions'] }); })
        .catch(() => {})
        .finally(() => done());
};

// Bulk add teams (per division)
const showBulkTeams = ref(false);
const bulkTeamDivId = ref(null);
const bulkTeamFields = [
    { key: 'name', label: 'Team Name', required: true, placeholder: 'Team name' },
    { key: 'contact_name', label: 'Coach Name', required: false, placeholder: 'Coach name' },
    { key: 'contact_email', label: 'Coach Email', required: false, placeholder: 'email@example.com', type: 'email' },
];
const openBulkTeams = (divId) => { bulkTeamDivId.value = divId; showBulkTeams.value = true; };
const submitBulkTeams = (rows, done) => {
    axios.post(route('leagues.teams.bulk', props.league.slug), { division_id: bulkTeamDivId.value, teams: rows })
        .then(() => { showBulkTeams.value = false; router.reload({ only: ['divisions'] }); })
        .catch(() => {})
        .finally(() => done());
};
</script>

<template>
    <Head :title="`${league.name} - Divisions & Teams`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Divisions & Teams</h2>
            <div v-if="isManager" class="flex items-center gap-2">
                <button @click="showBulkDivisions = true" class="text-[10px] text-brand-600 hover:text-brand-700">Bulk Add</button>
                <Link :href="route('leagues.divisions.create', league.slug)">
                    <PrimaryButton>Add Division</PrimaryButton>
                </Link>
            </div>
        </div>

        <FlashMessage />

        <div v-if="divisions.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
            <p class="text-sm text-gray-500">No divisions yet. Create a season first, then add divisions.</p>
        </div>

        <div v-else class="mt-3 rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
            <div v-for="div in divisions" :key="div.id">
                <!-- Division row -->
                <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 cursor-pointer" @click="toggle(div.id)">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="h-3 w-3 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-90': expanded[div.id] }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-xs font-medium text-gray-900">{{ div.name }}</span>
                        <span v-if="div.age_group" class="text-[10px] text-gray-400">{{ div.age_group }}</span>
                        <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500">{{ div.teams_count }}</span>
                        <span v-if="div.max_event_minutes" class="text-[10px] text-gray-400">{{ div.max_event_minutes }}min</span>
                        <span v-if="div.max_weekly_events_per_team" class="text-[10px] text-gray-400">{{ div.max_weekly_events_per_team }}/wk</span>
                        <span v-if="div.managers && div.managers.length" class="text-[10px] text-purple-500">Mgr: {{ div.managers.map(m => m.name).join(', ') }}</span>
                    </div>
                    <div v-if="isManager" class="flex items-center gap-2" @click.stop>
                        <Link :href="route('leagues.divisions.edit', [league.slug, div.id])" class="text-[10px] text-brand-600 hover:text-brand-700">Settings</Link>
                        <button @click="deleteDivision(div)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </div>

                <!-- Teams (expanded) -->
                <div v-if="expanded[div.id]" class="bg-gray-50">
                    <div v-if="div.teams && div.teams.length > 0">
                        <div v-for="team in div.teams" :key="team.id" class="flex items-center justify-between border-t border-gray-100 px-3 py-1.5">
                            <template v-if="editingTeamId !== team.id">
                                <div class="flex items-center gap-1.5 pl-5">
                                    <span v-if="team.color_code" class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: team.color_code }"></span>
                                    <Link :href="route('leagues.teams.show', [league.slug, team.id])" class="text-xs text-gray-700 hover:text-brand-600">{{ team.name }}</Link>
                                    <span v-if="team.users && team.users.length" class="text-[10px] text-gray-400">{{ team.users.map(u => u.name).join(', ') }}</span>
                                    <span v-else-if="team.contact_name" class="text-[10px] text-gray-400">{{ team.contact_name }}</span>
                                </div>
                                <div v-if="isManager" class="flex items-center gap-1.5" @click.stop>
                                    <Link :href="route('leagues.teams.edit', [league.slug, team.id])" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="startEditTeam(team)" class="text-[10px] text-gray-500 hover:text-gray-700">Rename</button>
                                    <button @click="deleteTeam(team)" class="text-[10px] text-red-500 hover:text-red-700">Del</button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex flex-1 items-center gap-1.5 pl-5" @click.stop>
                                    <TextInput v-model="editTeamName" class="flex-1 py-1 text-xs" @keyup.enter="saveTeam(team)" />
                                    <button @click="saveTeam(team)" class="rounded bg-brand-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Save</button>
                                    <button @click="cancelEditTeam" class="text-[10px] text-gray-500">Cancel</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div v-else class="border-t border-gray-100 px-3 py-2 pl-8 text-[11px] text-gray-400">No teams yet.</div>

                    <div v-if="isManager" class="border-t border-gray-100 px-3 py-1.5">
                        <div class="flex items-center gap-1.5 pl-5" @click.stop>
                            <TextInput v-model="newTeamName[div.id]" class="flex-1 py-1 text-xs" placeholder="Add team..." @keyup.enter="addTeam(div.id)" />
                            <button @click="addTeam(div.id)" :disabled="!newTeamName[div.id]" class="rounded bg-brand-600 px-1.5 py-0.5 text-[10px] font-semibold text-white disabled:opacity-50">Add</button>
                            <button @click.stop="openBulkTeams(div.id)" class="text-[10px] text-brand-600 hover:text-brand-700">Bulk</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <BulkAddModal :show="showBulkDivisions" title="Bulk Add Divisions" :fields="bulkDivFields" @close="showBulkDivisions = false" @submit="submitBulkDivisions" />
        <BulkAddModal :show="showBulkTeams" title="Bulk Add Teams" :fields="bulkTeamFields" @close="showBulkTeams = false" @submit="submitBulkTeams" />
    </LeagueLayout>
</template>
