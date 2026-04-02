<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import axios from 'axios';
import { useWeatherIcons } from '@/Composables/useWeatherIcons';

const props = defineProps({
    league: Object,
    teams: Array,
    divisions: Array,
    locations: Array,
    token: String,
    weather: { type: Object, default: () => ({}) },
});

const { dayCellDidMount: weatherDayCellDidMount, dayHeaderContent: weatherDayHeaderContent } = useWeatherIcons(props.weather);

const calendarRef = ref(null);
const filtersOpen = ref(false);

// Responsive
const isMobile = ref(window.innerWidth < 1024);
onMounted(() => {
    window.addEventListener('resize', () => { isMobile.value = window.innerWidth < 1024; });
});

// localStorage persistence
const storeKey = `skedbetter-pub-v1-${props.token}`;
function loadState() {
    try { return JSON.parse(localStorage.getItem(storeKey)) || {}; } catch { return {}; }
}
function saveState(patch) {
    localStorage.setItem(storeKey, JSON.stringify({ ...loadState(), ...patch }));
}

const validViews = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];
const saved = (() => {
    const s = loadState();
    if (s.view && !validViews.includes(s.view)) s.view = null;
    if (s.date) {
        const d = new Date(s.date);
        if (isNaN(d.getTime()) || Math.abs(d - new Date()) > 365 * 86400000) s.date = null;
    }
    return s;
})();

// Filters
const filters = ref({
    division_id: saved.division_id || '',
    team_id: saved.team_id || '',
    location_id: saved.location_id || '',
    field_id: saved.field_id || '',
});

const availableFields = computed(() => {
    if (filters.value.location_id) {
        const loc = props.locations.find(l => l.id == filters.value.location_id);
        return loc?.fields || [];
    }
    return props.locations.flatMap(l => l.fields || []);
});

const viewFilteredTeams = computed(() => {
    if (filters.value.division_id) {
        return props.teams.filter(t => t.division_id == filters.value.division_id);
    }
    return props.teams;
});

const divisionOptions = computed(() => props.divisions.map(d => ({ value: d.id, label: d.name })));
const teamFilterOptions = computed(() => viewFilteredTeams.value.map(t => ({ value: t.id, label: t.name })));
const locationOptions = computed(() => props.locations.map(l => ({ value: l.id, label: l.name })));
const fieldFilterOptions = computed(() => availableFields.value.map(f => ({ value: f.id, label: f.name })));

const filterParams = computed(() => {
    const params = {};
    if (filters.value.division_id) params.division_id = filters.value.division_id;
    if (filters.value.team_id) params.team_id = filters.value.team_id;
    if (filters.value.location_id) params.location_id = filters.value.location_id;
    if (filters.value.field_id) params.field_id = filters.value.field_id;
    return params;
});

const hasFilters = computed(() => Object.values(filters.value).some(v => v));

function clearFilters() {
    filters.value = { division_id: '', team_id: '', location_id: '', field_id: '' };
}

watch(filters, (val) => {
    saveState({ division_id: val.division_id, team_id: val.team_id, location_id: val.location_id, field_id: val.field_id });
    calendarRef.value?.getApi()?.refetchEvents();
}, { deep: true });

watch(() => filters.value.division_id, () => {
    if (filters.value.team_id && filters.value.division_id) {
        const team = props.teams.find(t => t.id == filters.value.team_id);
        if (team && team.division_id != filters.value.division_id) {
            filters.value.team_id = '';
        }
    }
});

watch(() => filters.value.location_id, () => {
    if (filters.value.field_id && filters.value.location_id) {
        const field = availableFields.value.find(f => f.id == filters.value.field_id);
        if (!field) filters.value.field_id = '';
    }
});

// Time formatting
function fmt12(time24) {
    if (!time24) return '';
    const [h, m] = time24.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}

// Mobile event detail popup
const showDetail = ref(false);
const detail = ref({});

function clearTooltips() {
    document.querySelectorAll('.fc-hover-tooltip').forEach(el => el.remove());
}

