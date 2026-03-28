<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ league: Object, locations: Array });

const form = useForm({
    name: '',
    reason: '',
    scope_type: 'league',
    scope_id: props.league.id,
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    recurrence: 'none',
    day_of_week: '',
});

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const allFields = computed(() => {
    return props.locations.flatMap(loc =>
        (loc.fields || []).map(f => ({ ...f, location_name: loc.name }))
    );
});

const submit = () => {
    if (form.scope_type === 'league') {
        form.scope_id = props.league.id;
    }
    form.post(route('leagues.blackouts.store', props.league.slug));
};
</script>

<template>
    <Head :title="`${league.name} - Add Blackout Rule`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('leagues.blackouts.index', league.slug)" class="text-sm text-brand-600 hover:text-brand-700">&larr; Blackout Rules</Link>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Add Blackout Rule</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="name" value="Rule Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. Thanksgiving Break" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="reason" value="Reason" />
                            <TextInput id="reason" v-model="form.reason" type="text" class="mt-1 block w-full" placeholder="e.g. Holiday closure" />
                        </div>

                        <div>
                            <InputLabel for="scope_type" value="Applies To" />
                            <select id="scope_type" v-model="form.scope_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="league">Entire League</option>
                                <option value="location">Specific Location</option>
                                <option value="field">Specific Field</option>
                            </select>
                        </div>

                        <div v-if="form.scope_type === 'location'">
                            <InputLabel for="scope_id" value="Location" />
                            <select id="scope_id" v-model="form.scope_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                        </div>

                        <div v-if="form.scope_type === 'field'">
                            <InputLabel for="scope_id" value="Field" />
                            <select id="scope_id" v-model="form.scope_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="f in allFields" :key="f.id" :value="f.id">{{ f.name }} @ {{ f.location_name }}</option>
                            </select>
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_time" value="Start Time (optional - blank = all day)" />
                                <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="end_time" value="End Time" />
                                <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="recurrence" value="Recurrence" />
                            <select id="recurrence" v-model="form.recurrence" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="none">None (one-time)</option>
                                <option value="weekly">Weekly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div v-if="form.recurrence === 'weekly'">
                            <InputLabel for="day_of_week" value="Day of Week" />
                            <select id="day_of_week" v-model="form.day_of_week" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="(name, i) in dayNames" :key="i" :value="i">{{ name }}</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.blackouts.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Add Blackout Rule</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
