<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
});

const form = useForm({
    name: props.league.name,
    description: props.league.description || '',
    timezone: props.league.timezone,
    contact_email: props.league.contact_email || '',
});

const submit = () => {
    form.put(route('leagues.update', props.league.slug));
};
</script>

<template>
    <Head :title="`Edit ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Edit {{ league.name }}
                    </h2>
<div class="mt-4">
            <div class="">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="League Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                rows="3"
                            />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="timezone" value="Timezone" />
                            <select
                                id="timezone"
                                v-model="form.timezone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="America/New_York">Eastern (America/New_York)</option>
                                <option value="America/Chicago">Central (America/Chicago)</option>
                                <option value="America/Denver">Mountain (America/Denver)</option>
                                <option value="America/Los_Angeles">Pacific (America/Los_Angeles)</option>
                                <option value="America/Anchorage">Alaska</option>
                                <option value="Pacific/Honolulu">Hawaii</option>
                            </select>
                            <InputError :message="form.errors.timezone" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="contact_email" value="Contact Email" />
                            <TextInput id="contact_email" v-model="form.contact_email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.contact_email" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.show', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
