<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    league: Object,
    windows: Array,
    unassignedDivisions: Array,
    allDivisions: Array,
    userRole: String,
});

const showModal = ref(false);
const editing = ref(null); // null = create, object = editing

const form = useForm({
    name: '',
    window_type: 'calendar',
    opens_date: '',
    rolling_days: '',
    division_ids: [],
});

function openCreate() {
    editing.value = null;
    form.name = '';
    form.window_type = 'calendar';
    form.opens_date = '';
    form.rolling_days = '';
    form.division_ids = [];
    form.clearErrors();
    showModal.value = true;
}

function openEdit(window) {
    editing.value = window;
    form.name = window.name;
    form.window_type = window.window_type;
    form.opens_date = window.opens_date ? String(window.opens_date).split('T')[0] : '';
    form.rolling_days = window.rolling_days || '';
    form.division_ids = window.divisions.map(d => d.id);
    showModal.value = true;
}

function save() {
    if (editing.value) {
        form.put(route('leagues.booking-windows.update', [props.league.slug, editing.value.id]), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('leagues.booking-windows.store', props.league.slug), {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

function deleteWindow(window) {
    if (!confirm(`Delete "${window.name}"? Divisions will be unassigned.`)) return;
    form.delete(route('leagues.booking-windows.destroy', [props.league.slug, window.id]));
}

function fmtDate(d) {
    if (!d) return '';
    const dateStr = d.split('T')[0];
    const [y, m, dy] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, dy).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const isOpen = (w) => {
    if (w.window_type === 'calendar' && w.opens_date) {
        const dateStr = w.opens_date.split('T')[0];
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Date() >= new Date(y, m - 1, d);
    }
    return true;
};

// Calendar windows sorted by opens_date for timeline
const calendarWindows = computed(() =>
    props.windows.filter(w => w.window_type === 'calendar' && w.opens_date)
        .sort((a, b) => String(a.opens_date).localeCompare(String(b.opens_date)))
);
</script>

<template>
    <Head :title="`${league.name} - Booking Windows`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Booking Windows</h2>
                <p class="text-[10px] text-gray-400">Control when each division can start booking field time.</p>
            </div>
            <PrimaryButton @click="openCreate" size="sm">New Window</PrimaryButton>
        </div>

        <FlashMessage />

        <div class="mt-3 space-y-3">
            <!-- Windows -->
            <div v-for="w in windows" :key="w.id"
                class="rounded-lg border bg-white"
                :class="isOpen(w) ? 'border-green-200' : 'border-gray-200'">
                <div class="flex items-center justify-between px-4 py-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-900">{{ w.name }}</h3>
                            <span v-if="w.window_type === 'calendar'" class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                :class="isOpen(w) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                {{ isOpen(w) ? 'Open' : 'Not Yet Open' }}
                            </span>
                            <span v-else class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-semibold text-blue-700">Rolling</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500">
                            <template v-if="w.window_type === 'calendar'">Opens {{ fmtDate(w.opens_date) }}</template>
                            <template v-else>Can book up to {{ w.rolling_days }} days ahead</template>
                        </p>
                        <div v-if="w.divisions.length" class="mt-1.5 flex flex-wrap gap-1">
                            <span v-for="d in w.divisions" :key="d.id"
                                class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">{{ d.name }}</span>
                        </div>
                        <p v-else class="mt-1 text-[10px] text-gray-400">No divisions assigned</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openEdit(w)" class="text-[10px] font-medium text-brand-600 hover:text-brand-700">Edit</button>
                        <button @click="deleteWindow(w)" class="text-[10px] font-medium text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Unassigned divisions -->
            <div v-if="unassignedDivisions.length" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                <p class="text-xs font-medium text-gray-500">No Booking Window (can book anytime)</p>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    <span v-for="d in unassignedDivisions" :key="d.id"
                        class="rounded bg-white px-2 py-0.5 text-[10px] font-medium text-gray-500 border border-gray-200">{{ d.name }}</span>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="windows.length === 0 && unassignedDivisions.length === 0"
                class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-400">
                No booking windows created yet. All divisions can book anytime.
            </div>

            <!-- Visual Timeline (calendar windows only) -->
            <div v-if="calendarWindows.length > 1" class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-2">Timeline</p>
                <div class="flex items-center gap-1 overflow-x-auto py-1">
                    <template v-for="(w, idx) in calendarWindows" :key="w.id">
                        <div v-if="idx > 0" class="h-px flex-1 min-w-4 bg-gray-300"></div>
                        <div class="flex flex-col items-center shrink-0">
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold"
                                :class="isOpen(w) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                {{ w.name }}
                            </span>
                            <span class="mt-0.5 text-[9px] text-gray-400">{{ fmtDate(w.opens_date) }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ editing ? 'Edit' : 'New' }} Booking Window</h3>

                <form @submit.prevent="save" class="mt-3 space-y-3">
                    <div>
                        <InputLabel for="bw_name" value="Name" class="text-xs" />
                        <TextInput id="bw_name" v-model="form.name" class="mt-1 block w-full" required placeholder="e.g. Early Access" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="bw_type" value="Window Type" class="text-xs" />
                        <select id="bw_type" v-model="form.window_type" class="mt-1 block w-full">
                            <option value="calendar">Opens on a specific date</option>
                            <option value="rolling">Rolling days ahead</option>
                        </select>
                    </div>

                    <div v-if="form.window_type === 'calendar'">
                        <InputLabel for="bw_date" value="Opens On" class="text-xs" />
                        <input id="bw_date" type="date" v-model="form.opens_date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.opens_date" class="mt-1" />
                    </div>

                    <div v-if="form.window_type === 'rolling'">
                        <InputLabel for="bw_days" value="Days Ahead" class="text-xs" />
                        <select id="bw_days" v-model="form.rolling_days" class="mt-1 block w-full" required>
                            <option value="">Select...</option>
                            <option v-for="d in [7, 14, 21, 28, 45, 60, 90]" :key="d" :value="d">{{ d }} days</option>
                        </select>
                        <InputError :message="form.errors.rolling_days" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Assign Divisions" class="text-xs" />
                        <div class="mt-1 space-y-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 p-2">
                            <label v-for="d in allDivisions" :key="d.id" class="flex items-center gap-2 cursor-pointer py-0.5">
                                <input type="checkbox" :value="d.id" v-model="form.division_ids"
                                    class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                <span class="text-xs text-gray-700">{{ d.name }}</span>
                                <span v-if="d.booking_window_id && d.booking_window_id !== editing?.id"
                                    class="text-[9px] text-amber-500">(currently in another window)</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.division_ids" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900">Cancel</button>
                        <PrimaryButton :disabled="form.processing">{{ editing ? 'Save' : 'Create' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </LeagueLayout>
</template>
