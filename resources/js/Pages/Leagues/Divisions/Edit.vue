<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const props = defineProps({
    league: Object, division: Object, seasons: Array,
    fields: { type: Array, default: () => [] },
    allowedFieldIds: { type: Array, default: () => [] },
    blockedFieldIds: { type: Array, default: () => [] },
    bookingWindows: { type: Array, default: () => [] },
    availabilityRules: { type: Object, default: () => ({}) },
    userRole: String,
});

const open = ref({ details: true, rules: false, availability: false, fields: false });

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const dayAbbrev = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Build reactive state for each day from existing rules
const days = reactive(
    Array.from({ length: 7 }, (_, i) => {
        const existing = props.availabilityRules[i];
        return {
            day_of_week: i,
            enabled: !!existing,
            all_day: existing ? existing.all_day : true,
            start_time: existing?.start_time ? existing.start_time.substring(0, 5) : '08:00',
            end_time: existing?.end_time ? existing.end_time.substring(0, 5) : '20:00',
        };
    })
);

const hasAnyDayEnabled = () => days.some(d => d.enabled);
const availabilitySummary = () => {
    if (!hasAnyDayEnabled()) return 'Unrestricted';
    const count = days.filter(d => d.enabled).length;
    return count + ' day' + (count !== 1 ? 's' : '');
};
const toggle = (key) => { open.value[key] = !open.value[key]; };

const form = useForm({
    name: props.division.name,
    season_id: props.division.season_id,
    age_group: props.division.age_group || '',
    skill_level: props.division.skill_level || '',
    max_event_minutes: props.division.max_event_minutes || '',
    max_weekly_events_per_team: props.division.max_weekly_events_per_team || '',
    booking_window_id: props.division.booking_window_id || '',
    field_access: props.allowedFieldIds.length > 0 ? 'specific' : 'all',
    allowed_field_ids: [...props.allowedFieldIds],
    availability_rules: [],
});

const toggleField = (id) => {
    const idx = form.allowed_field_ids.indexOf(id);
    if (idx >= 0) form.allowed_field_ids.splice(idx, 1);
    else form.allowed_field_ids.push(id);
};

const submit = () => {
    // Build availability_rules from enabled days only
    form.availability_rules = days
        .filter(d => d.enabled)
        .map(d => ({
            day_of_week: d.day_of_week,
            all_day: d.all_day,
            start_time: d.all_day ? null : d.start_time,
            end_time: d.all_day ? null : d.end_time,
        }));
    form.put(route('leagues.divisions.update', [props.league.slug, props.division.id]));
};

// Group fields by location
const fieldsByLocation = props.fields.reduce((acc, f) => {
    const loc = f.location?.name || 'Unknown';
    if (!acc[loc]) acc[loc] = [];
    acc[loc].push(f);
    return acc;
}, {});
</script>

