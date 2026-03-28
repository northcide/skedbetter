<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, divisions: Array });

const form = useForm({
    division_id: props.divisions[0]?.id || '',
    csv_file: null,
});

const submit = () => {
    form.post(route('leagues.teams.import.store', props.league.slug), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`${league.name} - Import Teams`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('leagues.teams.index', league.slug)" class="text-sm text-brand-600 hover:text-brand-700">&larr; Teams</Link>
            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Import Teams from CSV</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6 p-6">
                        <div>
                            <InputLabel for="division_id" value="Import into Division" />
                            <select id="division_id" v-model="form.division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option v-for="div in divisions" :key="div.id" :value="div.id">
                                    {{ div.name }} ({{ div.season?.name }})
                                </option>
                            </select>
                            <InputError :message="form.errors.division_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="csv_file" value="CSV File" />
                            <input
                                id="csv_file"
                                type="file"
                                accept=".csv,.txt"
                                @input="form.csv_file = $event.target.files[0]"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
                            />
                            <InputError :message="form.errors.csv_file" class="mt-2" />
                        </div>

                        <div class="rounded-md bg-gray-50 p-4">
                            <h4 class="text-sm font-medium text-gray-700">CSV Format</h4>
                            <p class="mt-1 text-xs text-gray-500">Required column: <code>name</code></p>
                            <p class="text-xs text-gray-500">Optional: <code>contact_name</code>, <code>contact_email</code>, <code>contact_phone</code>, <code>color</code></p>
                            <pre class="mt-2 rounded bg-gray-100 p-2 text-xs text-gray-600">name,contact_name,contact_email
Eagles,John Smith,john@example.com
Hawks,Jane Doe,jane@example.com</pre>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.teams.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :disabled="form.processing || !form.csv_file">Import Teams</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
