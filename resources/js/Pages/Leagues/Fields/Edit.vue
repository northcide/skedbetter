<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    league: Object, field: Object, fieldTypes: Array,
    divisions: { type: Array, default: () => [] },
    fieldRules: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
    userRole: String,
});

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const open = ref({ details: true, availability: false, slots: false, timeSlots: false, access: false });
const selectedSlotDays = ref([1]); // Default to Monday selected

const toggleSlotDay = (day) => {
    const idx = selectedSlotDays.value.indexOf(day);
    if (idx >= 0) selectedSlotDays.value.splice(idx, 1);
    else selectedSlotDays.value.push(day);
};
const toggle = (key) => { open.value[key] = !open.value[key]; };

const accessMode = ref(props.fieldRules.length > 0 ? 'restricted' : 'open');

const form = useForm({
    name: props.field.name,
    field_type_id: props.field.field_type_id || '',
    capacity: props.field.capacity || '',
    is_lighted: props.field.is_lighted,
    is_active: props.field.is_active,
    notes: props.field.notes || '',
    available_days: props.field.available_days || [],
    available_start_time: props.field.available_start_time?.slice(0, 5) || '',
    available_end_time: props.field.available_end_time?.slice(0, 5) || '',
    slot_interval_minutes: props.field.slot_interval_minutes || '',
    min_event_minutes: props.field.min_event_minutes || '',
    max_event_minutes: props.field.max_event_minutes || '',
    access_mode: accessMode.value,
    rules: props.fieldRules.map(r => ({
        division_id: r.division_id,
        max_weekly_slots: r.max_weekly_slots,
    })),
    time_slots: props.timeSlots.map(s => ({
        day_of_week: s.day_of_week,
        start_time: s.start_time?.slice(0, 5) || '',
        end_time: s.end_time?.slice(0, 5) || '',
        label: s.label || '',
    })),
});

watch(accessMode, (val) => { form.access_mode = val; });

const toggleDay = (day) => {
    const idx = form.available_days.indexOf(day);
    if (idx >= 0) form.available_days.splice(idx, 1);
    else form.available_days.push(day);
};

const addRule = () => {
    const used = form.rules.map(r => r.division_id);
    const avail = props.divisions.find(d => !used.includes(d.id));
    if (avail) form.rules.push({ division_id: avail.id, max_weekly_slots: null });
};
const removeRule = (i) => form.rules.splice(i, 1);

// Time slots helpers
const slotsForDay = (day) => form.time_slots.filter(s => s.day_of_week === day);
const dayHasSlots = (day) => slotsForDay(day).length > 0;

const addSlot = () => {
    selectedSlotDays.value.forEach(day => {
        form.time_slots.push({ day_of_week: day, start_time: '17:30', end_time: '19:00', label: '' });
    });
};
const removeSlotFromSelected = (idx) => {
    // Remove the slot at this index position from ALL selected days
    selectedSlotDays.value.forEach(day => {
        const daySlots = form.time_slots.filter(s => s.day_of_week === day);
        if (daySlots[idx]) {
            const globalIdx = form.time_slots.indexOf(daySlots[idx]);
            if (globalIdx >= 0) form.time_slots.splice(globalIdx, 1);
        }
    });
};
const clearSelectedDays = () => {
    selectedSlotDays.value.forEach(day => {
        form.time_slots = form.time_slots.filter(s => s.day_of_week !== day);
    });
};

// Show slots from the first selected day as the "template"
const displaySlots = computed(() => {
    if (!selectedSlotDays.value.length) return [];
    return slotsForDay(selectedSlotDays.value[0]);
});

// Sync changes from the displayed (first day) slot to all other selected days
function syncSlotToOtherDays(idx) {
    const sourceDay = selectedSlotDays.value[0];
    const sourceSlots = slotsForDay(sourceDay);
    if (!sourceSlots[idx]) return;
    const src = sourceSlots[idx];

    selectedSlotDays.value.forEach(day => {
        if (day === sourceDay) return;
        const daySlots = slotsForDay(day);
        if (daySlots[idx]) {
            daySlots[idx].start_time = src.start_time;
            daySlots[idx].end_time = src.end_time;
            daySlots[idx].label = src.label;
        }
    });
}

// Time slot dropdown options (30-min increments)
const slotTimeOptions = (() => {
    const opts = [];
    for (let h = 6; h <= 21; h++) {
        for (let m = 0; m < 60; m += 30) {
            const val = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
            const ampm = h >= 12 ? 'PM' : 'AM';
            opts.push({ value: val, label: `${h12}:${String(m).padStart(2, '0')} ${ampm}` });
        }
    }
    return opts;
})();

