<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object,
    teams: Array,
    divisions: { type: Array, default: () => [] },
    userRole: String,
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const filterDiv = ref('');
const saving = ref({});
const saved = ref({});

const filteredTeams = computed(() => {
    if (filterDiv.value) return props.teams.filter(t => t.division_id == filterDiv.value);
    return props.teams;
});

// Editable state — clone team data
const edits = ref({});
props.teams.forEach(t => {
    edits.value[t.id] = {
        name: t.name,
        contact_name: t.contact_name || '',
        contact_email: t.contact_email || '',
        color_code: t.color_code || '',
    };
});

function saveTeam(team) {
    saving.value[team.id] = true;
    axios.put(route('leagues.teams.update', [props.league.slug, team.id]), {
        name: edits.value[team.id].name,
        contact_name: edits.value[team.id].contact_name,
        contact_email: edits.value[team.id].contact_email,
        color_code: edits.value[team.id].color_code,
        division_id: team.division_id,
    }).then(() => {
        saved.value[team.id] = true;
        setTimeout(() => { saved.value[team.id] = false; }, 1500);
    }).finally(() => {
        saving.value[team.id] = false;
    });
}

function deleteTeam(team) {
    if (confirm(`Delete "${team.name}"?`)) {
        axios.delete(route('leagues.teams.destroy', [props.league.slug, team.id]))
            .then(() => router.reload({ only: ['teams'] }));
    }
}

function isDirty(team) {
    const e = edits.value[team.id];
    return e.name !== team.name
        || e.contact_name !== (team.contact_name || '')
        || e.contact_email !== (team.contact_email || '')
        || e.color_code !== (team.color_code || '');
}
</script>

<template>
    <Head :title="`${league.name} - Team Roster`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Team Roster</h2>
            <select v-model="filterDiv" class="w-40">
                <option value="">All Divisions</option>
                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
        </div>

        <FlashMessage />

        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <!-- Header -->
            <div class="grid grid-cols-[40px_1fr_1fr_1fr_30px_60px] gap-1 px-3 py-1.5 text-[9px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                <span></span>
                <span>Team Name</span>
                <span>Coach Name</span>
                <span>Coach Email</span>
                <span></span>
                <span></span>
            </div>

            <!-- Team rows -->
            <div v-for="team in filteredTeams" :key="team.id" class="grid grid-cols-[40px_1fr_1fr_1fr_30px_60px] gap-1 items-center px-3 py-1 border-b border-gray-50 hover:bg-gray-50">
                <!-- Color -->
                <div class="flex justify-center">
                    <input type="color" v-model="edits[team.id].color_code" class="h-5 w-5 cursor-pointer rounded border-0 p-0" :disabled="!isManager" />
                </div>

                <!-- Name -->
                <input v-model="edits[team.id].name" :disabled="!isManager"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-900 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <!-- Coach Name -->
                <input v-model="edits[team.id].contact_name" :disabled="!isManager" placeholder="—"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <!-- Coach Email -->
                <input v-model="edits[team.id].contact_email" type="email" :disabled="!isManager" placeholder="—"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <!-- Division badge -->
                <span class="text-[9px] text-gray-400 truncate" :title="team.division?.name">{{ team.division?.name?.slice(0, 4) }}</span>

                <!-- Actions -->
                <div class="flex items-center gap-1 justify-end" v-if="isManager">
                    <button v-if="isDirty(team)" @click="saveTeam(team)" :disabled="saving[team.id]"
                        class="rounded bg-brand-600 px-1.5 py-0.5 text-[9px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
                        {{ saving[team.id] ? '...' : 'Save' }}
                    </button>
                    <span v-if="saved[team.id]" class="text-[9px] text-green-600">Saved</span>
                    <button @click="deleteTeam(team)" class="text-[9px] text-red-400 hover:text-red-600">Del</button>
                </div>
            </div>

            <div v-if="filteredTeams.length === 0" class="px-3 py-6 text-center text-xs text-gray-400">
                No teams{{ filterDiv ? ' in this division' : '' }}.
            </div>
        </div>

        <p class="mt-2 text-[10px] text-gray-400">Click any field to edit. Changes save per row. {{ filteredTeams.length }} team(s) shown.</p>
    </LeagueLayout>
</template>
