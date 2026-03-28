<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object,
    currentStep: Number,
    seasons: Array,
    locations: Array,
    divisions: Array,
    teams: Array,
});

const activeStep = ref(props.currentStep);

const steps = [
    { num: 1, label: 'Season', desc: 'Set up your first season' },
    { num: 2, label: 'Locations', desc: 'Add facilities and fields' },
    { num: 3, label: 'Divisions', desc: 'Create age groups or skill levels' },
    { num: 4, label: 'Teams', desc: 'Add teams to divisions' },
    { num: 5, label: 'Done', desc: 'Start scheduling' },
];

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
const divisionForm = useForm({ name: '', season_id: props.seasons[0]?.id || '', age_group: '' });
const submitDivision = () => divisionForm.post(route('leagues.onboarding.division', props.league.slug), {
    onSuccess: () => divisionForm.reset('name', 'age_group'),
});

// Teams form
const teamsForm = useForm({ division_id: props.divisions[0]?.id || '', teams: [{ name: '' }] });
const addTeam = () => teamsForm.teams.push({ name: '' });
const removeTeam = (i) => { if (teamsForm.teams.length > 1) teamsForm.teams.splice(i, 1); };
const submitTeams = () => teamsForm.post(route('leagues.onboarding.teams', props.league.slug), {
    onSuccess: () => { teamsForm.teams = [{ name: '' }]; },
});

