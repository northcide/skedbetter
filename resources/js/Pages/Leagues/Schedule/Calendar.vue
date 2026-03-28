<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';

const props = defineProps({
    league: Object,
    userRole: String,
    teams: { type: Array, default: () => [] },
    seasons: { type: Array, default: () => [] },
    divisions: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
});

const isManager = ['superadmin', 'league_manager', 'division_manager'].includes(props.userRole);
const calendarRef = ref(null);
const teamListRef = ref(null);
const errorMessages = ref([]);
const showModal = ref(false);
const showConfirmation = ref(false);
const modalSubmitting = ref(false);
const confirmationDetails = ref({});
const modalFieldName = ref('');

// Persist state in localStorage keyed by league
const storeKey = `skedbetter-cal-${props.league.id}`;

function loadState() {
    try { return JSON.parse(localStorage.getItem(storeKey)) || {}; } catch { return {}; }
}

function saveState(patch) {
    const current = loadState();
    localStorage.setItem(storeKey, JSON.stringify({ ...current, ...patch }));
}

const saved = loadState();
const sidebarDivision = ref(saved.sidebarDivision || '');

// Filters — restore from localStorage
const filters = ref({
    division_id: saved.division_id || '',
    team_id: saved.team_id || '',
    location_id: saved.location_id || '',
    field_id: saved.field_id || '',
});

// Derived: fields for the selected location (or all)
const availableFields = computed(() => {
    if (filters.value.location_id) {
        const loc = props.locations.find(l => l.id == filters.value.location_id);
        return loc?.fields || [];
    }
    return props.locations.flatMap(l => l.fields || []);
});

// Teams filtered by main division filter (for calendar events)
const filteredTeams = computed(() => {
    if (filters.value.division_id) {
        return props.teams.filter(t => t.division_id == filters.value.division_id);
    }
    return props.teams;
});

// Teams filtered by sidebar division picker (independent from calendar filter)
const sidebarTeams = computed(() => {
    if (sidebarDivision.value) {
        return props.teams.filter(t => t.division_id == sidebarDivision.value);
    }
    return props.teams;
});

// Build extra params string for event fetching
const filterParams = computed(() => {
    const params = {};
    if (filters.value.division_id) params.division_id = filters.value.division_id;
    if (filters.value.team_id) params.team_id = filters.value.team_id;
    if (filters.value.location_id) params.location_id = filters.value.location_id;
    if (filters.value.field_id) params.field_id = filters.value.field_id;
    return params;
});

// Refetch events when filters change + persist
watch(filters, (val) => {
    saveState({ division_id: val.division_id, team_id: val.team_id, location_id: val.location_id, field_id: val.field_id });

    const api = calendarRef.value?.getApi();
    if (!api) return;

    const sources = api.getEventSources();
    sources.forEach(s => s.remove());

    const baseUrl = route('leagues.schedule.events', props.league.slug);
    const qs = new URLSearchParams(filterParams.value).toString();
    const url = qs ? `${baseUrl}?${qs}` : baseUrl;

    api.addEventSource({ url, method: 'GET' });
}, { deep: true });

// Persist sidebar division
watch(sidebarDivision, (val) => saveState({ sidebarDivision: val }));

// Reset dependent filters
watch(() => filters.value.division_id, () => {
    // If filtered team is no longer in this division, clear it
    if (filters.value.team_id && filters.value.division_id) {
        const team = props.teams.find(t => t.id == filters.value.team_id);
        if (team && team.division_id != filters.value.division_id) {
            filters.value.team_id = '';
        }
    }
});

watch(() => filters.value.location_id, () => {
    // If filtered field is no longer at this location, clear it
    if (filters.value.field_id && filters.value.location_id) {
        const field = availableFields.value.find(f => f.id == filters.value.field_id);
        if (!field) filters.value.field_id = '';
    }
});

function clearFilters() {
    filters.value = { division_id: '', team_id: '', location_id: '', field_id: '' };
}

const hasFilters = computed(() => Object.values(filters.value).some(v => v));

// Modal form
const modalForm = useForm({
    season_id: props.seasons.find(s => s.is_current)?.id || props.seasons[0]?.id || '',
    field_id: '',
    team_id: '',
    date: '',
    start_time: '',
    end_time: '',
    type: 'practice',
    title: '',
});

