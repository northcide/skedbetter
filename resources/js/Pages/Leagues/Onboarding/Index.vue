<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({ league: Object, userRole: String });

const activeStep = ref(1);
const saving = ref(false);
const errors = ref({});

// All wizard data lives in memory
const season = ref({ name: '', start_date: '', end_date: '' });
const locations = ref([]); // [{ name, address, city, state, fields: [{ name }] }]
const divisions = ref([]); // [{ name, age_group, teams: [{ name }] }]

// --- Locations ---
const newLoc = ref({ name: '', address: '', city: '', state: '', fields: [{ name: '' }] });
const addLocField = () => newLoc.value.fields.push({ name: '' });
const removeLocField = (i) => { if (newLoc.value.fields.length > 1) newLoc.value.fields.splice(i, 1); };
const addLocation = () => {
    if (!newLoc.value.name || !newLoc.value.fields.some(f => f.name)) return;
    locations.value.push(JSON.parse(JSON.stringify(newLoc.value)));
    newLoc.value = { name: '', address: '', city: '', state: '', fields: [{ name: '' }] };
};
const removeLocation = (i) => locations.value.splice(i, 1);
const editingLoc = ref(null);
const startEditLoc = (i) => { editingLoc.value = i; };
const cancelEditLoc = () => { editingLoc.value = null; };

// --- Divisions ---
const newDiv = ref({ name: '', age_group: '' });
const addDivision = () => {
    if (!newDiv.value.name) return;
    divisions.value.push({ name: newDiv.value.name, age_group: newDiv.value.age_group, teams: [] });
    newDiv.value = { name: '', age_group: '' };
};
const removeDivision = (i) => divisions.value.splice(i, 1);
const editingDiv = ref(null);
const startEditDiv = (i) => { editingDiv.value = i; };
const cancelEditDiv = () => { editingDiv.value = null; };

// --- Teams (per division) ---
const newTeamName = ref({});
const addTeam = (divIdx) => {
    const name = newTeamName.value[divIdx];
    if (!name) return;
    divisions.value[divIdx].teams.push({ name });
    newTeamName.value[divIdx] = '';
};
const removeTeam = (divIdx, teamIdx) => divisions.value[divIdx].teams.splice(teamIdx, 1);

// --- Steps ---
const steps = [
    { num: 1, label: 'Season' },
    { num: 2, label: 'Locations' },
    { num: 3, label: 'Divisions' },
    { num: 4, label: 'Teams' },
    { num: 5, label: 'Save' },
];

const canProceed = computed(() => ({
    1: season.value.name && season.value.start_date && season.value.end_date,
    2: locations.value.length > 0,
    3: divisions.value.length > 0,
    4: divisions.value.some(d => d.teams.length > 0),
}));

const stepComplete = (num) => !!canProceed.value[num];
const nextStep = () => { if (activeStep.value < 5) activeStep.value++; };
const prevStep = () => { if (activeStep.value > 1) activeStep.value--; };

const totalTeams = computed(() => divisions.value.reduce((sum, d) => sum + d.teams.length, 0));
const totalFields = computed(() => locations.value.reduce((sum, l) => sum + l.fields.length, 0));

const hasData = computed(() => season.value.name || locations.value.length || divisions.value.length);

// --- Navigation warning ---
function beforeUnloadHandler(e) {
    if (hasData.value && !saving.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}
onMounted(() => window.addEventListener('beforeunload', beforeUnloadHandler));
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnloadHandler));

// Inertia navigation warning
router.on('before', (event) => {
    if (hasData.value && !saving.value) {
        if (!confirm('League setup is not complete. Changes will be lost if you leave. Continue?')) {
            event.preventDefault();
        }
    }
});

// --- Save all ---
function saveAll() {
    saving.value = true;
    errors.value = {};

    axios.post(route('leagues.onboarding.save', props.league.slug), {
        season: season.value,
        locations: locations.value,
        divisions: divisions.value,
    }).then(() => {
        window.location.href = route('leagues.show', props.league.slug);
    }).catch((err) => {
        saving.value = false;
        errors.value = err.response?.data?.errors || {};
    });
}
</script>

