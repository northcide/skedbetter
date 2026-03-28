<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object, location: Object });

const form = useForm({
    name: props.location.name,
    address: props.location.address || '',
    city: props.location.city || '',
    state: props.location.state || '',
    zip: props.location.zip || '',
    notes: props.location.notes || '',
    is_active: props.location.is_active,
});

const submit = () => {
    form.put(route('leagues.locations.update', [props.league.slug, props.location.id]));
};

const deleteField = (field) => {
    if (confirm(`Delete field "${field.name}"?`)) {
        router.delete(route('leagues.fields.destroy', [props.league.slug, field.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Edit ${location.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Edit {{ location.name }}</h2>
<FlashMessage />

        <div class="mt-4">
            <div class=" space-y-8">
                <!-- Location Form -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Location Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="address" value="Address" />
                            <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel for="city" value="City" />
                                <TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="state" value="State" />
                                <TextInput id="state" v-model="form.state" type="text" class="mt-1 block w-full" maxlength="2" />
                            </div>
                            <div>
                                <InputLabel for="zip" value="ZIP" />
                                <TextInput id="zip" v-model="form.zip" type="text" class="mt-1 block w-full" maxlength="10" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" rows="2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.locations.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Fields -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Fields</h3>
                        <Link :href="route('leagues.locations.fields.create', [league.slug, location.id])">
                            <PrimaryButton type="button">Add Field</PrimaryButton>
                        </Link>
                    </div>

                    <div v-if="!location.fields || location.fields.length === 0" class="p-6 text-center text-gray-500">
                        No fields yet. Add your first field to this location.
                    </div>

                    <ul v-else class="divide-y">
                        <li v-for="field in location.fields" :key="field.id" class="flex items-center justify-between px-6 py-4">
                            <div>
                                <span class="font-medium text-gray-900">{{ field.name }}</span>
                                <span v-if="field.surface_type" class="ml-2 text-sm text-gray-500">{{ field.surface_type }}</span>
                                <span v-if="field.is_lighted" class="ml-2 text-xs text-yellow-600">Lighted</span>
                                <span v-if="!field.is_active" class="ml-2 text-xs text-red-500">Inactive</span>
                            </div>
                            <div class="flex gap-2">
                                <Link :href="route('leagues.fields.edit', [league.slug, field.id])" class="text-sm text-brand-600 hover:text-brand-700">Edit</Link>
                                <button @click="deleteField(field)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