onMounted(() => {
    if (teamListRef.value && isManager) {
        new Draggable(teamListRef.value, {
            itemSelector: '[data-team-id]',
            eventData: (el) => ({
                title: el.getAttribute('data-team-name'),
                duration: '01:00',
                extendedProps: { team_id: el.getAttribute('data-team-id') },
                backgroundColor: el.getAttribute('data-team-color') || '#3B82F6',
                borderColor: el.getAttribute('data-team-color') || '#3B82F6',
            }),
        });
    }
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, resourceTimelinePlugin, interactionPlugin],
    initialView: saved.view || 'timeGridWeek',
    initialDate: saved.date || undefined,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,resourceTimelineDay',
    },
    views: {
        resourceTimelineDay: { buttonText: 'Fields' },
        timeGridWeek: { buttonText: 'Week' },
        timeGridDay: { buttonText: 'Day' },
        dayGridMonth: { buttonText: 'Month' },
    },
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    allDaySlot: false,
    nowIndicator: true,
    expandRows: true,
    height: 'auto',
    contentHeight: 560,
    resourceAreaHeaderContent: 'Fields',
    resourceAreaWidth: '150px',
    editable: isManager,
    droppable: isManager,
    selectable: isManager,
    selectMirror: true,
    eventResourceEditable: isManager,
    resources: {
        url: route('leagues.schedule.resources', props.league.slug),
        method: 'GET',
    },
    events: {
        url: route('leagues.schedule.events', props.league.slug),
        method: 'GET',
    },
    datesSet: (info) => {
        saveState({ view: info.view.type, date: info.start.toISOString().slice(0, 10) });
    },
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    eventReceive: handleExternalDrop,
    select: handleSelect,
    eventClick: handleEventClick,
    resourceGroupField: 'parentId',
    schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
});

function handleEventDrop(info) {
    const event = info.event;
    const startTime = event.start.toTimeString().slice(0, 5);
    const endTime = event.end.toTimeString().slice(0, 5);
    const date = event.start.toISOString().slice(0, 10);
    const fieldId = event.getResources()[0]?.id;

    if (fieldId && fieldId.startsWith('loc-')) { info.revert(); return; }

    axios.patch(route('leagues.schedule.move', [props.league.slug, event.id]), {
        date, start_time: startTime, end_time: endTime, field_id: fieldId || undefined,
    }).catch((error) => {
        info.revert();
        showError(error.response?.data?.errors || ['Failed to move event']);
    });
}

function handleEventResize(info) { handleEventDrop(info); }

function handleExternalDrop(info) {
    const event = info.event;
    const teamId = event.extendedProps?.team_id;
    const date = event.start.toISOString().slice(0, 10);
    const startTime = event.start.toTimeString().slice(0, 5);
    const end = event.end || new Date(event.start.getTime() + 3600000);
    const endTime = end.toTimeString().slice(0, 5);
    const resource = event.getResources()[0];
    const fieldId = resource?.id;

    event.remove();
    openModal({ teamId, date, startTime, endTime, fieldId, fieldName: resource?.title });
}

function handleSelect(info) {
    if (!isManager) return;
    const resource = info.resource;
    const fieldId = resource?.id;
    const date = info.start.toISOString().slice(0, 10);
    const startTime = info.start.toTimeString().slice(0, 5);
    const endTime = info.end.toTimeString().slice(0, 5);

    openModal({ teamId: '', date, startTime, endTime, fieldId, fieldName: resource?.title });
    calendarRef.value?.getApi()?.unselect();
}

function openModal({ teamId, date, startTime, endTime, fieldId, fieldName }) {
    // Pre-fill from the interaction (drop target or selection)
    let resolvedFieldId = (fieldId && !fieldId.startsWith('loc-')) ? fieldId : '';
    let resolvedFieldName = fieldName || '';
    let resolvedTeamId = teamId || '';

    // Fall back to active calendar filters if not set by the interaction
    if (!resolvedFieldId && filters.value.field_id) {
        resolvedFieldId = filters.value.field_id;
        const f = availableFields.value.find(f => f.id == resolvedFieldId);
        if (f) {
            const loc = props.locations.find(l => (l.fields || []).some(fl => fl.id == f.id));
            resolvedFieldName = f.name + (loc ? ` @ ${loc.name}` : '');
        }
    }
    if (!resolvedTeamId && filters.value.team_id) {
        resolvedTeamId = filters.value.team_id;
    }

    modalForm.team_id = resolvedTeamId;
    modalForm.date = date;
    modalForm.start_time = startTime;
    modalForm.end_time = endTime;
    modalForm.field_id = resolvedFieldId;
    modalFieldName.value = resolvedFieldName;
    modalForm.title = '';
    modalForm.type = 'practice';
    modalForm.clearErrors();
    showModal.value = true;
}

