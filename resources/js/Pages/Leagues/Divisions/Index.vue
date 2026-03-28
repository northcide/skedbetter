<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ league: Object, divisions: Array, userRole: String });
const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);

const expanded = ref({});
props.divisions.forEach(d => { expanded.value[d.id] = true; });
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
</script>

<template>
    <Head :title="`${league.name} - Divisions & Teams`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Divisions & Teams</h2>
            <Link v-if="isManager" :href="route('leagues.divisions.create', league.slug)">
                <PrimaryButton>Add Division</PrimaryButton>
            </Link>
        </div>

        <FlashMessage />

        <div v-if="divisions.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
            <p class="text-sm text-gray-500">No divisions yet. Create a season first, then add divisions.</p>
        </div>

        <div v-else class="mt-4 space-y-3">
            <div v-for="div in divisions" :key="div.id" class="rounded-lg border border-gray-200 bg-white">
                <!-- Division Header -->
                <div class="flex items-center justify-between px-4 py-3">
                    <button @click="toggle(div.id)" class="flex items-center gap-2 text-left">
                        <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-90': expanded[div.id] }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ div.name }}</h3>
                            <div class="flex items-center gap-3 text-[11px] text-gray-400">
                                <span v-if="div.age_group">{{ div.age_group }}</span>
                                <span>{{ div.teams_count }} team(s)</span>
                                <span v-if="div.max_event_minutes">Max {{ div.max_event_minutes }}min</span>
                                <span v-if="div.max_weekly_events_per_team">{{ div.max_weekly_events_per_team }}/wk</span>
                            </div>
                        </div>
                    </button>
                    <div v-if="isManager" class="flex items-center gap-2">
                        <Link :href="route('leagues.divisions.edit', [league.slug, div.id])" class="text-[11px] text-brand-600 hover:text-brand-700">Settings</Link>
                        <button @click="deleteDivision(div)" class="text-[11px] text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </div>

                <!-- Teams -->
                <div v-if="expanded[div.id]" class="border-t border-gray-100">
                    <div v-if="div.teams && div.teams.length > 0">
                        <div v-for="team in div.teams" :key="team.id" class="flex items-center justify-between border-t border-gray-50 px-4 py-2 first:border-t-0">
                            <template v-if="editingTeamId !== team.id">
                                <div class="flex items-center gap-2 pl-6">
                                    <span v-if="team.color_code" class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: team.color_code }"></span>
                                    <Link :href="route('leagues.teams.show', [league.slug, team.id])" class="text-sm text-gray-900 hover:text-brand-600">{{ team.name }}</Link>
                                    <span v-if="team.contact_name" class="text-[10px] text-gray-400">{{ team.contact_name }}</span>
                                </div>
                                <div v-if="isManager" class="flex items-center gap-2">
                                    <button @click="startEditTeam(team)" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</button>
                                    <button @click="deleteTeam(team)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex flex-1 items-center gap-2 pl-6">
                                    <TextInput v-model="editTeamName" class="flex-1" @keyup.enter="saveTeam(team)" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="saveTeam(team)" class="rounded bg-brand-600 px-2 py-0.5 text-[10px] font-semibold text-white">Save</button>
                                    <button @click="cancelEditTeam" class="text-[10px] text-gray-500">Cancel</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div v-else class="px-4 py-3 pl-10 text-xs text-gray-400">No teams yet.</div>

                    <!-- Add team -->
                    <div v-if="isManager" class="border-t border-gray-100 px-4 py-2">
                        <div class="flex items-center gap-2 pl-6">
                            <TextInput v-model="newTeamName[div.id]" class="flex-1" placeholder="Add team..." @keyup.enter="addTeam(div.id)" />
                            <button @click="addTeam(div.id)" :disabled="!newTeamName[div.id]" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white disabled:opacity-50">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
