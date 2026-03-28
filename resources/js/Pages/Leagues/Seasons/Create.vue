<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object });

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

const submit = () => {
    form.post(route('leagues.seasons.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Add Season`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Season</h2>
<div class="mt-4">
            <div class="">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Season Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. Spring 2026" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_date" value="Start Date" />
                                <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.start_date" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="end_date" value="End Date" />
                                <TextInput id="end_date" v-model="form.end_date" type="date" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.end_date" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="is_current" v-model:checked="form.is_current" />
                            <InputLabel for="is_current" value="Set as current season" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.seasons.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Add Season</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