<template>
    <Head :title="`Setup ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <h2 class="text-lg font-semibold text-gray-900">Setup {{ league.name }}</h2>
        <FlashMessage />

        <div class="mt-4">
            <!-- Steps -->
            <nav class="mb-5">
                <ol class="flex items-center gap-1">
                    <li v-for="step in steps" :key="step.num">
                        <button @click="activeStep = step.num" class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition"
                            :class="{ 'bg-brand-600 text-white': activeStep === step.num, 'bg-green-100 text-green-700': stepComplete(step.num) && activeStep !== step.num, 'bg-gray-100 text-gray-500': !stepComplete(step.num) && activeStep !== step.num }">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold" :class="stepComplete(step.num) ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600'">{{ stepComplete(step.num) ? '✓' : step.num }}</span>
                            {{ step.label }}
                        </button>
                    </li>
                </ol>
            </nav>

            <!-- Validation errors -->
            <div v-if="Object.keys(errors).length" class="mb-4 rounded-lg bg-red-50 p-3 text-xs text-red-700">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-4"><li v-for="(msgs, key) in errors" :key="key">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li></ul>
            </div>

            <!-- Step 1: Season -->
            <div v-if="activeStep === 1" class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Season</h3>
                <div class="mt-3 space-y-3">
                    <div>
                        <InputLabel value="Name" class="text-xs" />
                        <TextInput v-model="season.name" class="mt-1 block w-full" placeholder="e.g. Spring 2026" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><InputLabel value="Start" class="text-xs" /><TextInput v-model="season.start_date" type="date" class="mt-1 block w-full" /></div>
                        <div><InputLabel value="End" class="text-xs" /><TextInput v-model="season.end_date" type="date" class="mt-1 block w-full" /></div>
                    </div>
                    <div class="flex justify-end"><PrimaryButton @click="nextStep" :disabled="!canProceed[1]">Next: Locations</PrimaryButton></div>
                </div>
            </div>

            <!-- Step 2: Locations -->
            <div v-if="activeStep === 2" class="space-y-4">
                <div v-if="locations.length" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2"><h4 class="text-xs font-semibold text-gray-500">Locations ({{ locations.length }})</h4></div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="(loc, i) in locations" :key="i" class="px-4 py-2">
                            <div v-if="editingLoc !== i" class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ loc.name }}</span>
                                    <span v-if="loc.city" class="text-xs text-gray-400"> — {{ loc.city }}{{ loc.state ? `, ${loc.state}` : '' }}</span>
                                    <span class="ml-2 text-xs text-gray-400">{{ loc.fields.length }} field(s)</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="startEditLoc(i)" class="text-[10px] text-brand-600">Edit</button>
                                    <button @click="removeLocation(i)" class="text-[10px] text-red-500">Delete</button>
                                </div>
                            </div>
                            <div v-else class="space-y-2">
                                <div class="grid grid-cols-4 gap-2">
                                    <TextInput v-model="loc.name" placeholder="Name" class="col-span-2" />
                                    <TextInput v-model="loc.city" placeholder="City" />
                                    <TextInput v-model="loc.state" placeholder="ST" maxlength="2" />
                                </div>
                                <div v-for="(f, fi) in loc.fields" :key="fi" class="flex items-center gap-2">
                                    <TextInput v-model="f.name" class="flex-1" :placeholder="`Field ${fi+1}`" />
                                    <button v-if="loc.fields.length > 1" @click="loc.fields.splice(fi, 1)" class="text-xs text-red-500">Remove</button>
                                </div>
                                <button @click="loc.fields.push({ name: '' })" class="text-xs text-brand-600">+ Add field</button>
                                <div class="flex gap-2"><button @click="cancelEditLoc" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white">Done</button></div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Add Location</h3>
                    <div class="mt-3 space-y-3">
                        <div><InputLabel value="Name" class="text-xs" /><TextInput v-model="newLoc.name" class="mt-1 block w-full" placeholder="e.g. Central Park" /></div>
                        <div class="grid grid-cols-4 gap-2">
                            <TextInput v-model="newLoc.address" placeholder="Address" class="col-span-2" />
                            <TextInput v-model="newLoc.city" placeholder="City" />
                            <TextInput v-model="newLoc.state" placeholder="ST" maxlength="2" />
                        </div>
                        <div>
                            <InputLabel value="Fields" class="text-xs" />
                            <div v-for="(f, i) in newLoc.fields" :key="i" class="mt-1.5 flex items-center gap-2">
                                <TextInput v-model="f.name" class="flex-1" :placeholder="`Field ${i+1}`" />
                                <button v-if="newLoc.fields.length > 1" @click="removeLocField(i)" class="text-xs text-red-500">Remove</button>
                            </div>
                            <button @click="addLocField" class="mt-1.5 text-xs text-brand-600">+ Add field</button>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="addLocation" :disabled="!newLoc.name">Add Location</SecondaryButton>
                            <PrimaryButton @click="nextStep" :disabled="!canProceed[2]">Next: Divisions</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Divisions -->
            <div v-if="activeStep === 3" class="space-y-4">
                <div v-if="divisions.length" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2"><h4 class="text-xs font-semibold text-gray-500">Divisions ({{ divisions.length }})</h4></div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="(div, i) in divisions" :key="i" class="px-4 py-2">
                            <div v-if="editingDiv !== i" class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ div.name }}</span>
                                    <span v-if="div.age_group" class="text-xs text-gray-400"> ({{ div.age_group }})</span>
                                    <span class="ml-2 text-xs text-gray-400">{{ div.teams.length }} team(s)</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="startEditDiv(i)" class="text-[10px] text-brand-600">Edit</button>
                                    <button @click="removeDivision(i)" class="text-[10px] text-red-500">Delete</button>
                                </div>
                            </div>
                            <div v-else class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <TextInput v-model="div.name" placeholder="Name" />
                                    <TextInput v-model="div.age_group" placeholder="Age Group" />
                                </div>
                                <button @click="cancelEditDiv" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white">Done</button>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Add Division</h3>
                    <div class="mt-3 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div><InputLabel value="Name" class="text-xs" /><TextInput v-model="newDiv.name" class="mt-1 block w-full" placeholder="e.g. U10 Boys" /></div>
                            <div><InputLabel value="Age Group" class="text-xs" /><TextInput v-model="newDiv.age_group" class="mt-1 block w-full" placeholder="e.g. Under 10" /></div>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="addDivision" :disabled="!newDiv.name">Add Division</SecondaryButton>
                            <PrimaryButton @click="nextStep" :disabled="!canProceed[3]">Next: Teams</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Teams -->
            <div v-if="activeStep === 4" class="space-y-4">
                <div v-for="(div, di) in divisions" :key="di" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2">
                        <h4 class="text-xs font-semibold text-gray-700">{{ div.name }} <span class="font-normal text-gray-400">({{ div.teams.length }} teams)</span></h4>
                    </div>
                    <div class="px-4 py-2">
                        <div v-for="(team, ti) in div.teams" :key="ti" class="flex items-center justify-between py-1">
                            <span class="text-sm text-gray-900">{{ team.name }}</span>
                            <button @click="removeTeam(di, ti)" class="text-[10px] text-red-500">Remove</button>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <TextInput v-model="newTeamName[di]" class="flex-1" placeholder="Team name" @keyup.enter="addTeam(di)" />
                            <button @click="addTeam(di)" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white" :disabled="!newTeamName[di]">Add</button>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <PrimaryButton @click="nextStep" :disabled="!canProceed[4]">Review & Save</PrimaryButton>
                </div>
            </div>

            <!-- Step 5: Review & Save -->
            <div v-if="activeStep === 5" class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="text-lg font-bold text-gray-900">Review & Save</h3>
                <p class="mt-1 text-xs text-gray-500">Everything below will be created when you click Save.</p>

                <div class="mt-4 grid grid-cols-4 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3 text-center"><p class="text-xl font-bold text-brand-600">1</p><p class="text-[10px] text-gray-500">Season</p></div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center"><p class="text-xl font-bold text-brand-600">{{ locations.length }}</p><p class="text-[10px] text-gray-500">Locations</p></div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center"><p class="text-xl font-bold text-brand-600">{{ divisions.length }}</p><p class="text-[10px] text-gray-500">Divisions</p></div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center"><p class="text-xl font-bold text-brand-600">{{ totalTeams }}</p><p class="text-[10px] text-gray-500">Teams</p></div>
                </div>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="rounded border border-gray-100 p-3">
                        <p class="text-xs font-semibold text-gray-500">Season</p>
                        <p class="text-gray-900">{{ season.name }} ({{ season.start_date }} to {{ season.end_date }})</p>
                    </div>
                    <div class="rounded border border-gray-100 p-3">
                        <p class="text-xs font-semibold text-gray-500">Locations & Fields</p>
                        <div v-for="loc in locations" :key="loc.name" class="mt-1">
                            <span class="font-medium text-gray-900">{{ loc.name }}</span>
                            <span class="text-xs text-gray-400"> — {{ loc.fields.map(f => f.name).join(', ') }}</span>
                        </div>
                    </div>
                    <div class="rounded border border-gray-100 p-3">
                        <p class="text-xs font-semibold text-gray-500">Divisions & Teams</p>
                        <div v-for="div in divisions" :key="div.name" class="mt-1">
                            <span class="font-medium text-gray-900">{{ div.name }}</span>
                            <span v-if="div.teams.length" class="text-xs text-gray-400"> — {{ div.teams.map(t => t.name).join(', ') }}</span>
                            <span v-else class="text-xs text-gray-400"> — no teams</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-between">
                    <SecondaryButton @click="prevStep">Go Back</SecondaryButton>
                    <PrimaryButton @click="saveAll" :disabled="saving">{{ saving ? 'Saving...' : 'Save & Complete Setup' }}</PrimaryButton>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
