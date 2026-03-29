<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ league: Object, rule: Object, scopeIds: Array, locations: Array, userRole: String });

const form = useForm({
    name: props.rule.name,
    reason: props.rule.reason || '',
    scope_type: props.rule.scope_type,
    scope_ids: [...(props.scopeIds || [])],
    start_date: props.rule.start_date?.split('T')[0] || '',
    end_date: props.rule.end_date?.split('T')[0] || '',
    start_time: props.rule.start_time?.slice(0, 5) || '',
    end_time: props.rule.end_time?.slice(0, 5) || '',
    recurrence: props.rule.recurrence?.value ?? props.rule.recurrence ?? 'none',
    day_of_week: props.rule.day_of_week ?? '',
    is_active: props.rule.is_active,
});

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const allFields = computed(() =>
    props.locations.flatMap(loc => (loc.fields || []).map(f => ({ ...f, location_name: loc.name })))
);

const toggleScopeId = (id) => {
    const idx = form.scope_ids.indexOf(id);
    if (idx >= 0) form.scope_ids.splice(idx, 1);
    else form.scope_ids.push(id);
};

const submit = () => {
    form.put(route('leagues.blackouts.update', [props.league.slug, props.rule.id]));
};
</script>

<template>
    <Head :title="`${league.name} - Edit Blackout Rule`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <h2 class="text-base font-semibold text-gray-900">Edit Blackout Rule</h2>

        <div class="mt-3 max-w-2xl rounded-lg border border-gray-200 bg-white">
            <form @submit.prevent="submit" class="space-y-3 p-4">
                <div>
                    <InputLabel for="name" value="Rule Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="reason" value="Reason (optional)" />
                    <TextInput id="reason" v-model="form.reason" type="text" class="mt-1 block w-full" />
                </div>

                <!-- Scope -->
                <div>
                    <InputLabel for="scope_type" value="Applies To" />
                    <select id="scope_type" v-model="form.scope_type" @change="form.scope_ids = []" class="mt-1 block w-full">
                        <option value="league">Entire League</option>
                        <option value="location">Locations</option>
                        <option value="field">Fields</option>
                    </select>
                </div>

                <div v-if="form.scope_type === 'location'" class="rounded-md border border-gray-200 p-2 max-h-48 overflow-y-auto">
                    <label v-for="loc in locations" :key="loc.id" class="flex items-center gap-2 py-1 cursor-pointer">
                        <input type="checkbox" :checked="form.scope_ids.includes(loc.id)" @change="toggleScopeId(loc.id)"
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-xs text-gray-700">{{ loc.name }}</span>
                    </label>
                </div>

                <div v-if="form.scope_type === 'field'" class="rounded-md border border-gray-200 p-2 max-h-48 overflow-y-auto">
                    <div v-for="loc in locations" :key="loc.id" class="mb-2">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ loc.name }}</p>
                        <label v-for="f in (loc.fields || [])" :key="f.id" class="flex items-center gap-2 py-0.5 pl-2 cursor-pointer">
                            <input type="checkbox" :checked="form.scope_ids.includes(f.id)" @change="toggleScopeId(f.id)"
                                class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            <span class="text-xs text-gray-700">{{ f.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.start_date" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="end_date" value="End Date" />
                        <TextInput id="end_date" v-model="form.end_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.end_date" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel for="start_time" value="Start Time (blank = all day)" />
                        <TextInput id="start_time" v-model="form.start_time" type="time" step="1800" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="end_time" value="End Time" />
                        <TextInput id="end_time" v-model="form.end_time" type="time" step="1800" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <InputLabel for="recurrence" value="Recurrence" />
                    <select id="recurrence" v-model="form.recurrence" class="mt-1 block w-full">
                        <option value="none">None (one-time)</option>
                        <option value="weekly">Weekly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <div v-if="form.recurrence === 'weekly'">
                    <InputLabel for="day_of_week" value="Day of Week" />
                    <select id="day_of_week" v-model="form.day_of_week" class="mt-1 block w-full" required>
                        <option v-for="(name, i) in dayNames" :key="i" :value="i">{{ name }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-xs text-gray-700">Active</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('leagues.blackouts.index', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                </div>
            </form>
        </div>
    </LeagueLayout>
</template>
