<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object,
    divisions: Array,
    fields: Array,
    fieldsByLocation: Array,
    matrix: Object,
    userRole: String,
});

const saving = ref(false);
const selectedDivisionId = ref(props.divisions[0]?.id || null);

const selectedDivision = computed(() =>
    props.divisions.find(d => d.id === selectedDivisionId.value)
);

const divMaxWeekly = ref({});
const divPriority = ref({});
props.divisions.forEach(d => {
    divMaxWeekly.value[d.id] = d.max_weekly_events_per_team || '';
    divPriority.value[d.id] = d.scheduling_priority ? String(d.scheduling_priority) : '';
});

const rules = ref({});
props.fields.forEach(f => {
    rules.value[f.id] = {};
    props.divisions.forEach(d => {
        const existing = props.matrix[f.id]?.[d.id];
        rules.value[f.id][d.id] = {
            enabled: !!existing,
            priority: existing?.priority ? String(existing.priority) : '',
            booking_window_type: existing?.booking_window_type || '',
            booking_opens_date: existing?.booking_opens_date || '',
            booking_opens_days: existing?.booking_opens_days || '',
        };
    });
});

const r = (fieldId) => rules.value[fieldId]?.[selectedDivisionId.value];

const enabledCount = computed(() => {
    if (!selectedDivisionId.value) return 0;
    return props.fields.filter(f => r(f.id)?.enabled).length;
});

const divEnabledCount = (divId) => {
    return props.fields.filter(f => rules.value[f.id]?.[divId]?.enabled).length;
};

function toggleField(fieldId) {
    const rule = r(fieldId);
    if (rule) rule.enabled = !rule.enabled;
}

function enableAll() {
    props.fields.forEach(f => { if (r(f.id)) r(f.id).enabled = true; });
}
function disableAll() {
    props.fields.forEach(f => { if (r(f.id)) r(f.id).enabled = false; });
}

function save() {
    saving.value = true;
    const divId = selectedDivisionId.value;
    const payload = [];
    props.fields.forEach(f => {
        const rule = rules.value[f.id][divId];
        payload.push({
            field_id: f.id,
            division_id: divId,
            enabled: rule.enabled,
            priority: rule.priority || null,
            booking_window_type: rule.booking_window_type || null,
            booking_opens_date: rule.booking_opens_date || null,
            booking_opens_days: rule.booking_opens_days || null,
        });
    });

    axios.post(route('leagues.scheduling-rules.update', props.league.slug), {
        rules: payload,
        division_id: divId,
        scheduling_priority: divPriority.value[divId] || null,
        max_weekly_events_per_team: divMaxWeekly.value[divId] || null,
    }, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        saving.value = false;
    }).catch(() => {
        saving.value = false;
    });
}
</script>

<template>
    <Head :title="`${league.name} - Scheduling Rules`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Scheduling Rules</h2>
            <PrimaryButton @click="save" :disabled="saving" size="sm">{{ saving ? 'Saving...' : 'Save' }}</PrimaryButton>
        </div>

        <FlashMessage />

        <!-- Main layout: side tabs + content -->
        <div class="mt-3 flex items-start">
            <!-- Division tabs (left side, file-folder style) -->
            <nav class="w-40 shrink-0 -mr-px relative z-10 pt-1">
                <button v-for="d in divisions" :key="d.id"
                    @click="selectedDivisionId = d.id"
                    class="relative flex w-full items-center justify-between pl-3 pr-2 py-2 text-left text-xs transition-colors border border-gray-200"
                    :class="selectedDivisionId === d.id
                        ? 'bg-white font-semibold text-brand-700 border-r-white rounded-l-lg z-20'
                        : 'bg-gray-50 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-l-md -my-px'">
                    <span class="truncate">{{ d.name }}</span>
                    <span class="ml-1 shrink-0 rounded-full px-1.5 py-px text-[9px] tabular-nums"
                        :class="selectedDivisionId === d.id ? 'bg-brand-50 text-brand-600' : 'text-gray-400'">
                        {{ divEnabledCount(d.id) }}
                    </span>
                </button>
            </nav>

            <!-- Right content (the "folder" body) -->
            <div v-if="selectedDivisionId" class="min-w-0 flex-1 rounded-lg rounded-tl-none border border-gray-200 bg-white">
                <!-- Division settings bar -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-gray-100 px-3 py-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-gray-500">Priority:</span>
                        <select v-model="divPriority[selectedDivisionId]" class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px] focus:border-brand-500 focus:ring-brand-500">
                            <option value="">None</option>
                            <option value="1">1 (Highest)</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5 (Lowest)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-gray-500">Max/wk:</span>
                        <select v-model="divMaxWeekly[selectedDivisionId]" class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px] focus:border-brand-500 focus:ring-brand-500">
                            <option value="">none</option>
                            <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>
                    <div class="ml-auto flex gap-2">
                        <button @click="enableAll" class="text-[10px] text-brand-600 hover:text-brand-700 font-medium">All on</button>
                        <button @click="disableAll" class="text-[10px] text-gray-500 hover:text-gray-700 font-medium">All off</button>
                    </div>
                </div>

                <!-- Field rows grouped by location -->
                <div class="divide-y divide-gray-100">
                    <template v-for="loc in fieldsByLocation" :key="loc.name">
                        <div class="bg-gray-50 px-3 py-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ loc.name }}</span>
                        </div>

                        <div v-for="field in loc.fields" :key="field.id"
                            class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50/50 transition-colors"
                            :class="{ 'opacity-40': !r(field.id)?.enabled }">

                            <label class="flex items-center gap-1.5 min-w-0 shrink-0" style="width: 160px">
                                <input type="checkbox" :checked="r(field.id)?.enabled"
                                    @change="toggleField(field.id)"
                                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                <span class="text-xs font-medium text-gray-900 truncate">{{ field.name }}</span>
                            </label>

                            <template v-if="r(field.id)?.enabled">
                                <select v-model="r(field.id).priority" title="Priority override"
                                    class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px] focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">{{ divPriority[selectedDivisionId] ? 'Pri ' + divPriority[selectedDivisionId] : 'Pri -' }}</option>
                                    <option value="1">Pri 1</option>
                                    <option value="2">Pri 2</option>
                                    <option value="3">Pri 3</option>
                                    <option value="4">Pri 4</option>
                                    <option value="5">Pri 5</option>
                                </select>

                                <select v-model="r(field.id).booking_window_type" title="Booking window"
                                    class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px] focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">No window</option>
                                    <option value="calendar">Opens on date</option>
                                    <option value="rolling">Rolling days</option>
                                </select>

                                <input v-if="r(field.id).booking_window_type === 'calendar'"
                                    v-model="r(field.id).booking_opens_date" type="date" title="Opens on date"
                                    class="rounded border-gray-200 py-0.5 px-1.5 text-[11px] focus:border-brand-500 focus:ring-brand-500" />
                                <select v-else-if="r(field.id).booking_window_type === 'rolling'"
                                    v-model="r(field.id).booking_opens_days" title="Days ahead"
                                    class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px] focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">Days?</option>
                                    <option v-for="d in [7,14,21,28,45,60,90]" :key="d" :value="d">{{ d }}d</option>
                                </select>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Legend -->
                <div class="border-t border-gray-100 px-3 py-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-[10px] text-gray-400">
                    <span><strong>Pri:</strong> 1=highest, default from division unless overridden</span>
                    <span><strong>Max/wk:</strong> total events per team per week across all fields</span>
                    <span><strong>Window:</strong> when this division can start booking a field</span>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
