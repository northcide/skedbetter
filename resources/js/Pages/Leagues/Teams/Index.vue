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
const colorPickerOpen = ref(null);
const sendInvite = ref({});
const inviting = ref({});
const invited = ref({});

const presetColors = [
    // Row 1: Reds & Oranges
    '#CC0000', '#8B0000', '#DC143C', '#FF4500', '#FF6600',
    // Row 2: Yellows & Golds
    '#FFD700', '#DAA520', '#FFA500', '#F4A460', '#CD853F',
    // Row 3: Greens
    '#006400', '#228B22', '#2E8B57', '#32CD32', '#00A550',
    // Row 4: Blues
    '#000080', '#00308F', '#0057B7', '#1E90FF', '#4169E1',
    // Row 5: Purples & Pinks
    '#4B0082', '#6A0DAD', '#800080', '#C71585', '#FF1493',
    // Row 6: Neutrals & Classics
    '#000000', '#333333', '#708090', '#C0C0C0', '#FFFFFF',
];

const filteredTeams = computed(() => {
    if (filterDiv.value) return props.teams.filter(t => t.division_id == filterDiv.value);
    return props.teams;
});

const edits = ref({});
props.teams.forEach(t => {
    edits.value[t.id] = {
        name: t.name,
        contact_name: t.contact_name || '',
        contact_email: t.contact_email || '',
        color_code: t.color_code || '',
    };
});

function pickColor(teamId, color) {
    edits.value[teamId].color_code = color;
    colorPickerOpen.value = null;
}

function clearColor(teamId) {
    edits.value[teamId].color_code = '';
    colorPickerOpen.value = null;
}

function toggleColorPicker(teamId) {
    colorPickerOpen.value = colorPickerOpen.value === teamId ? null : teamId;
}

function saveTeam(team) {
    saving.value[team.id] = true;
    axios.put(route('leagues.teams.update', [props.league.slug, team.id]), {
        name: edits.value[team.id].name,
        contact_name: edits.value[team.id].contact_name,
        contact_email: edits.value[team.id].contact_email,
        color_code: edits.value[team.id].color_code,
        division_id: team.division_id,
        send_invite: sendInvite.value[team.id] || false,
    }).then((res) => {
        saved.value[team.id] = true;
        if (res.data.invite_sent) invited.value[team.id] = true;
        sendInvite.value[team.id] = false;
        setTimeout(() => { saved.value[team.id] = false; invited.value[team.id] = false; }, 2000);
    }).finally(() => {
        saving.value[team.id] = false;
    });
}

