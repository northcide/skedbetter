<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, field: Object, surfaceTypes: Array });

const form = useForm({
    name: props.field.name,
    surface_type: props.field.surface_type || '',
    capacity: props.field.capacity || '',
    is_lighted: props.field.is_lighted,
    is_active: props.field.is_active,
    notes: props.field.notes || '',
});

const submit = () => {
    form.put(route('leagues.fields.update', [props.league.slug, props.field.id]));
};
</script>

<template>
    <Head :title="`Edit Field - ${field.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('leagues.locations.edit', [league.slug, field.location.id])" class="text-sm text-brand-600 hover:text-brand-700">&larr; {{ field.location.name }}</Link>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit {{ field.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Field Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
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

                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <Checkbox id="is_lighted" v-model:checked="form.is_lighted" />
                                <InputLabel for="is_lighted" value="Lighted" />
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="is_active" v-model:checked="form.is_active" />
                                <InputLabel for="is_active" value="Active" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" rows="2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.locations.edit', [league.slug, field.location.id])" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
