<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    league: Object, currentStep: Number, seasons: Array,
    locations: Array, divisions: Array, teams: Array, userRole: String,
});

const activeStep = ref(props.currentStep);
const steps = [
    { num: 1, label: 'Season' }, { num: 2, label: 'Locations' },
    { num: 3, label: 'Divisions' }, { num: 4, label: 'Teams' }, { num: 5, label: 'Done' },
];

const currentSeasonId = computed(() => props.seasons[0]?.id || '');

// Editing state
const editingId = ref(null);
const editForm = ref({});

function startEdit(type, item) {
    editingId.value = `${type}-${item.id}`;
    if (type === 'location') editForm.value = { name: item.name, address: item.address || '', city: item.city || '', state: item.state || '' };
    else if (type === 'division') editForm.value = { name: item.name, age_group: item.age_group || '' };
    else if (type === 'team') editForm.value = { name: item.name };
}

function cancelEdit() { editingId.value = null; editForm.value = {}; }

function saveEdit(type, item) {
    const routeMap = {
        location: 'leagues.onboarding.update-location',
        division: 'leagues.onboarding.update-division',
        team: 'leagues.onboarding.update-team',
    };
    router.put(route(routeMap[type], [props.league.slug, item.id]), editForm.value, {
        onSuccess: () => cancelEdit(),
    });
}

// Season form
const seasonForm = useForm({ name: '', start_date: '', end_date: '' });
const submitSeason = () => seasonForm.post(route('leagues.onboarding.season', props.league.slug), {
    onSuccess: () => { seasonForm.reset(); activeStep.value = 2; },
});

// Location form
const locationForm = useForm({ name: '', address: '', city: '', state: '', zip: '', fields: [{ name: '' }] });
const addField = () => locationForm.fields.push({ name: '' });
const removeField = (i) => { if (locationForm.fields.length > 1) locationForm.fields.splice(i, 1); };
const submitLocation = () => locationForm.post(route('leagues.onboarding.location', props.league.slug), {
    onSuccess: () => { locationForm.reset(); locationForm.fields = [{ name: '' }]; },
});

// Division form
const divisionForm = useForm({ name: '', season_id: '', age_group: '' });
watch(currentSeasonId, (val) => { if (!divisionForm.season_id) divisionForm.season_id = val; }, { immediate: true });
const submitDivision = () => {
    if (!divisionForm.season_id) divisionForm.season_id = currentSeasonId.value;
    divisionForm.post(route('leagues.onboarding.division', props.league.slug), {
        onSuccess: () => divisionForm.reset('name', 'age_group'),
    });
};

// Teams form
const teamsForm = useForm({ division_id: '', teams: [{ name: '' }] });
watch(() => props.divisions, (divs) => { if (!teamsForm.division_id && divs.length) teamsForm.division_id = divs[0].id; }, { immediate: true });
const addTeam = () => teamsForm.teams.push({ name: '' });
const removeTeam = (i) => { if (teamsForm.teams.length > 1) teamsForm.teams.splice(i, 1); };
const submitTeams = () => teamsForm.post(route('leagues.onboarding.teams', props.league.slug), {
    onSuccess: () => { teamsForm.teams = [{ name: '' }]; },
});

const completeSetup = () => router.post(route('leagues.onboarding.complete', props.league.slug));

const deleteLocation = (loc) => { if (confirm(`Delete "${loc.name}"?`)) router.delete(route('leagues.onboarding.delete-location', [props.league.slug, loc.id])); };
const deleteDivision = (div) => { if (confirm(`Delete "${div.name}"?`)) router.delete(route('leagues.onboarding.delete-division', [props.league.slug, div.id])); };
const deleteTeam = (team) => { if (confirm(`Delete "${team.name}"?`)) router.delete(route('leagues.onboarding.delete-team', [props.league.slug, team.id])); };

const stepComplete = (num) => {
    if (num === 1) return props.seasons.length > 0;
    if (num === 2) return props.locations.length > 0;
    if (num === 3) return props.divisions.length > 0;
    if (num === 4) return props.teams.length > 0;
    return false;
};
</script>

