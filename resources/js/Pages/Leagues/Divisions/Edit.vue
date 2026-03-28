<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, division: Object, seasons: Array });

const form = useForm({
    name: props.division.name,
    season_id: props.division.season_id,
    age_group: props.division.age_group || '',
    skill_level: props.division.skill_level || '',
    max_event_minutes: props.division.max_event_minutes || '',
    max_weekly_events_per_team: props.division.max_weekly_events_per_team || '',
});

const submit = () => {
    form.put(route('leagues.divisions.update', [props.league.slug, props.division.id]));
};
</script>

<template>
    <Head :title="`Edit Division - ${division.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit {{ division.name }}</h2>
<div class="mt-4">
            <div class="">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-3 p-3">
                        <div>
                            <InputLabel for="name" value="Division Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="season_id" value="Season" />
                            <select id="season_id" v-model="form.season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="season in seasons" :key="season.id" :value="season.id">
                                    {{ season.name }}{{ season.is_current ? ' (Current)' : '' }}
                                </option>
                            </select>
                            <InputError :message="form.errors.season_id" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="age_group" value="Age Group" />
                                <TextInput id="age_group" v-model="form.age_group" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="skill_level" value="Skill Level" />
                                <TextInput id="skill_level" v-model="form.skill_level" type="text" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="max_event_minutes" value="Max Event Duration" />
                                <select id="max_event_minutes" v-model="form.max_event_minutes" class="mt-1 block w-full">
                                    <option value="">No limit</option>
                                    <option value="30">30 minutes</option>
                                    <option value="60">60 minutes</option>
                                    <option value="90">90 minutes</option>
                                    <option value="120">120 minutes</option>
                                </select>
                                <InputError :message="form.errors.max_event_minutes" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="max_weekly_events_per_team" value="Max Events Per Team/Week" />
                                <select id="max_weekly_events_per_team" v-model="form.max_weekly_events_per_team" class="mt-1 block w-full">
                                    <option value="">No limit</option>
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                                <InputError :message="form.errors.max_weekly_events_per_team" class="mt-2" />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">These limits apply to all teams in this division. A team's own limit overrides the division limit.</p>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.divisions.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