onUnmounted(() => clearTooltips());

// Calendar options
const mobileDefault = isMobile.value ? 'listWeek' : 'timeGridWeek';

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
    initialView: saved.view || mobileDefault,
    initialDate: saved.date || undefined,
    headerToolbar: isMobile.value
        ? { left: 'prev,next', center: 'title', right: 'listWeek,dayGridMonth,timeGridDay' }
        : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
    views: {
        timeGridWeek: { buttonText: 'Week' },
        timeGridDay: { buttonText: 'Day' },
        dayGridMonth: { buttonText: 'Month' },
        listWeek: { buttonText: 'List' },
    },
    dayMaxEvents: 3,
    moreLinkClick: 'day',
    slotMinTime: '08:00:00',
    slotMaxTime: '21:00:00',
    slotDuration: '00:30:00',
    allDaySlot: false,
    nowIndicator: true,
    expandRows: true,
    height: 'auto',
    contentHeight: isMobile.value ? 'auto' : 560,
    editable: false,
    droppable: false,
    selectable: false,
    dayCellDidMount: (arg) => weatherDayCellDidMount(arg),
    dayHeaderContent: (arg) => weatherDayHeaderContent(arg),
    dateClick: (info) => {
        if (info.view.type === 'dayGridMonth') {
            const api = calendarRef.value?.getApi();
            if (api) api.changeView('timeGridDay', info.dateStr);
        }
    },
    eventDidMount: (info) => {
        if (info.event.display === 'background') return;
        if (info.el._tooltipBound) return;
        info.el._tooltipBound = true;
        const ev = info.event;

        let tooltip = null;
        info.el.addEventListener('mousedown', () => {
            if (tooltip) { tooltip.remove(); tooltip = null; }
        });
        info.el.addEventListener('mouseenter', () => {
            if (window.innerWidth < 1024) return;
            const ext = ev.extendedProps || {};
            const start = ev.start;
            const end = ev.end || new Date(start.getTime() + 3600000);
            const time = fmt12(start.toTimeString().slice(0, 5)) + ' – ' + fmt12(end.toTimeString().slice(0, 5));
            const team = ext.team_name || ev.title;
            const field = ext.field_name || '';
            const location = ext.location_name || '';
            const type = ext.type ? ext.type.charAt(0).toUpperCase() + ext.type.slice(1) : '';
            const status = ext.status ? ext.status.charAt(0).toUpperCase() + ext.status.slice(1) : '';

            tooltip = document.createElement('div');
            tooltip.className = 'fc-hover-tooltip';
            tooltip.innerHTML = `
                <div style="font-weight:600;font-size:12px;margin-bottom:3px;">${team}</div>
                <div style="font-size:11px;color:#6b7280;">${time}</div>
                ${field ? `<div style="font-size:11px;color:#9ca3af;">${field}${location ? ' @ ' + location : ''}</div>` : ''}
                ${type ? `<div style="font-size:10px;color:#9ca3af;margin-top:2px;">${type}${status ? ' · ' + status : ''}</div>` : ''}
            `;
            document.body.appendChild(tooltip);
            const rect = info.el.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 6 + window.scrollY + 'px';
        });
        info.el.addEventListener('mouseleave', () => {
            if (tooltip) { tooltip.remove(); tooltip = null; }
        });
    },
    eventClick: (info) => {
        if (info.event.display === 'background') return;
        if (window.innerWidth >= 1024) return; // Desktop uses hover tooltip
        clearTooltips();
        const ev = info.event;
        const ext = ev.extendedProps || {};
        detail.value = {
            team: ext.team_name || ev.title,
            time: fmt12(ev.start?.toTimeString().slice(0, 5)) + ' – ' + fmt12((ev.end || ev.start)?.toTimeString().slice(0, 5)),
            date: ev.start?.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }),
            field: ext.field_name || '',
            location: ext.location_name || '',
            type: ext.type ? ext.type.charAt(0).toUpperCase() + ext.type.slice(1) : '',
            status: ext.status ? ext.status.charAt(0).toUpperCase() + ext.status.slice(1) : '',
            color: ev.backgroundColor || '#3B82F6',
        };
        showDetail.value = true;
    },
    eventContent: (arg) => {
        if (arg.view.type === 'dayGridMonth') {
            const ext = arg.event.extendedProps || {};
            const time = arg.event.start ? fmt12(arg.event.start.toTimeString().slice(0, 5)) : '';
            const team = ext.team_name || arg.event.title;
            const color = arg.event.backgroundColor || '#3B82F6';
            return { html: `<span class="fc-month-event" style="background:${color}; color:#fff; border-radius:3px; padding:1px 4px;"><span class="fc-month-time">${time}</span> ${team}</span>` };
        }
        return true;
    },
    events: function (info, successCallback, failureCallback) {
        const params = { start: info.startStr.slice(0, 10), end: info.endStr.slice(0, 10), ...filterParams.value };
        const qs = new URLSearchParams(params).toString();
        axios.get(`/p/${props.token}/events?${qs}`).then(res => {
            successCallback(res.data);
        }).catch(err => {
            failureCallback(err);
        });
    },
    datesSet: (info) => {
        saveState({ view: info.view.type, date: info.start.toISOString().slice(0, 10) });
    },
});
</script>

