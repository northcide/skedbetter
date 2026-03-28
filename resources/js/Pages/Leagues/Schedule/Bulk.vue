<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    seasons: Array,
    teams: Array,
    fields: Array,
});

const form = useForm({
    season_id: props.seasons.find(s => s.is_current)?.id || props.seasons[0]?.id || '',
    field_id: '',
    team_id: '',
    date: '',
    start_time: '',
    end_time: '',
    type: 'practice',
    title: '',
    frequency: 'weekly',
    until: '',
});

const submit = () => {
    form.post(route('leagues.schedule.bulk.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Bulk Schedule`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Bulk Schedule</h2>
                    <p class="mt-1 text-sm text-gray-500">Create a recurring schedule entry for a team on a specific field.</p>
<div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="season_id" value="Season" />
                                <select id="season_id" v-model="form.season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                    <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
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
                                <option value="">-- Select Team --</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">
                                    {{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}
                                </option>
                            </select>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="field_id" value="Field" />
                            <select id="field_id" v-model="form.field_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option value="">-- Select Field --</option>
                                <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }} @ {{ f.location?.name }}</option>
                            </select>
                            <InputError :message="form.errors.field_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="date" value="First Date" />
                            <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_time" value="Start Time" />
                                <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.start_time" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="end_time" value="End Time" />
                                <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.end_time" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="title" value="Title (optional)" />
                            <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" />
                        </div>

                        <hr class="border-gray-200" />

                        <h3 class="text-sm font-medium text-gray-700">Recurrence</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="frequency" value="Repeat" />
                                <select id="frequency" v-model="form.frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="weekly">Every week</option>
                                    <option value="biweekly">Every 2 weeks</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel for="until" value="Until (last date)" />
                                <TextInput id="until" v-model="form.until" type="date" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.until" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.schedule.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Create Recurring Schedule</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
