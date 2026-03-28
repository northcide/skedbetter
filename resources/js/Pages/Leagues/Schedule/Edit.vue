<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    league: Object,
    entry: Object,
    seasons: Array,
    teams: Array,
    fields: Array,
});

const page = usePage();

const form = useForm({
    season_id: props.entry.season_id,
    field_id: props.entry.field_id,
    team_id: props.entry.team_id,
    date: props.entry.date?.split('T')[0] || '',
    start_time: props.entry.start_time?.slice(0, 5) || '',
    end_time: props.entry.end_time?.slice(0, 5) || '',
    type: props.entry.type,
    title: props.entry.title || '',
    notes: props.entry.notes || '',
    status: props.entry.status,
});

const conflictErrors = computed(() => page.props.errors?.conflicts || []);

const submit = () => {
    form.put(route('leagues.schedule.update', [props.league.slug, props.entry.id]));
};

const cancelEntry = () => {
    if (confirm('Cancel this schedule entry?')) {
        router.delete(route('leagues.schedule.destroy', [props.league.slug, props.entry.id]));
    }
};
</script>

<template>
    <Head :title="`Edit Schedule Entry`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit Schedule Entry</h2>
<div class="mt-4">
            <div class="">
                <div v-if="conflictErrors.length" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <h3 class="text-sm font-medium text-red-800">Scheduling conflicts detected:</h3>
                    <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                        <li v-for="error in conflictErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>

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
                                <option v-for="t in teams" :key="t.id" :value="t.id">
                                    {{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="field_id" value="Field" />
                            <select id="field_id" v-model="form.field_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }} @ {{ f.location?.name }}</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="date" value="Date" />
                            <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_time" value="Start Time" />
                                <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="end_time" value="End Time" />
                                <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="confirmed">Confirmed</option>
                                <option value="tentative">Tentative</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="title" value="Title (optional)" />
                            <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" rows="2" />
                        </div>

                        <div class="flex items-center justify-between">
                            <DangerButton v-if="entry.status !== 'cancelled'" type="button" @click="cancelEntry">
                                Cancel Entry
                            </DangerButton>
                            <div class="flex items-center gap-4">
                                <Link :href="route('leagues.schedule.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Back</Link>
                                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
