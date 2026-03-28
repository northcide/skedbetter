<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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

const form = useForm({
    access_mode: accessMode.value,
    rules: props.fieldRules.length > 0
        ? props.fieldRules.map(r => ({ division_id: r.division_id, max_weekly_slots: r.max_weekly_slots }))
        : [],
});

watch(accessMode, (val) => {
    form.access_mode = val;
});

const availableDivisions = computed(() => {
    const usedIds = form.rules.map(r => r.division_id);
    return props.divisions.filter(d => !usedIds.includes(d.id));
});

const addRule = () => {
    if (availableDivisions.value.length > 0) {
        form.rules.push({
            division_id: availableDivisions.value[0].id,
            max_weekly_slots: null,
        });
    }
};

const removeRule = (index) => {
    form.rules.splice(index, 1);
};

const getDivisionName = (id) => {
    const div = props.divisions.find(d => d.id === id);
    return div ? `${div.name}${div.age_group ? ` (${div.age_group})` : ''}` : 'Unknown';
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
                <h2 class="text-xl font-semibold text-gray-900">{{ field.name }} &mdash; Rules</h2>
            </div>
            <Link :href="route('leagues.locations.index', league.slug)" class="text-sm text-gray-500 hover:text-gray-700">
                Back to Locations
            </Link>
        </div>

        <FlashMessage />

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Access Mode -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900">Division Access</h3>
                <p class="mt-1 text-xs text-gray-500">Control which divisions can schedule this field.</p>

                <div class="mt-4 space-y-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition" :class="accessMode === 'open' ? 'border-brand-300 bg-brand-50' : 'border-gray-200'">
                        <input type="radio" v-model="accessMode" value="open" class="mt-0.5 text-brand-600 focus:ring-brand-500" />
                        <div>
                            <span class="text-sm font-medium text-gray-900">Open access</span>
                            <p class="text-xs text-gray-500">All divisions can schedule this field. No restrictions.</p>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition" :class="accessMode === 'restricted' ? 'border-brand-300 bg-brand-50' : 'border-gray-200'">
                        <input type="radio" v-model="accessMode" value="restricted" class="mt-0.5 text-brand-600 focus:ring-brand-500" />
                        <div>
                            <span class="text-sm font-medium text-gray-900">Restricted to specific divisions</span>
                            <p class="text-xs text-gray-500">Only selected divisions can schedule this field, with optional weekly limits.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Division Rules (only when restricted) -->
            <div v-if="accessMode === 'restricted'" class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Allowed Divisions</h3>
                        <p class="mt-1 text-xs text-gray-500">Set which divisions can use this field and how many times per week.</p>
                    </div>
                    <button
                        type="button"
                        @click="addRule"
                        :disabled="availableDivisions.length === 0"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-brand-600 transition hover:bg-gray-50 disabled:opacity-50"
                    >
                        + Add Division
                    </button>
                </div>

                <div v-if="form.rules.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-400">
                    No divisions added. Click "Add Division" to allow specific divisions to use this field.
                </div>

                <div v-else class="mt-4 space-y-3">
                    <div
                        v-for="(rule, index) in form.rules"
                        :key="index"
                        class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3"
                    >
                        <div class="flex-1">
                            <label class="text-xs font-medium text-gray-500">Division</label>
                            <select
                                v-model="rule.division_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            >
                                <!-- Show current selection + available ones -->
                                <option v-for="d in divisions" :key="d.id" :value="d.id" :disabled="form.rules.some((r, i) => i !== index && r.division_id === d.id)">
                                    {{ d.name }}{{ d.age_group ? ` (${d.age_group})` : '' }}{{ d.season?.name ? ` - ${d.season.name}` : '' }}
                                </option>
                            </select>
                        </div>
                        <div class="w-40">
                            <label class="text-xs font-medium text-gray-500">Weekly limit</label>
                            <TextInput
                                v-model="rule.max_weekly_slots"
                                type="number"
                                min="1"
                                class="mt-1 block w-full text-sm"
                                placeholder="No limit"
                            />
                        </div>
                        <button type="button" @click="removeRule(index)" class="mt-5 text-red-400 hover:text-red-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Summary</h4>
                <p v-if="accessMode === 'open'" class="mt-2 text-sm text-gray-700">
                    All divisions can schedule <strong>{{ field.name }}</strong> with no restrictions.
                </p>
                <div v-else class="mt-2">
                    <p v-if="form.rules.length === 0" class="text-sm text-amber-600">
                        No divisions are allowed yet. This field will be blocked for all scheduling until you add at least one division.
                    </p>
                    <ul v-else class="space-y-1">
                        <li v-for="rule in form.rules" :key="rule.division_id" class="text-sm text-gray-700">
                            <strong>{{ getDivisionName(rule.division_id) }}</strong>
                            <span v-if="rule.max_weekly_slots"> &mdash; max {{ rule.max_weekly_slots }} slot{{ rule.max_weekly_slots != 1 ? 's' : '' }}/week</span>
                            <span v-else> &mdash; unlimited</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing">Save Rules</PrimaryButton>
            </div>
        </form>
    </LeagueLayout>
</template>
