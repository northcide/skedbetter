<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    league: Object,
    seasons: Array,
    teams: Array,
    fields: Array,
});

const page = usePage();

// Pre-fill from query params (from calendar click)
const urlParams = new URLSearchParams(window.location.search);

const form = useForm({
    season_id: props.seasons.find(s => s.is_current)?.id || props.seasons[0]?.id || '',
    field_id: urlParams.get('field_id') || '',
    team_id: '',
    date: urlParams.get('date') || '',
    start_time: urlParams.get('start_time') || '',
    end_time: urlParams.get('end_time') || '',
    type: 'practice',
    title: '',
    notes: '',
});

const conflictErrors = computed(() => page.props.errors?.conflicts || []);

const submit = () => {
    form.post(route('leagues.schedule.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - New Schedule Entry`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">New Schedule Entry</h2>
<div class="mt-4">
            <div class="">
                <!-- Conflict Errors -->
                <div v-if="conflictErrors.length" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <h3 class="text-sm font-medium text-red-800">Scheduling conflicts detected:</h3>
                    <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                        <li v-for="error in conflictErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-3 p-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="season_id" value="Season" />
                                <select id="season_id" v-model="form.season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                    <option v-for="s in seasons" :key="s.id" :value="s.id">
                                        {{ s.name }}{{ s.is_current ? ' (Current)' : '' }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.season_id" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="type" value="Type" />
                                <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="practice">Practice</option>
                                    <option value="game">Game</option>
                                    <option value="scrimmage">Scrimmage</option>
                                    <option value="tournament">Tournament</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="team_id" value="Team" />
                            <select id="team_id" v-model="form.team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option value="">Select team</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">
                                    {{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}
                                </option>
                            </select>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="field_id" value="Field" />
                            <select id="field_id" v-model="form.field_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option value="">Select field</option>
                                <option v-for="f in fields" :key="f.id" :value="f.id">
                                    {{ f.name }} @ {{ f.location?.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.field_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="date" value="Date" />
                            <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_time" value="Start Time" />
                                <TextInput id="start_time" v-model="form.start_time" type="time" step="1800" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.start_time" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="end_time" value="End Time" />
                                <TextInput id="end_time" v-model="form.end_time" type="time" step="1800" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.end_time" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="title" value="Title (optional)" />
                            <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" placeholder="e.g. Playoff Game vs Eagles" />
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" rows="2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.schedule.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Create Entry</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
