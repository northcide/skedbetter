<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    league: Object,
    teams: Array,
    divisions: { type: Array, default: () => [] },
    userRole: String,
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const filterDiv = ref('');
const filterSearch = ref('');
const saving = ref({});
const showAddRow = ref(false);
const addingTeam = ref(false);
const newTeam = ref({ name: '', division_id: '', contact_name: '', contact_email: '' });
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
    let result = props.teams;
    if (filterDiv.value) {
        result = result.filter(t => t.division_id == filterDiv.value);
    }
    if (filterSearch.value) {
        const q = filterSearch.value.toLowerCase();
        result = result.filter(t =>
            t.name.toLowerCase().includes(q) ||
            (t.contact_name || '').toLowerCase().includes(q) ||
            (t.contact_email || '').toLowerCase().includes(q) ||
            (t.division?.name || '').toLowerCase().includes(q)
        );
    }
    return result;
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

function addTeam() {
    if (!newTeam.value.name || !newTeam.value.division_id) return;
    addingTeam.value = true;
    axios.post(route('leagues.teams.store', props.league.slug), {
        name: newTeam.value.name,
        division_id: newTeam.value.division_id,
        contact_name: newTeam.value.contact_name || null,
        contact_email: newTeam.value.contact_email || null,
    }).then(() => {
        newTeam.value = { name: '', division_id: '', contact_name: '', contact_email: '' };
        showAddRow.value = false;
        router.reload({ only: ['teams'] });
    }).catch(() => {}).finally(() => { addingTeam.value = false; });
}

const hasUnsavedChanges = computed(() => props.teams.some(t => isDirty(t)));

