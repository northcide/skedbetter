<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, seasons: Array });

const form = useForm({
    name: '',
    season_id: props.seasons.find(s => s.is_current)?.id || props.seasons[0]?.id || '',
    age_group: '',
    skill_level: '',
});

const submit = () => {
    form.post(route('leagues.divisions.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Add Division`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('leagues.divisions.index', league.slug)" class="text-sm text-brand-600 hover:text-brand-700">&larr; Divisions</Link>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Division</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div v-if="seasons.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">You need to create a season before adding divisions.</p>
                    <Link :href="route('leagues.seasons.create', league.slug)" class="mt-4 inline-block text-brand-600 hover:text-brand-700">Create a Season</Link>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Division Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. U10 Boys" />
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
                                <TextInput id="age_group" v-model="form.age_group" type="text" class="mt-1 block w-full" placeholder="e.g. U10, U12, Adult" />
                            </div>
                            <div>
                                <InputLabel for="skill_level" value="Skill Level" />
                                <TextInput id="skill_level" v-model="form.skill_level" type="text" class="mt-1 block w-full" placeholder="e.g. Recreational, Competitive" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.divisions.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Add Division</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
