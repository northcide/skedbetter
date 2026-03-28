<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object, divisions: Array, fields: Array, matrix: Object, userRole: String,
});

const saving = ref(false);
const expandedField = ref(null);
const toggleField = (id) => { expandedField.value = expandedField.value === id ? null : id; };

// Build editable state from matrix
const rules = ref({});
props.fields.forEach(f => {
    rules.value[f.id] = {};
    props.divisions.forEach(d => {
        const existing = props.matrix[f.id]?.[d.id];
        rules.value[f.id][d.id] = {
            enabled: !!existing,
            max_weekly_slots: existing?.max_weekly_slots || '',
            priority: existing?.priority || '',
            booking_window_type: existing?.booking_window_type || '',
            booking_opens_date: existing?.booking_opens_date || '',
            booking_opens_days: existing?.booking_opens_days || '',
        };
    });
});

const hasRestrictions = (fieldId) => {
    return Object.values(rules.value[fieldId] || {}).some(r => r.enabled);
};

const enabledCount = (fieldId) => {
    return Object.values(rules.value[fieldId] || {}).filter(r => r.enabled).length;
};

const getDivName = (id) => props.divisions.find(d => d.id === id)?.name || '';

function save() {
    saving.value = true;
    const payload = [];
    Object.entries(rules.value).forEach(([fieldId, divs]) => {
        Object.entries(divs).forEach(([divId, rule]) => {
            payload.push({
                field_id: parseInt(fieldId),
                division_id: parseInt(divId),
                enabled: rule.enabled,
                max_weekly_slots: rule.max_weekly_slots || null,
                priority: rule.priority || null,
                booking_window_type: rule.booking_window_type || null,
                booking_opens_date: rule.booking_opens_date || null,
                booking_opens_days: rule.booking_opens_days || null,
            });
        });
    });

    axios.post(route('leagues.scheduling-rules.update', props.league.slug), { rules: payload }, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        window.location.reload();
    }).catch(() => {
        saving.value = false;
    });
}
</script>

<template>
    <Head :title="`${league.name} - Scheduling Rules`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Scheduling Rules</h2>
                <p class="text-[10px] text-gray-400">Manage which divisions can use which fields, priorities, booking windows, and weekly limits — all in one place.</p>
            </div>
            <PrimaryButton @click="save" :disabled="saving">{{ saving ? 'Saving...' : 'Save All' }}</PrimaryButton>
        </div>

        <FlashMessage />

        <div class="mt-3 space-y-px rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
            <div v-for="field in fields" :key="field.id">
                <!-- Field row -->
                <div class="flex items-center justify-between px-3 py-2 hover:bg-gray-50 cursor-pointer" @click="toggleField(field.id)">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': expandedField === field.id }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-xs font-medium text-gray-900">{{ field.name }}</span>
                        <span class="text-[10px] text-gray-400">{{ field.location?.name }}</span>
                    </div>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                        :class="hasRestrictions(field.id) ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'">
                        {{ hasRestrictions(field.id) ? enabledCount(field.id) + ' divisions' : 'Open' }}
                    </span>
                </div>

                <!-- Expanded: division rules grid -->
                <div v-if="expandedField === field.id" class="bg-gray-50 px-3 py-2">
                    <!-- Header -->
                    <div class="grid grid-cols-[1fr_40px_40px_45px_70px_90px] gap-1 text-[9px] font-semibold text-gray-400 mb-1 pl-5">
                        <span>Division</span><span>On</span><span>Pri</span><span>/wk</span><span>Window</span><span>Opens</span>
                    </div>

                    <div v-for="div in divisions" :key="div.id" class="grid grid-cols-[1fr_40px_40px_45px_70px_90px] gap-1 items-center py-0.5 pl-5">
                        <span class="text-[11px] text-gray-700 truncate">{{ div.name }}</span>

                        <!-- Enabled toggle -->
                        <label class="flex justify-center">
                            <input type="checkbox" v-model="rules[field.id][div.id].enabled" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        </label>

                        <!-- Priority -->
                        <select v-model="rules[field.id][div.id].priority" :disabled="!rules[field.id][div.id].enabled" class="text-center disabled:opacity-30">
                            <option value="">-</option>
                            <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                        </select>

                        <!-- Weekly -->
                        <input v-model="rules[field.id][div.id].max_weekly_slots" type="number" min="1" placeholder="∞"
                            :disabled="!rules[field.id][div.id].enabled"
                            class="rounded border-gray-200 px-1 py-0.5 text-[10px] text-center disabled:opacity-30" />

                        <!-- Window type -->
                        <select v-model="rules[field.id][div.id].booking_window_type" :disabled="!rules[field.id][div.id].enabled" class="disabled:opacity-30">
                            <option value="">None</option>
                            <option value="calendar">Date</option>
                            <option value="rolling">Days</option>
                        </select>

                        <!-- Opens -->
                        <div>
                            <input v-if="rules[field.id][div.id].booking_window_type === 'calendar'"
                                v-model="rules[field.id][div.id].booking_opens_date" type="date"
                                :disabled="!rules[field.id][div.id].enabled"
                                class="rounded border-gray-200 px-1 py-0.5 text-[10px] w-full disabled:opacity-30" />
                            <select v-else-if="rules[field.id][div.id].booking_window_type === 'rolling'"
                                v-model="rules[field.id][div.id].booking_opens_days"
                                :disabled="!rules[field.id][div.id].enabled" class="w-full disabled:opacity-30">
                                <option value="">-</option>
                                <option v-for="d in [7,14,21,28,45,60,90]" :key="d" :value="d">{{ d }}d</option>
                            </select>
                            <span v-else class="text-[9px] text-gray-300 pl-1">-</span>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="mt-2 flex gap-2 pl-5">
                        <button @click="divisions.forEach(d => rules[field.id][d.id].enabled = true)" class="text-[9px] text-brand-600 hover:text-brand-700">Enable all</button>
                        <button @click="divisions.forEach(d => rules[field.id][d.id].enabled = false)" class="text-[9px] text-gray-500 hover:text-gray-700">Disable all</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <p class="text-[9px] font-semibold text-gray-400 mb-1">Legend</p>
            <div class="flex flex-wrap gap-3 text-[10px] text-gray-500">
                <span><strong>On:</strong> Division can use this field</span>
                <span><strong>Pri:</strong> Priority (1=highest, books first)</span>
                <span><strong>/wk:</strong> Max slots per week for this division</span>
                <span><strong>Window:</strong> None, Date (fixed open date), Days (rolling advance limit)</span>
                <span><strong>Opens:</strong> When booking becomes available</span>
            </div>
        </div>
    </LeagueLayout>
</template>
