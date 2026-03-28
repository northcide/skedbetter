<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, location: Object, surfaceTypes: Array });

const form = useForm({
    name: '',
    surface_type: '',
    capacity: '',
    is_lighted: false,
    notes: '',
});

const submit = () => {
    form.post(route('leagues.locations.fields.store', [props.league.slug, props.location.id]));
};
</script>

<template>
    <Head :title="`Add Field - ${location.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Field</h2>
<div class="mt-4">
            <div class="">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-3 p-3">
                        <div>
                            <InputLabel for="name" value="Field Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="surface_type" value="Surface Type" />
                            <select id="surface_type" v-model="form.surface_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">-- Select --</option>
                                <option v-for="st in surfaceTypes" :key="st" :value="st">{{ st.charAt(0).toUpperCase() + st.slice(1) }}</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="capacity" value="Capacity" />
                            <TextInput id="capacity" v-model="form.capacity" type="number" class="mt-1 block w-full" min="0" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="is_lighted" v-model:checked="form.is_lighted" />
                            <InputLabel for="is_lighted" value="Lighted" />
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" rows="2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.locations.edit', [league.slug, location.id])" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Add Field</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