function handleEventClick(info) {
    if (isManager) {
        window.location.href = route('leagues.schedule.edit', [props.league.slug, info.event.id]);
    }
}

function submitModal() {
    // Capture details before submit resets things
    const team = props.teams.find(t => t.id == modalForm.team_id);
    const field = availableFields.value.find(f => f.id == modalForm.field_id);
    const location = field ? props.locations.find(l => (l.fields || []).some(f => f.id == field.id)) : null;
    const details = {
        teamName: team?.name || 'Unknown',
        teamColor: team?.color_code || '#3B82F6',
        date: modalForm.date,
        startTime: modalForm.start_time,
        endTime: modalForm.end_time,
        fieldName: field?.name || modalFieldName.value || '',
        locationName: location?.name || '',
        type: modalForm.type,
        title: modalForm.title,
    };

    modalSubmitting.value = true;
    modalForm.clearErrors();

    axios.post(route('leagues.schedule.store', props.league.slug), {
        season_id: modalForm.season_id,
        field_id: modalForm.field_id,
        team_id: modalForm.team_id,
        date: modalForm.date,
        start_time: modalForm.start_time,
        end_time: modalForm.end_time,
        type: modalForm.type,
        title: modalForm.title,
    }).then(() => {
        showModal.value = false;
        calendarRef.value?.getApi()?.refetchEvents();

        confirmationDetails.value = details;
        showConfirmation.value = true;
    }).catch((error) => {
        const errs = error.response?.data?.errors || {};
        // Conflicts come back as errors.conflicts array
        if (errs.conflicts) {
            modalForm.setError('conflicts', errs.conflicts);
        } else {
            // Validation errors
            Object.entries(errs).forEach(([key, msgs]) => {
                modalForm.setError(key, Array.isArray(msgs) ? msgs[0] : msgs);
            });
        }
    }).finally(() => {
        modalSubmitting.value = false;
    });
}

function showError(messages) {
    errorMessages.value = Array.isArray(messages) ? messages : [messages];
    setTimeout(() => { errorMessages.value = []; }, 5000);
}
</script>