<template>
    <Head :title="`Setup ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <h2 class="text-lg font-semibold text-gray-900">Setup {{ league.name }}</h2>
        <FlashMessage />

        <div class="mt-4">
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

            <!-- Step 1: Season -->
            <div v-if="activeStep === 1" class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Create Season</h3>
                <div v-if="seasons.length" class="mt-2 rounded bg-green-50 px-3 py-1.5 text-xs text-green-700">{{ seasons.map(s => s.name).join(', ') }}</div>
                <form @submit.prevent="submitSeason" class="mt-3 space-y-3">
                    <div>
                        <InputLabel for="s_name" value="Name" class="text-xs" />
                        <TextInput id="s_name" v-model="seasonForm.name" class="mt-1 block w-full" required placeholder="e.g. Spring 2026" />
                        <InputError :message="seasonForm.errors.name" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="s_start" value="Start" class="text-xs" />
                            <TextInput id="s_start" v-model="seasonForm.start_date" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="s_end" value="End" class="text-xs" />
                            <TextInput id="s_end" v-model="seasonForm.end_date" type="date" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <div class="flex justify-end"><PrimaryButton :disabled="seasonForm.processing">Create Season</PrimaryButton></div>
                </form>
            </div>

            <!-- Step 2: Locations -->
            <div v-if="activeStep === 2" class="space-y-4">
                <div v-if="locations.length" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2"><h4 class="text-xs font-semibold text-gray-500">Locations</h4></div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="loc in locations" :key="loc.id" class="px-4 py-2">
                            <!-- View mode -->
                            <div v-if="editingId !== `location-${loc.id}`" class="flex items-center justify-between">
                                <div><span class="text-sm font-medium text-gray-900">{{ loc.name }}</span> <span class="text-xs text-gray-400">{{ loc.fields_count }} field(s)</span></div>
                                <div class="flex gap-2">
                                    <button @click="startEdit('location', loc)" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</button>
                                    <button @click="deleteLocation(loc)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </div>
                            <!-- Edit mode -->
                            <div v-else class="space-y-2">
                                <div class="grid grid-cols-4 gap-2">
                                    <TextInput v-model="editForm.name" class="col-span-2" placeholder="Name" />
                                    <TextInput v-model="editForm.city" placeholder="City" />
                                    <TextInput v-model="editForm.state" placeholder="ST" maxlength="2" />
                                </div>
                                <div class="flex gap-2">
                                    <button @click="saveEdit('location', loc)" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white">Save</button>
                                    <button @click="cancelEdit" class="text-[10px] text-gray-500">Cancel</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Add Location</h3>
                    <form @submit.prevent="submitLocation" class="mt-3 space-y-3">
                        <div>
                            <InputLabel for="l_name" value="Name" class="text-xs" />
                            <TextInput id="l_name" v-model="locationForm.name" class="mt-1 block w-full" required placeholder="e.g. Central Park" />
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            <TextInput v-model="locationForm.address" placeholder="Address" class="col-span-2" />
                            <TextInput v-model="locationForm.city" placeholder="City" />
                            <TextInput v-model="locationForm.state" placeholder="ST" maxlength="2" />
                        </div>
                        <div>
                            <InputLabel value="Fields" class="text-xs" />
                            <div v-for="(field, i) in locationForm.fields" :key="i" class="mt-1.5 flex items-center gap-2">
                                <TextInput v-model="field.name" class="flex-1" :placeholder="`Field ${i + 1}`" required />
                                <button v-if="locationForm.fields.length > 1" type="button" @click="removeField(i)" class="text-xs text-red-500">Remove</button>
                            </div>
                            <button type="button" @click="addField" class="mt-1.5 text-xs text-brand-600">+ Add field</button>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 3" v-if="locations.length > 0">Next: Divisions</SecondaryButton>
                            <PrimaryButton :disabled="locationForm.processing">Add Location</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 3: Divisions -->
            <div v-if="activeStep === 3" class="space-y-4">
                <div v-if="divisions.length" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2"><h4 class="text-xs font-semibold text-gray-500">Divisions</h4></div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="div in divisions" :key="div.id" class="px-4 py-2">
                            <div v-if="editingId !== `division-${div.id}`" class="flex items-center justify-between">
                                <div><span class="text-sm font-medium text-gray-900">{{ div.name }}</span> <span v-if="div.age_group" class="text-xs text-gray-400">({{ div.age_group }})</span> <span class="text-xs text-gray-400">{{ div.teams_count }} team(s)</span></div>
                                <div class="flex gap-2">
                                    <button @click="startEdit('division', div)" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</button>
                                    <button @click="deleteDivision(div)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </div>
                            <div v-else class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <TextInput v-model="editForm.name" placeholder="Division Name" />
                                    <TextInput v-model="editForm.age_group" placeholder="Age Group" />
                                </div>
                                <div class="flex gap-2">
                                    <button @click="saveEdit('division', div)" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white">Save</button>
                                    <button @click="cancelEdit" class="text-[10px] text-gray-500">Cancel</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Add Division</h3>
                    <form @submit.prevent="submitDivision" class="mt-3 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="d_name" value="Name" class="text-xs" />
                                <TextInput id="d_name" v-model="divisionForm.name" class="mt-1 block w-full" required placeholder="e.g. U10 Boys" />
                            </div>
                            <div>
                                <InputLabel for="d_age" value="Age Group" class="text-xs" />
                                <TextInput id="d_age" v-model="divisionForm.age_group" class="mt-1 block w-full" placeholder="e.g. Under 10" />
                            </div>
                        </div>
                        <div v-if="seasons.length > 1">
                            <InputLabel for="d_season" value="Season" class="text-xs" />
                            <select id="d_season" v-model="divisionForm.season_id" class="mt-1 block w-full">
                                <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 4" v-if="divisions.length > 0">Next: Teams</SecondaryButton>
                            <PrimaryButton :disabled="divisionForm.processing">Add Division</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 4: Teams -->
            <div v-if="activeStep === 4" class="space-y-4">
                <div v-if="teams.length" class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-4 py-2"><h4 class="text-xs font-semibold text-gray-500">Teams ({{ teams.length }})</h4></div>
                    <ul class="divide-y divide-gray-50 max-h-56 overflow-y-auto">
                        <li v-for="team in teams" :key="team.id" class="px-4 py-2">
                            <div v-if="editingId !== `team-${team.id}`" class="flex items-center justify-between">
                                <div><span class="text-sm font-medium text-gray-900">{{ team.name }}</span> <span class="text-xs text-gray-400">{{ team.division?.name }}</span></div>
                                <div class="flex gap-2">
                                    <button @click="startEdit('team', team)" class="text-[10px] text-brand-600 hover:text-brand-700">Edit</button>
                                    <button @click="deleteTeam(team)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <TextInput v-model="editForm.name" class="flex-1" placeholder="Team Name" />
                                <button @click="saveEdit('team', team)" class="rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white">Save</button>
                                <button @click="cancelEdit" class="text-[10px] text-gray-500">Cancel</button>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Add Teams</h3>
                    <form @submit.prevent="submitTeams" class="mt-3 space-y-3">
                        <div>
                            <InputLabel for="t_div" value="Division" class="text-xs" />
                            <select id="t_div" v-model="teamsForm.division_id" class="mt-1 block w-full" required>
                                <option v-for="div in divisions" :key="div.id" :value="div.id">{{ div.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Team Names" class="text-xs" />
                            <div v-for="(team, i) in teamsForm.teams" :key="i" class="mt-1.5 flex items-center gap-2">
                                <TextInput v-model="team.name" class="flex-1" :placeholder="`Team ${i + 1}`" required />
                                <button v-if="teamsForm.teams.length > 1" type="button" @click="removeTeam(i)" class="text-xs text-red-500">Remove</button>
                            </div>
                            <button type="button" @click="addTeam" class="mt-1.5 text-xs text-brand-600">+ Add team</button>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 5" v-if="teams.length > 0">Finish</SecondaryButton>
                            <PrimaryButton :disabled="teamsForm.processing">Add Teams</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Step 5 -->
            <div v-if="activeStep === 5" class="rounded-lg border border-gray-200 bg-white p-6 text-center">
                <h3 class="text-xl font-bold text-gray-900">Ready!</h3>
                <div class="mt-4 grid grid-cols-4 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-xl font-bold text-brand-600">{{ seasons.length }}</p><p class="text-[10px] text-gray-500">Season(s)</p></div>
                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-xl font-bold text-brand-600">{{ locations.length }}</p><p class="text-[10px] text-gray-500">Location(s)</p></div>
                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-xl font-bold text-brand-600">{{ divisions.length }}</p><p class="text-[10px] text-gray-500">Division(s)</p></div>
                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-xl font-bold text-brand-600">{{ teams.length }}</p><p class="text-[10px] text-gray-500">Team(s)</p></div>
                </div>
                <div class="mt-5"><PrimaryButton @click="completeSetup">Start Scheduling</PrimaryButton></div>
            </div>
        </div>
    </LeagueLayout>
</template>
