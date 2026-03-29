<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object,
    userRole: String,
});

const importType = ref('teams');
const file = ref(null);
const step = ref('upload'); // upload | preview | results
const loading = ref(false);
const error = ref('');

// Preview data
const previewRows = ref([]);
const previewHeaders = ref([]);
const validCount = ref(0);
const errorCount = ref(0);

// Results data
const results = ref({ created: 0, skipped: 0, errors: [] });

const typeLabels = {
    divisions: 'Divisions',
    teams: 'Teams',
    locations: 'Locations & Fields',
    schedule: 'Schedule Entries',
};

const typeDescriptions = {
    divisions: 'Import division names, age groups, and skill levels.',
    teams: 'Import teams with division assignment, coach info, and colors.',
    locations: 'Import locations and fields. New locations are created automatically.',
    schedule: 'Import schedule entries with conflict checking.',
};

function onFileChange(e) {
    file.value = e.target.files[0] || null;
    error.value = '';
}

async function preview() {
    if (!file.value) return;
    loading.value = true;
    error.value = '';

    const formData = new FormData();
    formData.append('type', importType.value);
    formData.append('file', file.value);

    try {
        const res = await axios.post(route('leagues.data-import.preview', props.league.slug), formData);
        previewRows.value = res.data.rows;
        previewHeaders.value = res.data.headers;
        validCount.value = res.data.validCount;
        errorCount.value = res.data.errorCount;
        step.value = 'preview';
    } catch (e) {
        error.value = e.response?.data?.error || e.response?.data?.message || 'Failed to parse CSV.';
    } finally {
        loading.value = false;
    }
}

async function doImport() {
    if (!file.value) return;
    loading.value = true;

    const formData = new FormData();
    formData.append('type', importType.value);
    formData.append('file', file.value);

    try {
        const res = await axios.post(route('leagues.data-import.import', props.league.slug), formData);
        results.value = res.data;
        step.value = 'results';
    } catch (e) {
        error.value = e.response?.data?.error || 'Import failed.';
    } finally {
        loading.value = false;
    }
}

function reset() {
    step.value = 'upload';
    file.value = null;
    previewRows.value = [];
    previewHeaders.value = [];
    validCount.value = 0;
    errorCount.value = 0;
    results.value = { created: 0, skipped: 0, errors: [] };
    error.value = '';
    // Reset file input
    const input = document.getElementById('csv_file');
    if (input) input.value = '';
}

const doneRoute = computed(() => ({
    divisions: 'leagues.divisions.index',
    teams: 'leagues.divisions.index',
    locations: 'leagues.locations.index',
    schedule: 'leagues.schedule.index',
}[importType.value]));
</script>

<template>
    <Head :title="`${league.name} - Import Data`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <h2 class="text-base font-semibold text-gray-900">Import Data</h2>

        <FlashMessage />

        <div class="mt-3 max-w-3xl">
            <!-- Step 1: Upload -->
            <div v-if="step === 'upload'" class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">What are you importing?</label>
                        <select v-model="importType" class="mt-1 block w-full sm:w-64">
                            <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p class="mt-1 text-[11px] text-gray-400">{{ typeDescriptions[importType] }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">CSV File</label>
                        <input id="csv_file" type="file" accept=".csv,.txt" @change="onFileChange"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                    </div>

                    <div class="flex items-center justify-between">
                        <a :href="route('leagues.data-import.template', [league.slug, importType])"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700">
                            Download {{ typeLabels[importType] }} template
                        </a>
                        <PrimaryButton @click="preview" :disabled="!file || loading">
                            {{ loading ? 'Parsing...' : 'Preview' }}
                        </PrimaryButton>
                    </div>

                    <div v-if="error" class="rounded-md bg-red-50 p-3 text-xs text-red-700">{{ error }}</div>
                </div>
            </div>

            <!-- Step 2: Preview -->
            <div v-if="step === 'preview'">
                <!-- Summary bar -->
                <div class="flex items-center justify-between rounded-t-lg border border-gray-200 bg-gray-50 px-4 py-2">
                    <div class="flex items-center gap-3 text-xs">
                        <span class="font-semibold text-gray-700">{{ typeLabels[importType] }}</span>
                        <span class="text-green-700">{{ validCount }} valid</span>
                        <span v-if="errorCount" class="text-red-600">{{ errorCount }} errors</span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="reset" class="rounded-md px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900">Back</button>
                        <PrimaryButton @click="doImport" :disabled="validCount === 0 || loading" size="sm">
                            {{ loading ? 'Importing...' : `Import ${validCount} rows` }}
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Preview table -->
                <div class="overflow-x-auto rounded-b-lg border border-t-0 border-gray-200 bg-white">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-[10px] font-semibold uppercase text-gray-400 w-8"></th>
                                <th v-for="h in previewHeaders" :key="h" class="px-3 py-2 text-left text-[10px] font-semibold uppercase text-gray-400">{{ h }}</th>
                                <th class="px-3 py-2 text-left text-[10px] font-semibold uppercase text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(row, idx) in previewRows" :key="idx"
                                :class="row.valid ? '' : 'bg-red-50'">
                                <td class="px-3 py-1.5 text-gray-400">{{ idx + 1 }}</td>
                                <td v-for="h in previewHeaders" :key="h" class="px-3 py-1.5"
                                    :class="row.valid ? 'text-gray-700' : 'text-red-700'">
                                    {{ row.data[h] || '' }}
                                </td>
                                <td class="px-3 py-1.5">
                                    <span v-if="row.valid" class="text-green-600">OK</span>
                                    <span v-else class="text-red-600 text-[10px]">{{ row.errors.join('; ') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="error" class="mt-2 rounded-md bg-red-50 p-3 text-xs text-red-700">{{ error }}</div>
            </div>

            <!-- Step 3: Results -->
            <div v-if="step === 'results'" class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Import Complete</h3>

                <div class="mt-3 flex gap-4">
                    <div class="rounded-lg bg-green-50 px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-green-700">{{ results.created }}</p>
                        <p class="text-xs text-green-600">Created</p>
                    </div>
                    <div v-if="results.skipped" class="rounded-lg bg-red-50 px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-red-700">{{ results.skipped }}</p>
                        <p class="text-xs text-red-600">Skipped</p>
                    </div>
                </div>

                <div v-if="results.errors?.length" class="mt-3 rounded-md border border-red-100 bg-red-50 p-3">
                    <p class="text-xs font-semibold text-red-700">Errors:</p>
                    <ul class="mt-1 space-y-0.5 text-[11px] text-red-600">
                        <li v-for="(err, i) in results.errors" :key="i">Row {{ err.row }}: {{ err.message }}</li>
                    </ul>
                </div>

                <div class="mt-4 flex gap-2">
                    <button @click="reset" class="rounded-md border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Import More</button>
                    <a :href="route(doneRoute, league.slug)"
                        class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Done</a>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