function sendInviteOnly(team) {
    inviting.value[team.id] = true;
    axios.post(route('leagues.teams.send-invite', [props.league.slug, team.id]))
        .then(() => {
            invited.value[team.id] = true;
            setTimeout(() => { invited.value[team.id] = false; }, 2000);
        })
        .catch((err) => {
            alert(err.response?.data?.message || 'Failed to send invite.');
        })
        .finally(() => {
            inviting.value[team.id] = false;
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
            <div class="hidden sm:grid grid-cols-[28px_minmax(100px,1.2fr)_minmax(80px,1fr)_minmax(100px,1.5fr)_minmax(60px,0.6fr)_auto] gap-2 px-3 py-1.5 text-[9px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                <span></span>
                <span>Team</span>
                <span>Coach</span>
                <span>Email</span>
                <span>Division</span>
                <span></span>
            </div>

            <!-- Team rows — desktop -->
            <div v-for="team in filteredTeams" :key="team.id"
                class="hidden sm:grid grid-cols-[28px_minmax(100px,1.2fr)_minmax(80px,1fr)_minmax(100px,1.5fr)_minmax(60px,0.6fr)_auto] gap-2 items-center px-3 py-1 border-b border-gray-50 hover:bg-gray-50">
                <!-- Color swatch -->
                <div class="relative flex justify-center">
                    <button @click="isManager && toggleColorPicker(team.id)"
                        class="h-5 w-5 rounded-full border border-gray-200 cursor-pointer"
                        :style="{ backgroundColor: edits[team.id].color_code || '#e5e7eb' }"
                        :disabled="!isManager">
                    </button>
                    <!-- Color picker popover -->
                    <div v-if="colorPickerOpen === team.id" class="absolute top-7 left-0 z-50 rounded-lg border border-gray-200 bg-white p-2 shadow-lg" style="width: 176px">
                        <div class="grid grid-cols-6 gap-1">
                            <button v-for="c in presetColors" :key="c"
                                @click="pickColor(team.id, c)"
                                class="h-6 w-6 rounded-full border transition hover:scale-110"
                                :class="[
                                    edits[team.id].color_code === c ? 'ring-2 ring-brand-500 border-gray-900' : '',
                                    c === '#FFFFFF' || c === '#C0C0C0' ? 'border-gray-300' : 'border-gray-200'
                                ]"
                                :style="{ backgroundColor: c }">
                            </button>
                        </div>
                        <button @click="clearColor(team.id)" class="mt-1.5 w-full text-center text-[10px] text-gray-500 hover:text-gray-700">No color</button>
                    </div>
                </div>

                <input v-model="edits[team.id].name" :disabled="!isManager"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs font-medium text-gray-900 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60 min-w-0" />

                <input v-model="edits[team.id].contact_name" :disabled="!isManager" placeholder="—"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60 min-w-0" />

                <input v-model="edits[team.id].contact_email" type="email" :disabled="!isManager" placeholder="—"
                    class="rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60 min-w-0" />

                <span class="text-[10px] text-gray-500 truncate" :title="team.division?.name">{{ team.division?.name }}</span>

                <div class="flex items-center gap-1.5 justify-end flex-wrap" v-if="isManager">
                    <label v-if="isDirty(team) && edits[team.id].contact_email" class="flex items-center gap-1 text-[9px] text-gray-500 cursor-pointer">
                        <input type="checkbox" v-model="sendInvite[team.id]" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 h-3 w-3" />
                        Invite
                    </label>
                    <button v-if="isDirty(team)" @click="saveTeam(team)" :disabled="saving[team.id]"
                        class="rounded bg-brand-600 px-1.5 py-0.5 text-[9px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
                        {{ saving[team.id] ? '...' : 'Save' }}
                    </button>
                    <button v-if="!isDirty(team) && (team.contact_email || edits[team.id].contact_email)" @click="sendInviteOnly(team)" :disabled="inviting[team.id]"
                        class="rounded border border-brand-300 bg-brand-50 px-1.5 py-0.5 text-[9px] font-medium text-brand-700 hover:bg-brand-100 disabled:opacity-50">
                        {{ inviting[team.id] ? '...' : 'Invite' }}
                    </button>
                    <span v-if="invited[team.id]" class="text-[9px] text-green-600">Sent!</span>
                    <span v-else-if="saved[team.id]" class="text-[9px] text-green-600">Saved</span>
                    <button @click="deleteTeam(team)" class="text-[9px] text-red-400 hover:text-red-600">Del</button>
                </div>
            </div>

            <!-- Team rows — mobile -->
            <div v-for="team in filteredTeams" :key="'m-' + team.id"
                class="sm:hidden border-b border-gray-50 px-3 py-2.5">
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <button @click="isManager && toggleColorPicker(team.id)"
                            class="h-5 w-5 rounded-full border border-gray-200 shrink-0"
                            :style="{ backgroundColor: edits[team.id].color_code || '#e5e7eb' }"
                            :disabled="!isManager">
                        </button>
                        <div v-if="colorPickerOpen === team.id" class="absolute top-7 left-0 z-50 rounded-lg border border-gray-200 bg-white p-2 shadow-lg" style="width: 176px">
                            <div class="grid grid-cols-6 gap-1">
                                <button v-for="c in presetColors" :key="c"
                                    @click="pickColor(team.id, c)"
                                    class="h-6 w-6 rounded-full border transition hover:scale-110"
                                    :class="[
                                        edits[team.id].color_code === c ? 'ring-2 ring-brand-500 border-gray-900' : '',
                                        c === '#FFFFFF' || c === '#C0C0C0' ? 'border-gray-300' : 'border-gray-200'
                                    ]"
                                    :style="{ backgroundColor: c }">
                                </button>
                            </div>
                            <button @click="clearColor(team.id)" class="mt-1.5 w-full text-center text-[10px] text-gray-500 hover:text-gray-700">No color</button>
                        </div>
                    </div>
                    <input v-model="edits[team.id].name" :disabled="!isManager"
                        class="flex-1 rounded border-transparent bg-transparent px-1 py-0.5 text-sm font-medium text-gray-900 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60 min-w-0" />
                    <span class="shrink-0 text-[10px] text-gray-400">{{ team.division?.name }}</span>
                </div>
                <div class="mt-1.5 grid grid-cols-2 gap-2 pl-7">
                    <input v-model="edits[team.id].contact_name" :disabled="!isManager" placeholder="Coach name"
                        class="rounded border-gray-200 bg-transparent px-1.5 py-1 text-xs text-gray-700 focus:border-brand-500 focus:ring-brand-500 disabled:opacity-60 min-w-0" />
                    <input v-model="edits[team.id].contact_email" type="email" :disabled="!isManager" placeholder="Coach email"
                        class="rounded border-gray-200 bg-transparent px-1.5 py-1 text-xs text-gray-700 focus:border-brand-500 focus:ring-brand-500 disabled:opacity-60 min-w-0" />
                </div>
                <div v-if="isManager" class="mt-1.5 flex items-center gap-2 pl-7 flex-wrap">
                    <label v-if="isDirty(team) && edits[team.id].contact_email" class="flex items-center gap-1 text-[10px] text-gray-500 cursor-pointer">
                        <input type="checkbox" v-model="sendInvite[team.id]" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 h-3.5 w-3.5" />
                        Send invite
                    </label>
                    <button v-if="isDirty(team)" @click="saveTeam(team)" :disabled="saving[team.id]"
                        class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
                        {{ saving[team.id] ? '...' : 'Save' }}
                    </button>
                    <button v-if="!isDirty(team) && (team.contact_email || edits[team.id].contact_email)" @click="sendInviteOnly(team)" :disabled="inviting[team.id]"
                        class="rounded border border-brand-300 bg-brand-50 px-2 py-1 text-[10px] font-medium text-brand-700 hover:bg-brand-100 disabled:opacity-50">
                        {{ inviting[team.id] ? '...' : 'Send Invite' }}
                    </button>
                    <span v-if="invited[team.id]" class="text-[10px] text-green-600">Sent!</span>
                    <span v-else-if="saved[team.id]" class="text-[10px] text-green-600">Saved</span>
                    <button @click="deleteTeam(team)" class="text-[10px] text-red-400 hover:text-red-600">Delete</button>
                </div>
            </div>

            <div v-if="filteredTeams.length === 0" class="px-3 py-6 text-center text-xs text-gray-400">
                No teams{{ filterDiv ? ' in this division' : '' }}.
            </div>
        </div>

        <p class="mt-2 text-[10px] text-gray-400">Click any field to edit. Changes save per row. {{ filteredTeams.length }} team(s) shown.</p>
    </LeagueLayout>
</template>
