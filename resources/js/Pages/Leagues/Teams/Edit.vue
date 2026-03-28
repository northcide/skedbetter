<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, team: Object, divisions: Array });

const form = useForm({
    name: props.team.name,
    division_id: props.team.division_id,
    color_code: props.team.color_code || '#3B82F6',
    contact_name: props.team.contact_name || '',
    contact_email: props.team.contact_email || '',
    contact_phone: props.team.contact_phone || '',
    max_weekly_slots: props.team.max_weekly_slots || '',
});

const submit = () => {
    form.put(route('leagues.teams.update', [props.league.slug, props.team.id]));
};
</script>

<template>
    <Head :title="`Edit Team - ${team.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('leagues.teams.index', league.slug)" class="text-sm text-brand-600 hover:text-brand-700">&larr; Teams</Link>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit {{ team.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Team Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
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

                        <div>
                            <InputLabel for="max_weekly_slots" value="Max Weekly Slots (override)" />
                            <TextInput id="max_weekly_slots" v-model="form.max_weekly_slots" type="number" class="mt-1 block w-full" min="1" placeholder="Leave blank to use league default" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.teams.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
