<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object });

const form = useForm({
    name: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    notes: '',
});

const submit = () => {
    form.post(route('leagues.locations.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Add Location`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Location</h2>
<div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Location Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="address" value="Address" />
                            <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.address" class="mt-2" />
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
                            <PrimaryButton :disabled="form.processing">Add Location</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