const submit = () => {
    form.put(route('leagues.fields.update', [props.league.slug, props.field.id]));
};
</script>

<template>
    <Head :title="`Edit ${field.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-400">{{ field.location?.name }}</p>
                <h2 class="text-base font-semibold text-gray-900">{{ field.name }}</h2>
            </div>
            <Link :href="route('leagues.locations.index', league.slug)" class="text-[11px] text-gray-500 hover:text-gray-700">Back</Link>
        </div>

        <FlashMessage />

        <form @submit.prevent="submit" class="mt-3 space-y-px rounded-lg border border-gray-200 bg-white">
            <!-- Details -->
            <div>
                <button type="button" @click="toggle('details')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Details</span>
                    <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open.details }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
                <div v-if="open.details" class="border-t border-gray-100 px-3 py-2 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div v-if="fieldTypes.length">
                            <InputLabel for="field_type_id" value="Field Type" />
                            <select id="field_type_id" v-model="form.field_type_id" class="mt-1 block w-full">
                                <option value="">None</option>
                                <option v-for="ft in fieldTypes" :key="ft.id" :value="ft.id">{{ ft.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <InputLabel for="capacity" value="Capacity" />
                            <TextInput id="capacity" v-model="form.capacity" type="number" class="mt-1 block w-full" min="0" />
                        </div>
                        <div class="flex items-end gap-3 col-span-2 pb-1">
                            <label class="flex items-center gap-1.5"><Checkbox v-model:checked="form.is_lighted" /><span class="text-xs text-gray-600">Lighted</span></label>
                            <label class="flex items-center gap-1.5"><Checkbox v-model:checked="form.is_active" /><span class="text-xs text-gray-600">Active</span></label>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea id="notes" v-model="form.notes" class="mt-1 block w-full" rows="1" />
                    </div>
                </div>
            </div>

            <!-- Availability -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('availability')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Availability</span>
                    <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open.availability }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
                <div v-if="open.availability" class="border-t border-gray-100 px-3 py-2 space-y-2">
                    <div>
                        <InputLabel value="Days" />
                        <div class="mt-1 flex flex-wrap gap-1">
                            <button v-for="(name, i) in dayNames" :key="i" type="button" @click="toggleDay(i)"
                                class="rounded border px-2 py-1 text-[11px] font-medium transition"
                                :class="form.available_days.includes(i) ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">{{ name }}</button>
                        </div>
                        <p class="mt-0.5 text-[10px] text-gray-400">Leave all off for no restriction.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><InputLabel value="Opens" /><TextInput v-model="form.available_start_time" type="time" step="1800" class="mt-1 block w-full" /></div>
                        <div><InputLabel value="Closes" /><TextInput v-model="form.available_end_time" type="time" step="1800" class="mt-1 block w-full" /></div>
                    </div>
                </div>
            </div>

            <!-- Slot Rules -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('slots')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Slot Rules</span>
                    <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open.slots }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
                <div v-if="open.slots" class="border-t border-gray-100 px-3 py-2">
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <InputLabel value="Start Interval" />
                            <select v-model="form.slot_interval_minutes" class="mt-1 block w-full">
                                <option value="">Any</option>
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Min Duration" />
                            <select v-model="form.min_event_minutes" class="mt-1 block w-full">
                                <option value="">None</option>
                                <option v-for="m in [30,60,90,120]" :key="m" :value="m">{{ m }} min</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Max Duration" />
                            <select v-model="form.max_event_minutes" class="mt-1 block w-full">
                                <option value="">None</option>
                                <option v-for="m in [30,60,90,120,180,240]" :key="m" :value="m">{{ m }} min</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Slots -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('timeSlots')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Time Slots</span>
                    <div class="flex items-center gap-2">
                        <span v-if="form.time_slots.length" class="rounded-full bg-brand-100 px-2 py-0.5 text-[9px] font-medium text-brand-700">{{ form.time_slots.length }} slot(s)</span>
                        <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open.timeSlots }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>
                <div v-if="open.timeSlots" class="border-t border-gray-100 px-3 py-2">
                    <p class="text-[10px] text-gray-400 mb-2">Define fixed time slots per day. Days without slots use open/flexible booking.</p>

                    <!-- Day toggles (multi-select) -->
                    <p class="text-[10px] text-gray-500 mb-1">Select days to edit together:</p>
                    <div class="flex flex-wrap gap-1 mb-3">
                        <button v-for="(name, idx) in dayNames" :key="idx" type="button"
                            @click="toggleSlotDay(idx)"
                            class="rounded px-2.5 py-1 text-[10px] font-medium transition border"
                            :class="selectedSlotDays.includes(idx)
                                ? 'bg-brand-600 text-white border-brand-600'
                                : dayHasSlots(idx)
                                    ? 'bg-brand-50 text-brand-700 border-brand-200 hover:bg-brand-100'
                                    : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                            {{ name }}
                            <span v-if="dayHasSlots(idx) && !selectedSlotDays.includes(idx)" class="ml-0.5 text-[8px]">{{ slotsForDay(idx).length }}</span>
                        </button>
                    </div>

                    <!-- Slots for selected days -->
                    <div v-if="selectedSlotDays.length" class="space-y-1.5">
                        <div v-for="(slot, idx) in displaySlots" :key="idx" class="flex items-center gap-2">
                            <select v-model="slot.start_time" @change="syncSlotToOtherDays(idx)" class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px]">
                                <option v-for="t in slotTimeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <span class="text-[10px] text-gray-400">to</span>
                            <select v-model="slot.end_time" @change="syncSlotToOtherDays(idx)" class="rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px]">
                                <option v-for="t in slotTimeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <input v-model="slot.label" @input="syncSlotToOtherDays(idx)" placeholder="Label" class="w-24 rounded border-gray-200 py-0.5 px-1.5 text-[11px]" />
                            <button type="button" @click="removeSlotFromSelected(idx)" class="text-[10px] text-red-400 hover:text-red-600">Remove</button>
                        </div>

                        <div v-if="!displaySlots.length" class="py-2 text-center text-[10px] text-gray-400">
                            Open booking (no fixed slots) on selected days
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="addSlot" class="text-[10px] font-medium text-brand-600 hover:text-brand-700">+ Add Slot</button>
                            <button v-if="displaySlots.length" type="button" @click="clearSelectedDays" class="text-[10px] text-red-400 hover:text-red-600">Clear selected days</button>
                        </div>

                        <p v-if="selectedSlotDays.length > 1" class="text-[9px] text-amber-600">
                            Changes apply to: {{ selectedSlotDays.map(d => dayNames[d]).join(', ') }}
                        </p>
                    </div>
                    <div v-else class="py-2 text-center text-[10px] text-gray-400">
                        Select one or more days above to manage slots
                    </div>
                </div>
            </div>

            <!-- Division Access -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('access')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Division Access</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium" :class="accessMode === 'open' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">{{ accessMode === 'open' ? 'Open' : form.rules.length + ' divisions' }}</span>
                </button>
                <div v-if="open.access" class="border-t border-gray-100 px-3 py-2 space-y-2">
                    <div class="flex gap-3">
                        <label class="flex items-center gap-1.5 rounded border px-2 py-1 cursor-pointer text-[11px] font-medium transition" :class="accessMode === 'open' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" v-model="accessMode" value="open" class="text-brand-600 focus:ring-brand-500" /> Open
                        </label>
                        <label class="flex items-center gap-1.5 rounded border px-2 py-1 cursor-pointer text-[11px] font-medium transition" :class="accessMode === 'restricted' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" v-model="accessMode" value="restricted" class="text-brand-600 focus:ring-brand-500" /> Restricted
                        </label>
                    </div>

                    <div v-if="accessMode === 'restricted'">
                        <!-- Header -->
                        <div class="grid grid-cols-[1fr_60px_24px] gap-1 text-[9px] font-semibold text-gray-400 px-0.5 mb-1">
                            <span>Division</span><span>Max/wk</span><span></span>
                        </div>

                        <div v-for="(rule, i) in form.rules" :key="i" class="grid grid-cols-[1fr_60px_24px] gap-1 items-center mt-1">
                            <select v-model="rule.division_id">
                                <option v-for="d in divisions" :key="d.id" :value="d.id" :disabled="form.rules.some((r, j) => j !== i && r.division_id === d.id)">{{ d.name }}</option>
                            </select>
                            <TextInput v-model="rule.max_weekly_slots" type="number" min="1" placeholder="∞" />
                            <button type="button" @click="removeRule(i)" class="text-red-400 hover:text-red-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>

                        <button type="button" @click="addRule" class="mt-1.5 text-[10px] text-brand-600 hover:text-brand-700">+ Add division</button>
                        <div v-if="form.rules.length === 0" class="text-[10px] text-amber-600">No divisions = field blocked for all.</div>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="border-t border-gray-100 px-3 py-2 flex justify-end gap-2">
                <Link :href="route('leagues.locations.index', league.slug)" class="text-[11px] text-gray-500 hover:text-gray-700 py-1">Cancel</Link>
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </LeagueLayout>
</template>
