<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    league: Object,
    field: Object,
    divisions: Array,
    fieldRules: Array,
    userRole: String,
});

const accessMode = ref(props.fieldRules.length > 0 ? 'restricted' : 'open');

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const form = useForm({
    access_mode: accessMode.value,
    rules: props.fieldRules.length > 0
        ? props.fieldRules.map(r => ({ division_id: r.division_id, max_weekly_slots: r.max_weekly_slots }))
        : [],
    available_days: props.field.available_days || [],
    available_start_time: props.field.available_start_time?.slice(0, 5) || '',
    available_end_time: props.field.available_end_time?.slice(0, 5) || '',
    slot_interval_minutes: props.field.slot_interval_minutes || '',
    min_event_minutes: props.field.min_event_minutes || '',
    max_event_minutes: props.field.max_event_minutes || '',
});

watch(accessMode, (val) => { form.access_mode = val; });

const availableDivisions = computed(() => {
    const usedIds = form.rules.map(r => r.division_id);
    return props.divisions.filter(d => !usedIds.includes(d.id));
});

const addRule = () => {
    if (availableDivisions.value.length > 0) {
        form.rules.push({ division_id: availableDivisions.value[0].id, max_weekly_slots: null });
    }
};
const removeRule = (i) => form.rules.splice(i, 1);

const toggleDay = (day) => {
    const idx = form.available_days.indexOf(day);
    if (idx >= 0) form.available_days.splice(idx, 1);
    else form.available_days.push(day);
};

const submit = () => {
    form.put(route('leagues.fields.rules.update', [props.league.slug, props.field.id]));
};
</script>

<template>
    <Head :title="`Field Rules - ${field.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400">{{ field.location?.name }}</p>
                <h2 class="text-lg font-semibold text-gray-900">{{ field.name }} &mdash; Rules</h2>
            </div>
            <Link :href="route('leagues.locations.index', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">Back</Link>
        </div>

        <FlashMessage />

        <form @submit.prevent="submit" class="mt-4 space-y-3">
            <!-- Availability: Days & Hours -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Availability</h3>
                <p class="mt-0.5 text-xs text-gray-500">When can this field be booked?</p>

                <div class="mt-4 space-y-4">
                    <!-- Days of week -->
                    <div>
                        <InputLabel value="Available Days" class="text-xs" />
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <button
                                v-for="(name, i) in dayNames"
                                :key="i"
                                type="button"
                                @click="toggleDay(i)"
                                class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                                :class="form.available_days.includes(i)
                                    ? 'border-brand-300 bg-brand-50 text-brand-700'
                                    : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                            >{{ name }}</button>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">Leave all unselected for no day restrictions.</p>
                    </div>

                    <!-- Hours -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="av_start" value="Opens At" class="text-xs" />
                            <TextInput id="av_start" v-model="form.available_start_time" type="time" step="1800" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="av_end" value="Closes At" class="text-xs" />
                            <TextInput id="av_end" v-model="form.available_end_time" type="time" step="1800" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slot Rules -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Slot Rules</h3>
                <p class="mt-0.5 text-xs text-gray-500">Control when events can start and how long they can be.</p>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div>
                        <InputLabel for="slot_int" value="Start Time Interval" class="text-xs" />
                        <select id="slot_int" v-model="form.slot_interval_minutes" class="mt-1 block w-full">
                            <option value="">Any time</option>
                            <option value="30">Every 30 min</option>
                            <option value="60">On the hour</option>
                        </select>
                        <p class="mt-0.5 text-[10px] text-gray-400">Events must start at these intervals</p>
                    </div>
                    <div>
                        <InputLabel for="min_dur" value="Min Duration" class="text-xs" />
                        <select id="min_dur" v-model="form.min_event_minutes" class="mt-1 block w-full">
                            <option value="">No minimum</option>
                            <option value="30">30 min</option>
                            <option value="60">60 min</option>
                            <option value="90">90 min</option>
                            <option value="120">120 min</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="max_dur" value="Max Duration" class="text-xs" />
                        <select id="max_dur" v-model="form.max_event_minutes" class="mt-1 block w-full">
                            <option value="">No maximum</option>
                            <option value="30">30 min</option>
                            <option value="60">60 min</option>
                            <option value="90">90 min</option>
                            <option value="120">120 min</option>
                            <option value="180">180 min</option>
                            <option value="240">240 min</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Division Access -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Division Access</h3>
                <p class="mt-0.5 text-xs text-gray-500">Which divisions can schedule this field?</p>

                <div class="mt-3 flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-xs font-medium transition" :class="accessMode === 'open' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                        <input type="radio" v-model="accessMode" value="open" class="text-brand-600 focus:ring-brand-500" />
                        Open to all
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-xs font-medium transition" :class="accessMode === 'restricted' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600'">
                        <input type="radio" v-model="accessMode" value="restricted" class="text-brand-600 focus:ring-brand-500" />
                        Restricted
                    </label>
                </div>

                <div v-if="accessMode === 'restricted'" class="mt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Allowed divisions:</span>
                        <button type="button" @click="addRule" :disabled="availableDivisions.length === 0" class="text-xs font-medium text-brand-600 hover:text-brand-700 disabled:opacity-50">+ Add</button>
                    </div>

                    <div v-if="form.rules.length === 0" class="mt-2 rounded border border-dashed border-gray-300 p-4 text-center text-xs text-gray-400">
                        No divisions added yet.
                    </div>

                    <div v-else class="mt-2 space-y-2">
                        <div v-for="(rule, i) in form.rules" :key="i" class="flex items-center gap-2 rounded border border-gray-100 bg-gray-50 p-2">
                            <select v-model="rule.division_id" class="flex-1 text-xs">
                                <option v-for="d in divisions" :key="d.id" :value="d.id" :disabled="form.rules.some((r, j) => j !== i && r.division_id === d.id)">
                                    {{ d.name }}{{ d.age_group ? ` (${d.age_group})` : '' }}
                                </option>
                            </select>
                            <TextInput v-model="rule.max_weekly_slots" type="number" min="1" class="w-20 text-xs" placeholder="Unlimited" />
                            <span class="text-[10px] text-gray-400">/wk</span>
                            <button type="button" @click="removeRule(i)" class="text-red-400 hover:text-red-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Summary</h4>
                <ul class="mt-2 space-y-1 text-xs text-gray-700">
                    <li v-if="form.available_days.length > 0">
                        Days: <strong>{{ form.available_days.map(d => dayNames[d]).join(', ') }}</strong>
                    </li>
                    <li v-else>Days: <span class="text-gray-400">All days</span></li>
                    <li v-if="form.available_start_time || form.available_end_time">
                        Hours: <strong>{{ form.available_start_time || '(open)' }} &ndash; {{ form.available_end_time || '(open)' }}</strong>
                    </li>
                    <li v-if="form.slot_interval_minutes">Starts every: <strong>{{ form.slot_interval_minutes }} min</strong></li>
                    <li v-if="form.min_event_minutes">Min duration: <strong>{{ form.min_event_minutes }} min</strong></li>
                    <li v-if="form.max_event_minutes">Max duration: <strong>{{ form.max_event_minutes }} min</strong></li>
                    <li v-if="accessMode === 'open'">Divisions: <span class="text-gray-400">All allowed</span></li>
                    <li v-else-if="form.rules.length > 0">Divisions: <strong>{{ form.rules.length }} allowed</strong></li>
                    <li v-else class="text-amber-600">No divisions allowed (field will be blocked)</li>
                </ul>
            </div>

            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing">Save Rules</PrimaryButton>
            </div>
        </form>
    </LeagueLayout>
</template>
