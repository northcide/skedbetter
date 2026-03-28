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
    league: Object, field: Object, surfaceTypes: Array,
    divisions: { type: Array, default: () => [] },
    fieldRules: { type: Array, default: () => [] },
    userRole: String,
});

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const open = ref({ details: true, availability: false, slots: false, access: false });
const toggle = (key) => { open.value[key] = !open.value[key]; };

const accessMode = ref(props.fieldRules.length > 0 ? 'restricted' : 'open');

const form = useForm({
    name: props.field.name,
    surface_type: props.field.surface_type || '',
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
    rules: props.fieldRules.map(r => ({ division_id: r.division_id, max_weekly_slots: r.max_weekly_slots })),
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
                        <div>
                            <InputLabel for="surface_type" value="Surface" />
                            <select id="surface_type" v-model="form.surface_type" class="mt-1 block w-full">
                                <option value="">--</option>
                                <option v-for="st in surfaceTypes" :key="st" :value="st">{{ st.charAt(0).toUpperCase() + st.slice(1) }}</option>
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
                        <div><InputLabel value="Opens" /><TextInput v-model="form.available_start_time" type="time" class="mt-1 block w-full" /></div>
                        <div><InputLabel value="Closes" /><TextInput v-model="form.available_end_time" type="time" class="mt-1 block w-full" /></div>
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
                                <option value="15">15 min</option>
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Min Duration" />
                            <select v-model="form.min_event_minutes" class="mt-1 block w-full">
                                <option value="">None</option>
                                <option v-for="m in [15,30,45,60,90,120]" :key="m" :value="m">{{ m }} min</option>
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
                        <div v-for="(rule, i) in form.rules" :key="i" class="flex items-center gap-1.5 mt-1">
                            <select v-model="rule.division_id" class="flex-1">
                                <option v-for="d in divisions" :key="d.id" :value="d.id" :disabled="form.rules.some((r, j) => j !== i && r.division_id === d.id)">{{ d.name }}</option>
                            </select>
                            <TextInput v-model="rule.max_weekly_slots" type="number" min="1" class="w-16" placeholder="∞" />
                            <span class="text-[9px] text-gray-400">/wk</span>
                            <button type="button" @click="removeRule(i)" class="text-red-400 hover:text-red-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                        <button type="button" @click="addRule" class="mt-1 text-[10px] text-brand-600 hover:text-brand-700">+ Add division</button>
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