// Warn on browser close/refresh
function onBeforeUnload(e) {
    if (hasUnsavedChanges.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}
onMounted(() => window.addEventListener('beforeunload', onBeforeUnload));
onUnmounted(() => window.removeEventListener('beforeunload', onBeforeUnload));

// Warn on Inertia navigation (sidebar links, etc.)
let removeInertiaListener;
onMounted(() => {
    removeInertiaListener = router.on('before', (event) => {
        if (hasUnsavedChanges.value && !confirm('You have unsaved changes. Leave this page?')) {
            event.preventDefault();
        }
    });
});
onUnmounted(() => removeInertiaListener?.());
</script>

<template>
    <Head :title="`${league.name} - Team Roster`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-base font-semibold text-gray-900">Team Roster</h2>
            <input v-model="filterSearch" type="text" placeholder="Search..." class="w-32 rounded-md border-gray-300 text-xs" />
            <select v-model="filterDiv" class="w-32">
                <option value="">All Divisions</option>
                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
            <button v-if="isManager && !showAddRow" @click="showAddRow = true"
                class="text-[10px] font-medium text-brand-600 hover:text-brand-700">+ Add Team</button>
        </div>

        <FlashMessage />

        <div class="mt-3 max-w-4xl rounded-lg border border-gray-200 bg-white">
            <!-- Header -->
            <div class="hidden sm:flex items-center gap-1.5 px-2 py-1.5 text-[9px] font-semibold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                <span class="w-6 shrink-0"></span>
                <span class="w-32 shrink-0">Team</span>
                <span class="w-28 shrink-0">Coach</span>
                <span class="w-44 shrink-0">Email</span>
                <span class="w-20 shrink-0">Division</span>
                <span class="flex-1"></span>
            </div>

            <!-- Team rows — desktop -->
            <div v-for="team in filteredTeams" :key="team.id"
                class="hidden sm:flex items-center gap-1.5 px-2 py-1 border-b border-gray-50 hover:bg-gray-50">
                <!-- Color swatch -->
                <div class="relative w-6 shrink-0 flex justify-center">
                    <button @click="isManager && toggleColorPicker(team.id)"
                        class="h-5 w-5 rounded-full border border-gray-200 cursor-pointer"
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
                    class="w-32 shrink-0 rounded border-transparent bg-transparent px-1 py-0.5 text-xs font-medium text-gray-900 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <input v-model="edits[team.id].contact_name" :disabled="!isManager" placeholder="—"
                    class="w-28 shrink-0 rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <input v-model="edits[team.id].contact_email" type="email" :disabled="!isManager" placeholder="—"
                    class="w-44 shrink-0 rounded border-transparent bg-transparent px-1 py-0.5 text-xs text-gray-700 hover:border-gray-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500 disabled:opacity-60" />

                <span class="w-20 shrink-0 text-[10px] text-gray-500 truncate" :title="team.division?.name">{{ team.division?.name }}</span>

                <div class="flex-1 flex items-center gap-1 justify-end" v-if="isManager">
                    <label v-if="isDirty(team) && edits[team.id].contact_email" class="flex items-center gap-0.5 text-[9px] text-gray-500 cursor-pointer" title="Send invite on save">
                        <input type="checkbox" v-model="sendInvite[team.id]" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 h-3 w-3" />
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </label>
                    <button v-if="isDirty(team)" @click="saveTeam(team)" :disabled="saving[team.id]"
                        class="rounded bg-brand-600 px-1.5 py-0.5 text-[9px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
                        {{ saving[team.id] ? '...' : 'Save' }}
                    </button>
                    <button v-if="!isDirty(team) && (team.contact_email || edits[team.id].contact_email)" @click="sendInviteOnly(team)" :disabled="inviting[team.id]"
                        title="Send login invite to coach"
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

            <div v-if="filteredTeams.length === 0 && !showAddRow" class="px-3 py-6 text-center text-xs text-gray-400">
                No teams{{ filterDiv ? ' in this division' : '' }}.
            </div>

            <!-- Inline add row -->
            <div v-if="showAddRow && isManager" class="border-t border-brand-200 bg-brand-50/30 px-2 py-2">
                <!-- Desktop -->
                <div class="hidden sm:flex items-center gap-1.5">
                    <div class="w-6 shrink-0"></div>
                    <input v-model="newTeam.name" placeholder="Team name *" class="w-32 shrink-0 rounded border-gray-300 bg-white px-1 py-0.5 text-xs" />
                    <input v-model="newTeam.contact_name" placeholder="Coach name" class="w-28 shrink-0 rounded border-gray-300 bg-white px-1 py-0.5 text-xs" />
                    <input v-model="newTeam.contact_email" type="email" placeholder="Coach email" class="w-44 shrink-0 rounded border-gray-300 bg-white px-1 py-0.5 text-xs" />
                    <select v-model="newTeam.division_id" class="w-28 shrink-0 rounded border-gray-300 bg-white py-0.5 pl-1 pr-6 text-[10px]">
                        <option value="">Division *</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <div class="flex-1 flex items-center gap-1 justify-end">
                        <button @click="addTeam" :disabled="addingTeam || !newTeam.name || !newTeam.division_id"
                            class="rounded bg-brand-600 px-1.5 py-0.5 text-[9px] font-semibold text-white hover:bg-brand-700 disabled:opacity-50">
                            {{ addingTeam ? '...' : 'Add' }}
                        </button>
                        <button @click="showAddRow = false" class="text-[9px] text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </div>
                <!-- Mobile -->
                <div class="sm:hidden space-y-2">
                    <input v-model="newTeam.name" placeholder="Team name *" class="w-full rounded border-gray-300 bg-white px-2 py-1.5 text-sm" />
                    <select v-model="newTeam.division_id" class="w-full rounded border-gray-300 bg-white py-1.5 px-2 text-sm">
                        <option value="">Select division *</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input v-model="newTeam.contact_name" placeholder="Coach name" class="rounded border-gray-300 bg-white px-2 py-1.5 text-xs" />
                        <input v-model="newTeam.contact_email" type="email" placeholder="Coach email" class="rounded border-gray-300 bg-white px-2 py-1.5 text-xs" />
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton @click="addTeam" :disabled="addingTeam || !newTeam.name || !newTeam.division_id" class="text-xs">
                            {{ addingTeam ? '...' : 'Add Team' }}
                        </PrimaryButton>
                        <button @click="showAddRow = false" class="text-xs text-gray-500">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-2 text-[10px] text-gray-400">Click any field to edit. Changes save per row. {{ filteredTeams.length }} team(s) shown.</p>
    </LeagueLayout>
</template>
