<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, season: Object });

const form = useForm({
    name: props.season.name,
    start_date: props.season.start_date?.split('T')[0] || '',
    end_date: props.season.end_date?.split('T')[0] || '',
    is_current: props.season.is_current,
});

const submit = () => {
    form.put(route('leagues.seasons.update', [props.league.slug, props.season.id]));
};
</script>

<template>
    <Head :title="`Edit Season - ${season.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit {{ season.name }}</h2>
<div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Season Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
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
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
