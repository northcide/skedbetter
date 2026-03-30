<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    divisions: 4,
    teams_per_division: 6,
    locations: 2,
    fields_per_location: 3,
    populate_schedule: true,
    weeks_to_schedule: 4,
});

const totalTeams = computed(() => form.divisions * form.teams_per_division);
const totalFields = computed(() => form.locations * form.fields_per_location);
const estimatedEntries = computed(() => {
    if (!form.populate_schedule) return 0;
    const fields = totalFields.value;
    // ~1.5 entries per field per weekday (4 days) + ~2.5 per field per weekend day (2 days)
    const perWeek = Math.round(fields * 1.5 * 4 + fields * 2.5 * 2);
    return perWeek * form.weeks_to_schedule;
});

const generate = () => {
    if (!confirm(`This will create a demo league with ${form.divisions} divisions, ${totalTeams.value} teams, ${form.locations} locations, and ${totalFields.value} fields. Continue?`)) return;
    form.post(route('admin.demo-league.store'));
};
</script>

<template>
    <Head title="Demo League Generator" />

    <AdminLayout>
        <h2 class="text-base font-semibold text-gray-900">Demo League Generator</h2>
        <p class="mt-1 text-xs text-gray-500">Create a fully populated demo league with realistic data for testing and demonstrations.</p>

        <FlashMessage />

        <div class="mt-4 max-w-lg">
            <form @submit.prevent="generate" class="rounded-lg border border-gray-200 bg-white p-5 space-y-5">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Divisions" class="text-xs" />
                        <input v-model.number="form.divisions" type="number" min="1" max="20"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p class="mt-0.5 text-[10px] text-gray-400">e.g. 6U, 8U, 10U, 12U</p>
                    </div>
                    <div>
                        <InputLabel value="Teams per Division" class="text-xs" />
                        <input v-model.number="form.teams_per_division" type="number" min="1" max="30"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Locations" class="text-xs" />
                        <input v-model.number="form.locations" type="number" min="1" max="10"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p class="mt-0.5 text-[10px] text-gray-400">Parks / complexes</p>
                    </div>
                    <div>
                        <InputLabel value="Fields per Location" class="text-xs" />
                        <input v-model.number="form.fields_per_location" type="number" min="1" max="10"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                </div>

                <!-- Schedule Population -->
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" v-model="form.populate_schedule" class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <div>
                            <span class="text-sm font-medium text-gray-900">Populate schedule with entries</span>
                            <p class="text-[10px] text-gray-400">Fills the calendar with practices (weekdays) and games (weekends). No notifications sent.</p>
                        </div>
                    </label>
                    <div v-if="form.populate_schedule" class="mt-3 pl-6">
                        <InputLabel value="Weeks to schedule" class="text-xs" />
                        <input v-model.number="form.weeks_to_schedule" type="number" min="1" max="12"
                            class="mt-1 block w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p class="mt-0.5 text-[10px] text-gray-400">Starting from this week. ~{{ estimatedEntries }} entries estimated.</p>
                    </div>
                </div>

                <!-- Preview -->
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-semibold text-gray-500">Preview</p>
                    <div class="mt-2 grid grid-cols-4 gap-3 text-center">
                        <div>
                            <p class="text-2xl font-bold text-brand-600">{{ form.divisions }}</p>
                            <p class="text-[10px] text-gray-400">Divisions</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-brand-600">{{ totalTeams }}</p>
                            <p class="text-[10px] text-gray-400">Teams</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-brand-600">{{ form.locations }}</p>
                            <p class="text-[10px] text-gray-400">Locations</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-brand-600">{{ totalFields }}</p>
                            <p class="text-[10px] text-gray-400">Fields</p>
                        </div>
                    </div>
                    <p v-if="form.populate_schedule" class="mt-2 text-center text-xs font-medium text-brand-600">
                        + ~{{ estimatedEntries }} schedule entries over {{ form.weeks_to_schedule }} week{{ form.weeks_to_schedule > 1 ? 's' : '' }}
                    </p>
                    <p class="mt-2 text-[10px] text-gray-400 text-center">
                        Each team gets a random coach name and demo email. League is auto-approved and ready to use.
                        Sport is randomly selected (Baseball, Soccer, Softball, or Lacrosse).
                    </p>
                </div>

                <PrimaryButton :disabled="form.processing" class="w-full justify-center">
                    {{ form.processing ? 'Generating...' : 'Generate Demo League' }}
                </PrimaryButton>
            </form>
        </div>
    </AdminLayout>
</template>