const completeSetup = () => router.post(route('leagues.onboarding.complete', props.league.slug));

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
        

        
        <!-- Page Header -->
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Setup {{ league.name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Complete these steps to get your league ready for scheduling.</p>
<FlashMessage />

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <!-- Step indicator -->
                <nav class="mb-8">
                    <ol class="flex items-center justify-between">
                        <li v-for="step in steps" :key="step.num" class="flex items-center">
                            <button
                                @click="activeStep = step.num"
                                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                                :class="{
                                    'bg-brand-600 text-white': activeStep === step.num,
                                    'bg-green-100 text-green-800': stepComplete(step.num) && activeStep !== step.num,
                                    'bg-gray-100 text-gray-500': !stepComplete(step.num) && activeStep !== step.num,
                                }"
                            >
                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                    :class="stepComplete(step.num) ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600'">
                                    {{ stepComplete(step.num) ? '✓' : step.num }}
                                </span>
                                <span class="hidden sm:inline">{{ step.label }}</span>
                            </button>
                        </li>
                    </ol>
                </nav>

                <!-- Step 1: Season -->
                <div v-if="activeStep === 1" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900">Create Your First Season</h3>
                    <p class="mt-1 text-sm text-gray-500">Seasons organize your divisions, teams, and schedules into time periods.</p>

                    <div v-if="seasons.length > 0" class="mt-4 rounded-md bg-green-50 p-3">
                        <p class="text-sm text-green-800">Current seasons: {{ seasons.map(s => s.name).join(', ') }}</p>
                    </div>

                    <form @submit.prevent="submitSeason" class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="s_name" value="Season Name" />
                            <TextInput id="s_name" v-model="seasonForm.name" class="mt-1 block w-full" required placeholder="e.g. Spring 2026" />
                            <InputError :message="seasonForm.errors.name" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="s_start" value="Start Date" />
                                <TextInput id="s_start" v-model="seasonForm.start_date" type="date" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="s_end" value="End Date" />
                                <TextInput id="s_end" v-model="seasonForm.end_date" type="date" class="mt-1 block w-full" required />
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span></span>
                            <PrimaryButton :disabled="seasonForm.processing">Create Season</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Locations -->
                <div v-if="activeStep === 2" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900">Add Locations & Fields</h3>
                    <p class="mt-1 text-sm text-gray-500">Add your facilities and the fields at each one.</p>

                    <div v-if="locations.length > 0" class="mt-4 rounded-md bg-green-50 p-3">
                        <p class="text-sm text-green-800" v-for="loc in locations" :key="loc.id">
                            {{ loc.name }} ({{ loc.fields_count }} fields)
                        </p>
                    </div>

                    <form @submit.prevent="submitLocation" class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="l_name" value="Location Name" />
                            <TextInput id="l_name" v-model="locationForm.name" class="mt-1 block w-full" required placeholder="e.g. Central Park Complex" />
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            <TextInput v-model="locationForm.address" placeholder="Address" class="col-span-2" />
                            <TextInput v-model="locationForm.city" placeholder="City" />
                            <TextInput v-model="locationForm.state" placeholder="ST" maxlength="2" />
                        </div>

                        <div>
                            <InputLabel value="Fields at this location" />
                            <div v-for="(field, i) in locationForm.fields" :key="i" class="mt-2 flex items-center gap-2">
                                <TextInput v-model="field.name" class="flex-1" :placeholder="`Field ${i + 1}`" required />
                                <button v-if="locationForm.fields.length > 1" type="button" @click="removeField(i)" class="text-sm text-red-500">Remove</button>
                            </div>
                            <button type="button" @click="addField" class="mt-2 text-sm text-brand-600 hover:text-brand-700">+ Add another field</button>
                        </div>

                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 3" v-if="locations.length > 0">Skip to Divisions</SecondaryButton>
                            <PrimaryButton :disabled="locationForm.processing">Add Location</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Divisions -->
                <div v-if="activeStep === 3" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900">Create Divisions</h3>
                    <p class="mt-1 text-sm text-gray-500">Organize teams by age group, skill level, or however your league is structured.</p>

                    <div v-if="divisions.length > 0" class="mt-4 rounded-md bg-green-50 p-3">
                        <p class="text-sm text-green-800" v-for="div in divisions" :key="div.id">
                            {{ div.name }} {{ div.age_group ? `(${div.age_group})` : '' }} — {{ div.teams_count }} teams
                        </p>
                    </div>

                    <form @submit.prevent="submitDivision" class="mt-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="d_name" value="Division Name" />
                                <TextInput id="d_name" v-model="divisionForm.name" class="mt-1 block w-full" required placeholder="e.g. U10 Boys" />
                            </div>
                            <div>
                                <InputLabel for="d_age" value="Age Group (optional)" />
                                <TextInput id="d_age" v-model="divisionForm.age_group" class="mt-1 block w-full" placeholder="e.g. Under 10" />
                            </div>
                        </div>
                        <div v-if="seasons.length > 1">
                            <InputLabel for="d_season" value="Season" />
                            <select id="d_season" v-model="divisionForm.season_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 4" v-if="divisions.length > 0">Skip to Teams</SecondaryButton>
                            <PrimaryButton :disabled="divisionForm.processing">Add Division</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Step 4: Teams -->
                <div v-if="activeStep === 4" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900">Add Teams</h3>
                    <p class="mt-1 text-sm text-gray-500">Add teams to your divisions. You can add more later.</p>

                    <div v-if="teams.length > 0" class="mt-4 rounded-md bg-green-50 p-3">
                        <p class="text-sm text-green-800">{{ teams.length }} team(s) added so far.</p>
                    </div>

                    <form @submit.prevent="submitTeams" class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="t_div" value="Division" />
                            <select id="t_div" v-model="teamsForm.division_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                                <option v-for="div in divisions" :key="div.id" :value="div.id">{{ div.name }}</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel value="Team Names" />
                            <div v-for="(team, i) in teamsForm.teams" :key="i" class="mt-2 flex items-center gap-2">
                                <TextInput v-model="team.name" class="flex-1" :placeholder="`Team ${i + 1}`" required />
                                <button v-if="teamsForm.teams.length > 1" type="button" @click="removeTeam(i)" class="text-sm text-red-500">Remove</button>
                            </div>
                            <button type="button" @click="addTeam" class="mt-2 text-sm text-brand-600 hover:text-brand-700">+ Add another team</button>
                        </div>

                        <div class="flex justify-between">
                            <SecondaryButton @click="activeStep = 5" v-if="teams.length > 0">Finish Setup</SecondaryButton>
                            <PrimaryButton :disabled="teamsForm.processing">Add Teams</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Step 5: Complete -->
                <div v-if="activeStep === 5" class="rounded-lg bg-white p-6 text-center shadow-sm">
                    <h3 class="text-2xl font-bold text-gray-900">You're All Set!</h3>
                    <p class="mt-2 text-gray-600">Your league is ready. Here's what you've set up:</p>
                    <div class="mt-6 grid grid-cols-2 gap-4 text-left sm:grid-cols-4">
                        <div class="rounded-lg bg-gray-50 p-4 text-center">
                            <p class="text-2xl font-bold text-brand-600">{{ seasons.length }}</p>
                            <p class="text-sm text-gray-500">Season(s)</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 text-center">
                            <p class="text-2xl font-bold text-brand-600">{{ locations.length }}</p>
                            <p class="text-sm text-gray-500">Location(s)</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 text-center">
                            <p class="text-2xl font-bold text-brand-600">{{ divisions.length }}</p>
                            <p class="text-sm text-gray-500">Division(s)</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 text-center">
                            <p class="text-2xl font-bold text-brand-600">{{ teams.length }}</p>
                            <p class="text-sm text-gray-500">Team(s)</p>
                        </div>
                    </div>
                    <div class="mt-8">
                        <PrimaryButton @click="completeSetup" class="px-8">
                            Start Scheduling
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
