<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

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

// Build editable state from matrix: field_id => division_id => rule
const rules = ref({});
props.fields.forEach(f => {
    rules.value[f.id] = {};
    props.divisions.forEach(d => {
        const existing = props.matrix[f.id]?.[d.id];
        rules.value[f.id][d.id] = {
            enabled: !!existing,
            max_weekly_slots: existing?.max_weekly_slots || '',
            priority: existing?.priority ? String(existing.priority) : '',
            booking_window_type: existing?.booking_window_type || '',
            booking_opens_date: existing?.booking_opens_date || '',
            booking_opens_days: existing?.booking_opens_days || '',
        };
    });
});

// Shortcut to get rule for current division + field
const r = (fieldId) => rules.value[fieldId]?.[selectedDivisionId.value];

const enabledCount = computed(() => {
    if (!selectedDivisionId.value) return 0;
    return props.fields.filter(f => r(f.id)?.enabled).length;
});

function toggleField(fieldId) {
    const rule = r(fieldId);
    if (rule) rule.enabled = !rule.enabled;
}

function enableAll() {
    props.fields.forEach(f => { if (r(f.id)) r(f.id).enabled = true; });
}
function disableAll() {
    props.fields.forEach(f => {
        if (r(f.id)) {
            r(f.id).enabled = false;
        }
    });
}

function save() {
    saving.value = true;
    const payload = [];
    // Only send current division's rules
    const divId = selectedDivisionId.value;
    props.fields.forEach(f => {
        const rule = rules.value[f.id][divId];
        payload.push({
            field_id: f.id,
            division_id: divId,
            enabled: rule.enabled,
            max_weekly_slots: rule.max_weekly_slots || null,
            priority: rule.priority || null,
            booking_window_type: rule.booking_window_type || null,
            booking_opens_date: rule.booking_opens_date || null,
            booking_opens_days: rule.booking_opens_days || null,
        });
    });

    axios.post(route('leagues.scheduling-rules.update', props.league.slug), { rules: payload }, {
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
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Scheduling Rules</h2>
                <p class="text-[10px] text-gray-400">Pick a division, then configure which fields it can access.</p>
            </div>
            <PrimaryButton @click="save" :disabled="saving" size="sm">{{ saving ? 'Saving...' : 'Save' }}</PrimaryButton>
        </div>

        <FlashMessage />

        <!-- Division Picker + Quick Actions -->
        <div class="mt-3 flex items-center gap-3">
            <select v-model="selectedDivisionId" class="rounded-lg border-gray-300 text-sm font-medium focus:border-brand-500 focus:ring-brand-500">
                <option v-for="d in divisions" :key="d.id" :value="d.id">
                    {{ d.name }} ({{ d.teams_count ?? 0 }} teams)
                </option>
            </select>
            <span v-if="selectedDivision?.scheduling_priority" class="rounded-md bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">
                Default Priority: {{ selectedDivision.scheduling_priority }}
            </span>
            <span v-else class="text-[10px] text-gray-400">No default priority</span>
            <span class="text-xs text-gray-500">
                &middot; {{ enabledCount }} of {{ fields.length }} fields enabled
            </span>
            <div class="ml-auto flex gap-2">
                <button @click="enableAll" class="text-[10px] text-brand-600 hover:text-brand-700 font-medium">Enable all</button>
                <button @click="disableAll" class="text-[10px] text-gray-500 hover:text-gray-700 font-medium">Disable all</button>
            </div>
        </div>

        <!-- Field Cards by Location -->
        <div v-if="selectedDivisionId" class="mt-4 space-y-5">
            <div v-for="loc in fieldsByLocation" :key="loc.name">
                <!-- Location header -->
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ loc.name }}</span>
                    <div class="flex-1 border-t border-gray-200"></div>
                </div>

                <!-- Cards grid -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-for="field in loc.fields" :key="field.id"
                        class="rounded-xl border bg-white transition-all"
                        :class="r(field.id)?.enabled ? 'border-brand-200 shadow-sm' : 'border-gray-100'">

                        <!-- Card header -->
                        <div class="flex items-center justify-between px-3 py-2 cursor-pointer" @click="toggleField(field.id)">
                            <div class="flex items-center gap-2 min-w-0">
                                <input type="checkbox" :checked="r(field.id)?.enabled"
                                    @click.stop="toggleField(field.id)"
                                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                <span class="text-sm font-medium text-gray-900 truncate">{{ field.name }}</span>
                            </div>
                            <span v-if="!r(field.id)?.enabled" class="text-[10px] text-gray-400">Not enabled</span>
                        </div>

                        <!-- Settings (only when enabled) -->
                        <div v-if="r(field.id)?.enabled" class="border-t border-gray-100 px-3 py-2 space-y-2">
                            <!-- Row 1: Priority + Weekly limit -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">
                                        Priority Override
                                        <span v-if="selectedDivision?.scheduling_priority && !r(field.id).priority" class="text-gray-400 font-normal">(using {{ selectedDivision.scheduling_priority }})</span>
                                    </label>
                                    <select v-model="r(field.id).priority"
                                        class="w-full rounded-md border-gray-200 text-xs py-1 px-2 focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">{{ selectedDivision?.scheduling_priority ? 'Use default (' + selectedDivision.scheduling_priority + ')' : 'None' }}</option>
                                        <option value="1">1 (Highest)</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5 (Lowest)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Max / week</label>
                                    <input v-model="r(field.id).max_weekly_slots" type="number" min="1" placeholder="No limit"
                                        class="w-full rounded-md border-gray-200 text-xs py-1 px-2 focus:border-brand-500 focus:ring-brand-500" />
                                </div>
                            </div>

                            <!-- Row 2: Booking window -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Booking Window</label>
                                    <select v-model="r(field.id).booking_window_type"
                                        class="w-full rounded-md border-gray-200 text-xs py-1 px-2 focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">None</option>
                                        <option value="calendar">Opens on date</option>
                                        <option value="rolling">Rolling days ahead</option>
                                    </select>
                                </div>
                                <div v-if="r(field.id).booking_window_type === 'calendar'">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Opens on</label>
                                    <input v-model="r(field.id).booking_opens_date" type="date"
                                        class="w-full rounded-md border-gray-200 text-xs py-1 px-2 focus:border-brand-500 focus:ring-brand-500" />
                                </div>
                                <div v-else-if="r(field.id).booking_window_type === 'rolling'">
                                    <label class="block text-[10px] font-medium text-gray-500 mb-0.5">Days ahead</label>
                                    <select v-model="r(field.id).booking_opens_days"
                                        class="w-full rounded-md border-gray-200 text-xs py-1 px-2 focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">Select</option>
                                        <option v-for="d in [7,14,21,28,45,60,90]" :key="d" :value="d">{{ d }} days</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-gray-500">
                <span><strong>Priority:</strong> 1 = highest, books first when windows overlap. Set default on division, override per-field here</span>
                <span><strong>Max/week:</strong> Slots this division can book per week on this field</span>
                <span><strong>Booking Window:</strong> When this division can start booking this field</span>
            </div>
        </div>
    </LeagueLayout>
</template>
