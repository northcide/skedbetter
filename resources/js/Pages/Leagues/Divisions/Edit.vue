<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object, division: Object, seasons: Array,
    fields: { type: Array, default: () => [] },
    allowedFieldIds: { type: Array, default: () => [] },
    userRole: String,
});

const open = ref({ details: true, rules: false, fields: false });
const toggle = (key) => { open.value[key] = !open.value[key]; };

const form = useForm({
    name: props.division.name,
    season_id: props.division.season_id,
    age_group: props.division.age_group || '',
    skill_level: props.division.skill_level || '',
    max_event_minutes: props.division.max_event_minutes || '',
    max_weekly_events_per_team: props.division.max_weekly_events_per_team || '',
    field_access: props.allowedFieldIds.length > 0 ? 'specific' : 'all',
    allowed_field_ids: [...props.allowedFieldIds],
});

const toggleField = (id) => {
    const idx = form.allowed_field_ids.indexOf(id);
    if (idx >= 0) form.allowed_field_ids.splice(idx, 1);
    else form.allowed_field_ids.push(id);
};

const submit = () => {
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
                    <div class="grid grid-cols-2 gap-2">
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
                    <p class="mt-1 text-[10px] text-gray-400">Team-level limits override these.</p>
                </div>
            </div>

            <!-- Field Access -->
            <div class="border-t border-gray-100">
                <button type="button" @click="toggle('fields')" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                    <span class="text-xs font-semibold text-gray-900">Field Access</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-medium" :class="form.field_access === 'all' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                        {{ form.field_access === 'all' ? 'All fields' : form.allowed_field_ids.length + ' field(s)' }}
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
