<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, divisions: Array });

const form = useForm({
    name: '',
    division_id: props.divisions[0]?.id || '',
    color_code: '#3B82F6',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
});

const submit = () => {
    form.post(route('leagues.teams.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Add Team`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Team</h2>
<div class="mt-4">
            <div class="">
                <div v-if="divisions.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">You need to create a division before adding teams.</p>
                    <Link :href="route('leagues.divisions.create', league.slug)" class="mt-4 inline-block text-brand-600 hover:text-brand-700">Create a Division</Link>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-3 p-3">
                        <div>
                            <InputLabel for="name" value="Team Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="division_id" value="Division" />
                            <select id="division_id" v-model="form.division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="div in divisions" :key="div.id" :value="div.id">
                                    {{ div.name }} ({{ div.season?.name }})
                                </option>
                            </select>
                            <InputError :message="form.errors.division_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="color_code" value="Team Color" />
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color" id="color_code" v-model="form.color_code" class="h-10 w-14 cursor-pointer rounded border border-gray-300" />
                                <TextInput v-model="form.color_code" type="text" class="block w-24" maxlength="7" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="contact_name" value="Contact Name" />
                            <TextInput id="contact_name" v-model="form.contact_name" type="text" class="mt-1 block w-full" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="contact_email" value="Contact Email" />
                                <TextInput id="contact_email" v-model="form.contact_email" type="email" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="contact_phone" value="Contact Phone" />
                                <TextInput id="contact_phone" v-model="form.contact_phone" type="text" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.teams.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Add Team</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