<template>
    <Head :title="`${league.name} - Calendar`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Calendar</h2>
            <div class="flex items-center gap-2">
                <Link :href="route('leagues.schedule.index', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">List</Link>
                <Link v-if="isManager" :href="route('leagues.schedule.bulk', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">Bulk</Link>
            </div>
        </div>

        <FlashMessage />

        <!-- Filter Bar -->
        <div class="mt-2 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Filter:</span>

            <select v-model="filters.division_id" class="w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                <option value="">All Divisions</option>
                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>

            <select v-model="filters.team_id" class="w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                <option value="">All Teams</option>
                <option v-for="t in filteredTeams" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>

            <select v-model="filters.location_id" class="w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                <option value="">All Locations</option>
                <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>

            <select v-model="filters.field_id" class="w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                <option value="">All Fields</option>
                <option v-for="f in availableFields" :key="f.id" :value="f.id">{{ f.name }}</option>
            </select>

            <button v-if="hasFilters" @click="clearFilters" class="text-[11px] font-medium text-brand-600 hover:text-brand-700">Clear</button>
        </div>

        <div v-if="errorMessages.length" class="fixed top-3 right-3 z-50 max-w-sm rounded-lg bg-red-500 px-3 py-2 text-xs text-white shadow-lg">
            <p class="font-semibold">Conflict:</p>
            <ul class="mt-1 list-disc pl-4"><li v-for="msg in errorMessages" :key="msg">{{ msg }}</li></ul>
        </div>

        <div class="mt-2 flex gap-3">
            <!-- Team drag sidebar -->
            <div v-if="isManager && teams.length" class="hidden w-44 shrink-0 lg:block">
                <div class="sticky top-3 rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-2 py-1.5">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Drag to Schedule</h3>
                        <select v-model="sidebarDivision" class="mt-1 w-full rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px]">
                            <option value="">All Divisions</option>
                            <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div ref="teamListRef" class="max-h-[490px] overflow-y-auto p-1">
                        <div
                            v-for="team in sidebarTeams"
                            :key="team.id"
                            :data-team-id="team.id"
                            :data-team-name="team.name"
                            :data-team-color="team.color_code || '#3B82F6'"
                            class="mb-0.5 flex cursor-grab items-center gap-1.5 rounded px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-50 active:cursor-grabbing"
                        >
                            <span class="inline-block h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: team.color_code || '#3B82F6' }"></span>
                            <span class="truncate">{{ team.name }}</span>
                        </div>
                        <div v-if="sidebarTeams.length === 0" class="px-2 py-3 text-center text-[10px] text-gray-400">No teams</div>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white p-2">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- Quick Schedule Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <form @submit.prevent="submitModal" class="p-4">
                <h3 class="text-sm font-semibold text-gray-900">New Schedule Entry</h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ modalForm.date }}
                    <span v-if="modalForm.start_time"> &middot; {{ modalForm.start_time }}-{{ modalForm.end_time }}</span>
                    <span v-if="modalFieldName"> &middot; {{ modalFieldName }}</span>
                </p>

                <div v-if="Object.keys(modalForm.errors).length" class="mt-2 rounded bg-red-50 p-2 text-xs text-red-700">
                    <ul class="list-disc pl-4">
                        <template v-if="modalForm.errors.conflicts">
                            <li v-for="e in modalForm.errors.conflicts" :key="e">{{ e }}</li>
                        </template>
                        <template v-else>
                            <li v-for="(msg, key) in modalForm.errors" :key="key">{{ msg }}</li>
                        </template>
                    </ul>
                </div>

                <div class="mt-3 space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="m_team" value="Team" class="text-xs" />
                            <select id="m_team" v-model="modalForm.team_id" class="mt-1 block w-full" required>
                                <option value="">-- Select --</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="m_field" value="Field" class="text-xs" />
                            <select id="m_field" v-model="modalForm.field_id" class="mt-1 block w-full" required>
                                <option value="">-- Select --</option>
                                <template v-for="loc in locations" :key="loc.id">
                                    <option v-for="f in (loc.fields || [])" :key="f.id" :value="f.id">{{ f.name }} @ {{ loc.name }}</option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <InputLabel for="m_date" value="Date" class="text-xs" />
                            <TextInput id="m_date" v-model="modalForm.date" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="m_start" value="Start" class="text-xs" />
                            <TextInput id="m_start" v-model="modalForm.start_time" type="time" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="m_end" value="End" class="text-xs" />
                            <TextInput id="m_end" v-model="modalForm.end_time" type="time" class="mt-1 block w-full" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel for="m_type" value="Type" class="text-xs" />
                            <select id="m_type" v-model="modalForm.type" class="mt-1 block w-full">
                                <option value="practice">Practice</option>
                                <option value="game">Game</option>
                                <option value="scrimmage">Scrimmage</option>
                                <option value="tournament">Tournament</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="m_title" value="Title" class="text-xs" />
                            <TextInput id="m_title" v-model="modalForm.title" type="text" class="mt-1 block w-full" placeholder="Optional" />
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="rounded px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900">Cancel</button>
                    <PrimaryButton :disabled="modalSubmitting || !modalForm.team_id || !modalForm.field_id">Schedule</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmation Modal -->
        <Modal :show="showConfirmation" @close="showConfirmation = false" max-width="sm">
            <div class="p-5 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-gray-900">Scheduled!</h3>
                <div class="mt-3 space-y-1 text-sm text-gray-700">
                    <p>
                        <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: confirmationDetails.teamColor }"></span>
                        <strong>{{ confirmationDetails.teamName }}</strong>
                        <span class="text-gray-400">&mdash; {{ confirmationDetails.type }}</span>
                    </p>
                    <p>
                        {{ new Date(confirmationDetails.date + 'T00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                    </p>
                    <p>{{ confirmationDetails.startTime }} &ndash; {{ confirmationDetails.endTime }}</p>
                    <p v-if="confirmationDetails.fieldName" class="text-gray-500">
                        {{ confirmationDetails.fieldName }}<span v-if="confirmationDetails.locationName"> @ {{ confirmationDetails.locationName }}</span>
                    </p>
                    <p v-if="confirmationDetails.title" class="italic text-gray-500">{{ confirmationDetails.title }}</p>
                </div>
                <button @click="showConfirmation = false" class="mt-4 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700">
                    Done
                </button>
            </div>
        </Modal>
    </LeagueLayout>
</template>