<template>
    <Head :title="`${league.name} — Schedule`" />
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="border-b border-gray-200 bg-white px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ league.name }}</h1>
                <p class="text-xs text-gray-500">Public Schedule</p>
            </div>
            <a href="/login" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition">Login</a>
        </div>

        <div class="mx-auto max-w-screen-2xl px-3 py-3">
            <!-- Filter Bar -->
            <div class="rounded-t-lg border border-gray-200 bg-white px-2 py-1.5 sm:px-3 sm:py-2">
                <button @click="filtersOpen = !filtersOpen" class="flex w-full items-center justify-between lg:hidden">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Filters</span>
                    <div class="flex items-center gap-2">
                        <span v-if="hasFilters" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Active</span>
                        <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': filtersOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </button>
                <div :class="filtersOpen ? 'mt-2' : 'hidden'" class="lg:!block">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center lg:gap-2">
                        <SearchSelect v-model="filters.division_id" :options="divisionOptions" placeholder="All Divisions" class="w-full lg:w-40" />
                        <SearchSelect v-model="filters.team_id" :options="teamFilterOptions" placeholder="All Teams" class="w-full lg:w-40" />
                        <SearchSelect v-model="filters.location_id" :options="locationOptions" placeholder="All Locations" class="w-full lg:w-40" />
                        <SearchSelect v-model="filters.field_id" :options="fieldFilterOptions" placeholder="All Fields" class="w-full lg:w-40" />
                        <button v-if="hasFilters" @click="clearFilters" class="text-[11px] font-medium text-blue-600 hover:text-blue-700 py-2 lg:py-0">Clear</button>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="rounded-b-lg border border-t-0 border-gray-200 bg-white p-1 sm:p-2">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- Mobile event detail overlay -->
        <div v-if="showDetail" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" @click.self="showDetail = false">
            <div class="fixed inset-0 bg-black/30" @click="showDetail = false"></div>
            <div class="relative w-full max-w-sm rounded-t-xl sm:rounded-xl bg-white p-4 shadow-xl">
                <button @click="showDetail = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-3 w-3 rounded-full" :style="{ background: detail.color }"></span>
                    <span class="text-sm font-semibold text-gray-900">{{ detail.team }}</span>
                </div>
                <div class="space-y-1 text-xs text-gray-600">
                    <div>{{ detail.date }}</div>
                    <div>{{ detail.time }}</div>
                    <div v-if="detail.field" class="text-gray-500">{{ detail.field }}<span v-if="detail.location"> @ {{ detail.location }}</span></div>
                    <div v-if="detail.type" class="text-gray-400">{{ detail.type }}<span v-if="detail.status"> &middot; {{ detail.status }}</span></div>
                </div>
            </div>
        </div>
    </div>
</template>
