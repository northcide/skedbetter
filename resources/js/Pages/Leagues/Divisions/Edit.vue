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
<div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
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