<template>
    <Head :title="`Edit ${division.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">{{ division.name }}</h2>
            <Link :href="route('leagues.divisions.index', league.slug)" class="text-[11px] text-gray-500 hover:text-gray-700">Back</Link>
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
                        <div>
                            <InputLabel for="season_id" value="Season" />
                            <select id="season_id" v-model="form.season_id" class="mt-1 block w-full" required>
                                <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="age_group" value="Age Group" />
                            <TextInput id="age_group" v-model="form.age_group" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="skill_level" value="Skill Level" />
                            <TextInput id="skill_level" v-model="form.skill_level" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scheduling Rules -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('rules')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Scheduling Rules</span>
                    <svg class="h-3 w-3 text-gray-400 transition-transform" :class="{ 'rotate-90': open.rules }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
                <div v-if="open.rules" class="border-t border-gray-100 px-3 py-2">
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <InputLabel for="booking_window_id" value="Booking Window" />
                            <select id="booking_window_id" v-model="form.booking_window_id" class="mt-1 block w-full">
                                <option value="">No restriction</option>
                                <option v-for="w in bookingWindows" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="max_event_minutes" value="Max Event Duration" />
                            <select id="max_event_minutes" v-model="form.max_event_minutes" class="mt-1 block w-full">
                                <option value="">No limit</option>
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                                <option value="90">90 min</option>
                                <option value="120">120 min</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="max_weekly" value="Max Events/Team/Week" />
                            <select id="max_weekly" v-model="form.max_weekly_events_per_team" class="mt-1 block w-full">
                                <option value="">No limit</option>
                                <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                            </select>
                        </div>
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400">Manage booking windows on the Booking Windows page.</p>
                </div>
            </div>

            <!-- Allowed Days & Times -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('availability')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Allowed Days &amp; Times</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium" :class="hasAnyDayEnabled() ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'">
                        {{ availabilitySummary() }}
                    </span>
                </button>
                <div v-if="open.availability" class="border-t border-gray-100 px-3 py-2 space-y-2">
                    <p class="text-[10px] text-gray-400">Restrict which days and times this division can book. No days selected = unrestricted (any day, any time).</p>

                    <div class="space-y-1">
                        <div v-for="day in days" :key="day.day_of_week" class="flex items-center gap-2 rounded border px-2 py-1.5 transition"
                            :class="day.enabled ? 'border-brand-200 bg-brand-50/50' : 'border-gray-100 bg-gray-50/50'">
                            <!-- Day toggle -->
                            <label class="flex items-center gap-1.5 cursor-pointer min-w-[80px]">
                                <input type="checkbox" v-model="day.enabled" class="rounded text-brand-600 focus:ring-brand-500" />
                                <span class="text-[11px] font-medium" :class="day.enabled ? 'text-gray-900' : 'text-gray-400'">{{ dayNames[day.day_of_week] }}</span>
                            </label>

                            <!-- Time options (only when day is enabled) -->
                            <template v-if="day.enabled">
                                <label class="flex items-center gap-1 cursor-pointer ml-2">
                                    <input type="checkbox" v-model="day.all_day" class="rounded text-brand-600 focus:ring-brand-500" />
                                    <span class="text-[10px] text-gray-500">All day</span>
                                </label>

                                <template v-if="!day.all_day">
                                    <input type="time" v-model="day.start_time" class="ml-2 rounded border-gray-200 px-1.5 py-0.5 text-[11px]" />
                                    <span class="text-[10px] text-gray-400">to</span>
                                    <input type="time" v-model="day.end_time" class="rounded border-gray-200 px-1.5 py-0.5 text-[11px]" />
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field Access -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('fields')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Field Access</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium" :class="blockedFieldIds.length ? 'bg-red-100 text-red-700' : form.field_access === 'all' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                        {{ blockedFieldIds.length ? blockedFieldIds.length + ' blocked' : form.field_access === 'all' ? 'All fields' : form.allowed_field_ids.length + ' field(s)' }}
                    </span>
                </button>
                <div v-if="open.fields" class="border-t border-gray-100 px-3 py-2 space-y-2">
                    <div class="flex gap-3">
                        <label class="flex items-center gap-1.5 rounded border px-2 py-1 cursor-pointer text-[11px] font-medium transition"
                            :class="form.field_access === 'all' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" v-model="form.field_access" value="all" class="text-brand-600 focus:ring-brand-500" /> All fields
                        </label>
                        <label class="flex items-center gap-1.5 rounded border px-2 py-1 cursor-pointer text-[11px] font-medium transition"
                            :class="form.field_access === 'specific' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" v-model="form.field_access" value="specific" class="text-brand-600 focus:ring-brand-500" /> Specific fields only
                        </label>
                    </div>

                    <div v-if="form.field_access === 'specific'">
                        <p class="text-[10px] text-gray-400 mb-2">Select which fields this division can schedule.</p>
                        <div v-for="(locFields, locName) in fieldsByLocation" :key="locName" class="mb-2">
                            <p class="text-[10px] font-semibold text-gray-500 mb-1">{{ locName }}</p>
                            <div class="flex flex-wrap gap-1">
                                <label v-for="f in locFields" :key="f.id"
                                    class="flex items-center gap-1 rounded border px-2 py-1 cursor-pointer text-[11px] transition"
                                    :class="form.allowed_field_ids.includes(f.id) ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    <input type="checkbox" :checked="form.allowed_field_ids.includes(f.id)" @change="toggleField(f.id)" class="hidden" />
                                    {{ f.name }}
                                </label>
                            </div>
                        </div>
                        <p v-if="form.allowed_field_ids.length === 0" class="text-[10px] text-amber-600">No fields selected — this division won't be able to schedule.</p>
                    </div>

                    <!-- Show fields that block this division (set from field side) -->
                    <div v-if="blockedFieldIds.length" class="rounded border border-red-100 bg-red-50 p-2">
                        <p class="text-[10px] font-semibold text-red-700">Blocked by field restrictions:</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span v-for="fid in blockedFieldIds" :key="fid" class="rounded border border-red-200 bg-white px-1.5 py-0.5 text-[10px] text-red-600">
                                {{ fields.find(f => f.id === fid)?.name }} @ {{ fields.find(f => f.id === fid)?.location?.name }}
                            </span>
                        </div>
                        <p class="mt-1 text-[9px] text-red-500">These fields have division restrictions that exclude this division. Edit the field to change.</p>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="border-t border-gray-100 px-3 py-2 flex justify-end gap-2">
                <Link :href="route('leagues.divisions.index', league.slug)" class="text-[11px] text-gray-500 hover:text-gray-700 py-1">Cancel</Link>
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </LeagueLayout>
</template>
