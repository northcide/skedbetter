<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
    coachTeamIds: { type: Array, default: () => [] },
    teamWindowStatus: { type: Array, default: () => [] },
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const isCoach = props.userRole === 'coach';
const canSchedule = isManager || isCoach;

// Coaches can only schedule their own teams
const schedulableTeams = computed(() => {
    if (isManager) return props.teams;
    return props.teams.filter(t => props.coachTeamIds.includes(t.id));
});

// Divisions the user can schedule (for coaches: only divisions their teams are in)
const schedulableDivisions = computed(() => {
    if (isManager) return props.divisions;
    const divIds = new Set(schedulableTeams.value.map(t => t.division_id));
    return props.divisions.filter(d => divIds.has(d.id));
});
const calendarRef = ref(null);
const teamListRef = ref(null);
const errorMessages = ref([]);
const filtersOpen = ref(false);

// Mobile detection
const mobileQuery = typeof window !== 'undefined' ? window.matchMedia('(max-width: 767px)') : null;
const isMobile = ref(mobileQuery?.matches ?? false);
function onMobileChange(e) { isMobile.value = e.matches; }
onMounted(() => mobileQuery?.addEventListener('change', onMobileChange));
onUnmounted(() => mobileQuery?.removeEventListener('change', onMobileChange));
const showModal = ref(false);
const showConfirmation = ref(false);
const modalSubmitting = ref(false);
const editingEntryId = ref(null); // null = new entry, number = editing existing
const confirmationDetails = ref({});
const highlightStartTime = ref(false);
const modalFieldName = ref('');
const liveErrors = ref([]);
const liveWarnings = ref([]);
const validating = ref(false);
let validateTimeout = null;

function liveValidate() {
    clearTimeout(validateTimeout);
    liveErrors.value = [];
    liveWarnings.value = [];

    if (!modalForm.team_id || !modalForm.field_id || !modalForm.date || !modalForm.start_time || !modalForm.end_time) return;
    if (modalForm.start_time >= modalForm.end_time) return;

    validateTimeout = setTimeout(() => {
        validating.value = true;
        axios.post(route('leagues.schedule.validate', props.league.slug), {
            field_id: modalForm.field_id,
            team_id: modalForm.team_id,
            date: modalForm.date,
            start_time: modalForm.start_time,
            end_time: modalForm.end_time,
            exclude_entry_id: editingEntryId.value || null,
        }).then(res => {
            liveErrors.value = res.data.valid ? [] : (res.data.errors || []);
            liveWarnings.value = res.data.warnings || [];
        }).catch(() => {
            liveErrors.value = [];
            liveWarnings.value = [];
        }).finally(() => {
            validating.value = false;
        });
    }, 400);
}

// Persist state in localStorage keyed by league
const STORE_VERSION = 2;
const storeKey = `skedbetter-cal-v${STORE_VERSION}-${props.league.id}`;

function loadState() {
    try { return JSON.parse(localStorage.getItem(storeKey)) || {}; } catch { return {}; }
}

function saveState(patch) {
    const current = loadState();
    localStorage.setItem(storeKey, JSON.stringify({ ...current, ...patch }));
}

const validViews = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'resourceTimelineDay', 'listWeek'];

function validateSavedState(s) {
    // Validate view
    if (s.view && !validViews.includes(s.view)) s.view = null;
    // Validate date — must be a valid date within 1 year
    if (s.date) {
        const d = new Date(s.date);
        const now = new Date();
        if (isNaN(d.getTime()) || Math.abs(d - now) > 365 * 86400000) s.date = null;
    }
    // Clear stale filter IDs that might reference deleted records
    // (they'll just show "All" which is safe)
    return s;
}

const saved = validateSavedState(loadState());
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

// All teams filtered by division (for calendar view filter — everyone can see all)
const viewFilteredTeams = computed(() => {
    if (filters.value.division_id) {
        return props.teams.filter(t => t.division_id == filters.value.division_id);
    }
    return props.teams;
});

// Schedulable teams filtered by division (for drag sidebar — only user's teams)
const filteredTeams = computed(() => {
    if (filters.value.division_id) {
        return schedulableTeams.value.filter(t => t.division_id == filters.value.division_id);
    }
    return schedulableTeams.value;
});

// Teams filtered by sidebar division picker (independent from calendar filter)
const sidebarTeams = computed(() => {
    if (sidebarDivision.value) {
        return schedulableTeams.value.filter(t => t.division_id == sidebarDivision.value);
    }
    return schedulableTeams.value;
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
    calendarRef.value?.getApi()?.refetchEvents();
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

// Check if a field is blocked for the currently selected team's division
// Build a set of field IDs each division is assigned to
const divisionFieldMap = computed(() => {
    const map = {};
    props.locations.forEach(loc => {
        (loc.fields || []).forEach(f => {
            (f.allowed_divisions || []).forEach(d => {
                if (!map[d.id]) map[d.id] = new Set();
                map[d.id].add(f.id);
            });
        });
    });
    return map;
});

function getFieldBlockReason(field) {
    if (!modalForm.team_id) return null;
    const team = props.teams.find(t => t.id == modalForm.team_id);
    if (!team) return null;

    // Check 1: field has restrictions and division not in list
    const allowed = field.allowed_divisions;
    if (allowed && allowed.length > 0) {
        const divEntry = allowed.find(d => d.id === team.division_id);
        if (!divEntry) return 'restricted';
    }

    // Check 2: division has field assignments — can only use those fields
    const divFields = divisionFieldMap.value[team.division_id];
    if (divFields && divFields.size > 0 && !divFields.has(field.id)) return 'not assigned';

    return null;
}

function getFieldWindowWarning(field) {
    if (!modalForm.team_id) return null;
    const team = props.teams.find(t => t.id == modalForm.team_id);
    if (!team) return null;

    const division = props.divisions.find(d => d.id === team.division_id);
    if (!division?.booking_window) return null;

    const w = division.booking_window;
    if (w.window_type === 'calendar' && w.opens_date) {
        const dateStr = String(w.opens_date).split('T')[0];
        const [y, m, d] = dateStr.split('-').map(Number);
        const opens = new Date(y, m - 1, d);
        if (new Date() < opens) {
            return `${w.name}: opens ${opens.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
        }
    } else if (w.window_type === 'rolling' && w.rolling_days && modalForm.date) {
        const [ey, em, ed] = modalForm.date.split('-').map(Number);
        const eventDate = new Date(ey, em - 1, ed);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const daysAhead = Math.ceil((eventDate - today) / 86400000);
        if (daysAhead > w.rolling_days) {
            return `${w.name}: ${w.rolling_days}d ahead only`;
        }
    }
    return null;
}

const isAdmin = ['superadmin', 'league_admin'].includes(props.userRole);

function isFieldBlocked(field) {
    // Admins can override everything
    if (isAdmin) return false;
    if (getFieldBlockReason(field)) return true;
    if (getFieldWindowWarning(field)) return true;
    return false;
}

// Time slot generation (6:00 AM to 10:00 PM in 15-min increments)
function fmt12(time24) {
    if (!time24) return '';
    const [h, m] = time24.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}

const timeSlots = computed(() => {
    const slots = [];
    for (let h = 6; h <= 21; h++) {
        for (let m = 0; m < 60; m += 15) {
            const val = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            slots.push({ value: val, label: fmt12(val) });
        }
    }
    return slots;
});

const durationOptions = [
    { value: 15, label: '15 min' },
    { value: 30, label: '30 min' },
    { value: 45, label: '45 min' },
    { value: 60, label: '1 hour' },
    { value: 75, label: '1h 15m' },
    { value: 90, label: '1h 30m' },
    { value: 120, label: '2 hours' },
];

const modalDuration = ref(60);

// Auto-calculate end_time when start_time or duration changes
watch([() => modalForm.start_time, modalDuration], ([start, dur]) => {
    if (!start) return;
    const [h, m] = start.split(':').map(Number);
    const totalMin = h * 60 + m + dur;
    const endH = Math.floor(totalMin / 60);
    const endM = totalMin % 60;
    if (endH < 24) {
        modalForm.end_time = `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`;
    }
});

onMounted(() => {
    if (teamListRef.value && canSchedule) {
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

const mobileDefault = isMobile.value ? 'listWeek' : 'timeGridWeek';

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, resourceTimelinePlugin, interactionPlugin],
    initialView: saved.view || mobileDefault,
    initialDate: saved.date || undefined,
    headerToolbar: isMobile.value
        ? { left: 'prev,next', center: 'title', right: 'listWeek,dayGridMonth,timeGridDay' }
        : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,resourceTimelineDay' },
    views: {
        resourceTimelineDay: { buttonText: 'Fields' },
        timeGridWeek: { buttonText: 'Week' },
        timeGridDay: { buttonText: 'Day' },
        dayGridMonth: { buttonText: 'Month' },
        listWeek: { buttonText: 'List' },
    },
    dayMaxEvents: 3,
    moreLinkClick: 'day',
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    allDaySlot: false,
    nowIndicator: true,
    expandRows: true,
    height: 'auto',
    contentHeight: isMobile.value ? 'auto' : 560,
    dateClick: (info) => {
        if (info.view.type === 'dayGridMonth') {
            const api = calendarRef.value?.getApi();
            if (api) {
                api.changeView('timeGridDay', info.dateStr);
            }
        } else if (canSchedule && (info.view.type === 'timeGridDay' || info.view.type === 'timeGridWeek')) {
            const date = info.date.toISOString().slice(0, 10);
            const startTime = info.date.toTimeString().slice(0, 5);
            openModal({ date, startTime });
        }
    },
    eventContent: (arg) => {
        const view = arg.view.type;
        if (view === 'dayGridMonth') {
            const ext = arg.event.extendedProps || {};
            const time = arg.event.start ? fmt12(arg.event.start.toTimeString().slice(0, 5)) : '';
            const team = ext.team_name || arg.event.title;
            const color = arg.event.backgroundColor || '#3B82F6';
            return { html: `<span class="fc-month-event" style="background:${color}; color:#fff; border-radius:3px; padding:1px 4px;"><span class="fc-month-time">${time}</span> ${team}</span>` };
        }
        return true;
    },
    resourceAreaHeaderContent: 'Fields',
    resourceAreaWidth: '150px',
    editable: false,  // per-event editable from server
    droppable: canSchedule,
    selectable: canSchedule,
    selectMirror: true,
    eventResourceEditable: false,  // per-event from server
    eventResizableFromStart: true,
    resources: {
        url: route('leagues.schedule.resources', props.league.slug),
        method: 'GET',
    },
    events: function(info, successCallback, failureCallback) {
        const baseUrl = route('leagues.schedule.events', props.league.slug);
        const params = { start: info.startStr.slice(0, 10), end: info.endStr.slice(0, 10), ...filterParams.value };
        const qs = new URLSearchParams(params).toString();
        axios.get(`${baseUrl}?${qs}`).then(res => {
            console.log('Events loaded:', res.data.length, 'for', params.start, '-', params.end);
            successCallback(res.data);
        }).catch(err => {
            console.error('Events fetch error:', err);
            failureCallback(err);
        });
    },
    datesSet: (info) => {
        saveState({ view: info.view.type, date: info.start.toISOString().slice(0, 10) });
    },
    eventDrop: (info) => handleEventDrop(info),
    eventResize: (info) => handleEventResize(info),
    eventReceive: (info) => handleExternalDrop(info),
    select: (info) => handleSelect(info),
    eventClick: (info) => handleEventClick(info),
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
    resourceGroupField: 'parentId',
    schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
});

function handleEventDrop(info) {
    clearTooltips();
    try {
        const event = info.event;
        const ext = event.extendedProps || {};
        const resource = event.getResources()[0];
        let dropFieldId = resource?.id != null ? String(resource.id) : null;

        if (dropFieldId && dropFieldId.startsWith('loc-')) { info.revert(); return; }

        const newDate = event.start.toISOString().slice(0, 10);
        const newStartTime = event.start.toTimeString().slice(0, 5);
        const end = event.end || new Date(event.start.getTime() + 3600000);
        const newEndTime = end.toTimeString().slice(0, 5);
        const fieldId = (dropFieldId && !dropFieldId.startsWith('loc-')) ? dropFieldId : (ext.field_id || '');

        // Save directly via move endpoint — no modal
        axios.patch(route('leagues.schedule.move', [props.league.slug, event.id]), {
            date: newDate, start_time: newStartTime, end_time: newEndTime, field_id: fieldId,
        }, { headers: { Accept: 'application/json' } }).then(() => {
            clearTooltips();
            setTimeout(() => calendarRef.value?.getApi()?.refetchEvents(), 300);
        }).catch((error) => {
            console.error('Move error:', error.response?.data);
            info.revert();
            const msgs = error.response?.data?.errors || ['Move failed'];
            showError(Array.isArray(msgs) ? msgs : [msgs]);
        });
    } catch (e) {
        console.error('handleEventDrop error:', e);
        info.revert();
    }
}

// Remove any lingering tooltips
function clearTooltips() {
    document.querySelectorAll('.fc-hover-tooltip').forEach(el => el.remove());
}

function handleEventResize(info) {
    clearTooltips();
    const event = info.event;
    const ext = event.extendedProps || {};
    const date = event.start.toISOString().slice(0, 10);
    const startTime = event.start.toTimeString().slice(0, 5);
    const end = event.end || new Date(event.start.getTime() + 3600000);
    const endTime = end.toTimeString().slice(0, 5);
    const fieldId = ext.field_id || '';

    axios.patch(route('leagues.schedule.move', [props.league.slug, event.id]), {
        date, start_time: startTime, end_time: endTime, field_id: fieldId,
    }, { headers: { Accept: 'application/json' } }).then(() => {
        clearTooltips();
        setTimeout(() => calendarRef.value?.getApi()?.refetchEvents(), 300);
    }).catch((error) => {
        console.error('Resize error:', error.response?.data);
        info.revert();
        const msgs = error.response?.data?.errors || ['Resize failed'];
        showError(Array.isArray(msgs) ? msgs : [msgs]);
    });
}

function handleExternalDrop(info) {
    const event = info.event;
    const teamId = event.extendedProps?.team_id;
    const date = event.start.toISOString().slice(0, 10);
    const startTime = event.start.toTimeString().slice(0, 5);
    const end = event.end || new Date(event.start.getTime() + 3600000);
    const endTime = end.toTimeString().slice(0, 5);
    const resource = event.getResources()[0];
    const fieldId = resource?.id != null ? String(resource.id) : null;

    event.remove();
    openModal({ teamId, date, startTime, endTime, fieldId, fieldName: resource?.title });
}

function handleSelect(info) {
    if (!canSchedule) return;
    // In month view, navigate to day view instead of opening modal
    if (info.view.type === 'dayGridMonth') {
        const api = calendarRef.value?.getApi();
        if (api) {
            api.changeView('timeGridDay', info.start.toISOString().slice(0, 10));
            api.unselect();
        }
        return;
    }
    const resource = info.resource;
    const fieldId = resource?.id != null ? String(resource.id) : null;
    const date = info.start.toISOString().slice(0, 10);
    const startTime = info.start.toTimeString().slice(0, 5);
    const endTime = info.end.toTimeString().slice(0, 5);

    openModal({ teamId: '', date, startTime, endTime, fieldId, fieldName: resource?.title });
    calendarRef.value?.getApi()?.unselect();
}

function openModal({ entryId, teamId, date, startTime, endTime, fieldId, fieldName, type, title }) {
    editingEntryId.value = entryId || null;

    const fid = fieldId != null ? String(fieldId) : '';
    let resolvedFieldId = (fid && !fid.startsWith('loc-')) ? fid : '';
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
    // Auto-select if coach has only one schedulable team
    if (!resolvedTeamId && schedulableTeams.value.length === 1) {
        resolvedTeamId = schedulableTeams.value[0].id;
    }

    // Ensure team_id is numeric to match <option :value="t.id">
    modalForm.team_id = resolvedTeamId ? Number(resolvedTeamId) : '';
    modalForm.date = date;
    modalForm.field_id = resolvedFieldId ? Number(resolvedFieldId) : '';
    modalFieldName.value = resolvedFieldName;
    modalForm.title = title || '';
    modalForm.type = type || 'practice';
    modalForm.clearErrors();
    liveErrors.value = [];
    liveWarnings.value = [];

    // Calculate duration from start/end if both provided, otherwise default 60
    if (startTime && endTime) {
        const [sh, sm] = startTime.split(':').map(Number);
        const [eh, em] = endTime.split(':').map(Number);
        const dur = (eh * 60 + em) - (sh * 60 + sm);
        const closest = durationOptions.find(d => d.value === dur);
        modalDuration.value = closest ? dur : 60;
    } else {
        modalDuration.value = 60;
    }

    // Snap start to nearest 30-min slot
    let snappedStart = startTime || '';
    if (snappedStart) {
        const [sh, sm] = snappedStart.split(':').map(Number);
        const snappedMin = Math.round(sm / 15) * 15;
        const adjH = snappedMin >= 60 ? sh + 1 : sh;
        const adjM = snappedMin >= 60 ? 0 : snappedMin;
        snappedStart = `${String(adjH).padStart(2, '0')}:${String(adjM).padStart(2, '0')}`;
    }

    // Set start_time AFTER duration so the watcher calculates end_time
    modalForm.start_time = snappedStart;
    modalForm.end_time = endTime || '';
    highlightStartTime.value = !!snappedStart;
    if (highlightStartTime.value) {
        setTimeout(() => { highlightStartTime.value = false; }, 3000);
    }
    showModal.value = true;
    liveValidate();
}

// Re-validate when key scheduling fields change in the modal
watch(
    () => [modalForm.date, modalForm.start_time, modalForm.end_time],
    () => { if (showModal.value) liveValidate(); },
);

// When team changes, clear field if it's now blocked
watch(() => modalForm.team_id, () => {
    if (!showModal.value) return;
    if (modalForm.field_id) {
        // Find the field and check if it's blocked for the new team
        let field = null;
        for (const loc of props.locations) {
            field = (loc.fields || []).find(f => f.id == modalForm.field_id);
            if (field) break;
        }
        if (field && isFieldBlocked(field)) {
            modalForm.field_id = '';
        }
    }
    liveValidate();
});

// Event detail popover
const showEventDetail = ref(false);
const eventDetail = ref({});

function handleEventClick(info) {
    const ev = info.event;
    const ext = ev.extendedProps || {};
    eventDetail.value = {
        id: ev.id,
        title: ev.title,
        teamId: ext.team_id || null,
        teamName: ext.team_name || '',
        fieldName: ext.field_name || '',
        locationName: ext.location_name || '',
        type: ext.type || '',
        status: ext.status || '',
        date: ev.start?.toISOString().slice(0, 10) || '',
        startTime: ev.start?.toTimeString().slice(0, 5) || '',
        endTime: ev.end?.toTimeString().slice(0, 5) || '',
    };
    showEventDetail.value = true;
}

function cancelEvent() {
    const entryId = eventDetail.value.id;
    showEventDetail.value = false;

    // Remove from calendar immediately (try both string and number ID)
    const api = calendarRef.value?.getApi();
    if (api) {
        const ev = api.getEventById(entryId) || api.getEventById(String(entryId));
        if (ev) {
            ev.remove();
        } else {
            // Fallback: remove all events with this ID and refetch
            api.getEvents().forEach(e => {
                if (String(e.id) === String(entryId)) e.remove();
            });
        }
    }

    // Then delete on server
    axios.delete(route('leagues.schedule.destroy', [props.league.slug, entryId]), {
        headers: { Accept: 'application/json' },
    });
}

function editEvent() {
    const ev = eventDetail.value;
    showEventDetail.value = false;

    // Resolve field_id from name
    let fieldId = '';
    for (const loc of props.locations) {
        const f = (loc.fields || []).find(f => f.name === ev.fieldName);
        if (f) { fieldId = f.id; break; }
    }

    openModal({
        entryId: ev.id,
        teamId: ev.teamId,
        date: ev.date,
        startTime: ev.startTime,
        endTime: ev.endTime,
        fieldId: fieldId,
        fieldName: ev.fieldName,
        type: ev.type,
        title: ev.title?.replace(/^.+ - /, '') || '',
    });
}

function buildDetails() {
    const team = props.teams.find(t => t.id == modalForm.team_id);
    const field = availableFields.value.find(f => f.id == modalForm.field_id);
    const location = field ? props.locations.find(l => (l.fields || []).some(fl => fl.id == field.id)) : null;
    return {
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
}

// Step 1: form modal -> show confirmation
function submitModal() {
    // Basic client-side check
    if (!modalForm.team_id || !modalForm.field_id || !modalForm.date || !modalForm.start_time || !modalForm.end_time) {
        return;
    }
    confirmationDetails.value = buildDetails();
    showModal.value = false;
    showConfirmation.value = true;
}

// Step 2: confirmation -> actually save (create or update)
function confirmSchedule() {
    modalSubmitting.value = true;

    const payload = {
        season_id: modalForm.season_id,
        field_id: modalForm.field_id,
        team_id: modalForm.team_id,
        date: modalForm.date,
        start_time: modalForm.start_time,
        end_time: modalForm.end_time,
        type: modalForm.type,
        title: modalForm.title,
    };

    const headers = { Accept: 'application/json' };

    const isEdit = !!editingEntryId.value;
    console.log('confirmSchedule:', isEdit ? 'UPDATE ' + editingEntryId.value : 'CREATE', payload);

    const request = isEdit
        ? axios.put(route('leagues.schedule.update', [props.league.slug, editingEntryId.value]), { ...payload, status: 'confirmed', notes: '' }, { headers })
        : axios.post(route('leagues.schedule.store', props.league.slug), payload, { headers });

    request.then((res) => {
        console.log('Schedule save success:', res.data);
        showConfirmation.value = false;
        editingEntryId.value = null;
        setTimeout(() => calendarRef.value?.getApi()?.refetchEvents(), 200);
    }).catch((error) => {
        console.error('Schedule save error:', error.response?.status, error.response?.data);
        showConfirmation.value = false;
        showModal.value = true;
        const errs = error.response?.data?.errors || {};
        const msg = error.response?.data?.message;
        if (errs.conflicts) {
            modalForm.setError('conflicts', errs.conflicts);
        } else if (Object.keys(errs).length) {
            Object.entries(errs).forEach(([key, msgs]) => {
                modalForm.setError(key, Array.isArray(msgs) ? msgs[0] : msgs);
            });
        } else {
            modalForm.setError('conflicts', [msg || 'An error occurred. Please try again.']);
        }
    }).finally(() => {
        modalSubmitting.value = false;
    });
}

// Cancel from confirmation -> go back to form
function cancelConfirmation() {
    showConfirmation.value = false;
    showModal.value = true;
}

function showError(messages) {
    errorMessages.value = Array.isArray(messages) ? messages : [messages];
    setTimeout(() => { errorMessages.value = []; }, 5000);
}
</script>

<template>
    <Head :title="`${league.name} - Calendar`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between sm:mb-1">
            <h2 class="text-base sm:text-lg font-semibold text-gray-900">Calendar</h2>
            <div class="flex items-center gap-2">
                <Link :href="route('leagues.schedule.index', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">List</Link>
                <Link v-if="isManager" :href="route('leagues.schedule.bulk', league.slug)" class="text-xs text-gray-500 hover:text-gray-700">Bulk</Link>
            </div>
        </div>

        <FlashMessage />

        <!-- Booking Window Status (coaches) -->
        <div v-if="teamWindowStatus.length" class="mt-1 sm:mt-2 space-y-1">
            <div v-for="tw in teamWindowStatus" :key="tw.team_id"
                class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs"
                :class="tw.window_open ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200'">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: tw.color_code || '#3B82F6' }"></span>
                <span class="font-medium" :class="tw.window_open ? 'text-green-800' : 'text-amber-800'">{{ tw.team_name }}</span>
                <span v-if="tw.window_open" class="text-green-600">Booking open</span>
                <span v-else class="text-amber-600">{{ tw.window_name }}: {{ tw.window_description }}</span>
            </div>
        </div>

        <div v-if="errorMessages.length" class="fixed top-3 right-3 z-50 max-w-sm rounded-lg bg-red-500 px-3 py-2 text-xs text-white shadow-lg">
            <p class="font-semibold">Conflict:</p>
            <ul class="mt-1 list-disc pl-4"><li v-for="msg in errorMessages" :key="msg">{{ msg }}</li></ul>
        </div>

        <div class="mt-1 sm:mt-2 flex gap-3">
            <!-- Team drag sidebar -->
            <div v-if="canSchedule && teams.length" class="hidden w-44 shrink-0 lg:block">
                <div class="sticky top-3 rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-2 py-1.5">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Drag to Schedule</h3>
                        <select v-model="sidebarDivision" class="mt-1 w-full rounded border-gray-200 py-0.5 pl-1.5 pr-6 text-[11px]">
                            <option value="">{{ schedulableDivisions.length === 1 ? schedulableDivisions[0].name : (isCoach ? 'My Teams' : 'All Divisions') }}</option>
                            <option v-for="d in schedulableDivisions" :key="d.id" :value="d.id" v-show="schedulableDivisions.length > 1">{{ d.name }}</option>
                        </select>
                    </div>
                    <div ref="teamListRef" class="max-h-[490px] overflow-y-auto p-1">
                        <div
                            v-for="team in sidebarTeams"
                            :key="team.id"
                            :data-team-id="team.id"
                            :data-team-name="team.name"
                            :data-team-color="team.color_code || '#3B82F6'"
                            class="mb-0.5 flex cursor-grab items-start gap-1.5 rounded px-2 py-1 transition hover:bg-gray-50 active:cursor-grabbing"
                        >
                            <span class="mt-0.5 inline-block h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: team.color_code || '#3B82F6' }"></span>
                            <div class="min-w-0">
                                <span class="block truncate text-[11px] font-medium text-gray-700">{{ team.name }}</span>
                                <span v-if="team.contact_name" class="block truncate text-[9px] text-gray-400 leading-tight">{{ team.contact_name }}</span>
                            </div>
                        </div>
                        <div v-if="sidebarTeams.length === 0" class="px-2 py-3 text-center text-[10px] text-gray-400">No teams</div>
                    </div>
                </div>
            </div>

            <!-- Calendar + Filter -->
            <div class="min-w-0 flex-1">
                <!-- Filter Bar -->
                <div class="rounded-t-lg border border-gray-200 bg-white px-2 py-1.5 sm:px-3 sm:py-2">
                    <!-- Mobile: collapsible toggle -->
                    <button @click="filtersOpen = !filtersOpen" class="flex w-full items-center justify-between lg:hidden">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Filters</span>
                        <div class="flex items-center gap-2">
                            <span v-if="hasFilters" class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-medium text-brand-700">Active</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': filtersOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </button>
                    <div :class="filtersOpen ? 'mt-2' : 'hidden'" class="lg:!block">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center lg:gap-2">
                            <select v-model="filters.division_id" class="w-full lg:w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                                <option value="">All Divisions</option>
                                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                            <select v-model="filters.team_id" class="w-full lg:w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                                <option value="">All Teams</option>
                                <option v-for="t in viewFilteredTeams" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <select v-model="filters.location_id" class="w-full lg:w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                                <option value="">All Locations</option>
                                <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                            <select v-model="filters.field_id" class="w-full lg:w-auto rounded-md border-gray-200 py-1 pl-2 pr-7 text-xs">
                                <option value="">All Fields</option>
                                <option v-for="f in availableFields" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                            <button v-if="hasFilters" @click="clearFilters" class="text-[11px] font-medium text-brand-600 hover:text-brand-700 py-2 lg:py-0">Clear</button>
                        </div>
                    </div>
                </div>
                <!-- Calendar -->
                <div class="rounded-b-lg border border-t-0 border-gray-200 bg-white p-1 sm:p-2">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>
            </div>
        </div>

        <!-- Mobile FAB: New Entry -->
        <button v-if="canSchedule"
            @click="openModal({ date: new Date().toISOString().slice(0, 10) })"
            class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg active:bg-brand-700 lg:hidden"
            title="New Entry">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        </button>

        <!-- Quick Schedule Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <form @submit.prevent="submitModal" class="p-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ editingEntryId ? 'Edit Schedule Entry' : 'New Schedule Entry' }}</h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ modalForm.date }}
                    <span v-if="modalForm.start_time"> &middot; {{ modalForm.start_time }}-{{ modalForm.end_time }}</span>
                    <span v-if="modalFieldName"> &middot; {{ modalFieldName }}</span>
                </p>

                <!-- Server validation errors (from save attempt) -->
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

                <!-- Live conflict errors -->
                <div v-if="liveErrors.length && !Object.keys(modalForm.errors).length" class="mt-2 rounded border border-red-200 bg-red-50 p-2 text-xs text-red-700">
                    <p class="font-semibold">Conflicts detected:</p>
                    <ul class="mt-1 list-disc pl-4">
                        <li v-for="e in liveErrors" :key="e">{{ e }}</li>
                    </ul>
                </div>

                <!-- Admin override warnings -->
                <div v-if="liveWarnings.length && !Object.keys(modalForm.errors).length" class="mt-2 rounded border border-amber-300 bg-amber-50 p-2 text-xs text-amber-700">
                    <p class="font-semibold">Admin override:</p>
                    <ul class="mt-1 list-disc pl-4">
                        <li v-for="w in liveWarnings" :key="w">{{ w }}</li>
                    </ul>
                </div>

                <div class="mt-3 space-y-2.5">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-2">
                        <div>
                            <InputLabel for="m_team" value="Team" class="text-xs" />
                            <select id="m_team" v-model="modalForm.team_id" @change="liveValidate" class="mt-1 block w-full" required>
                                <option value="">Select</option>
                                <option v-for="t in schedulableTeams" :key="t.id" :value="t.id">{{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="m_field" value="Field" class="text-xs" />
                            <select
                                id="m_field"
                                v-model="modalForm.field_id"
                                @change="liveValidate"
                                :key="'field-' + modalForm.team_id + '-' + modalForm.date"
                                class="mt-1 block w-full"
                                :class="liveErrors.length ? 'border-red-400 ring-1 ring-red-400' : ''"
                                required
                            >
                                <option value="">Select</option>
                                <template v-for="loc in locations" :key="loc.id">
                                    <option
                                        v-for="f in (loc.fields || [])"
                                        :key="f.id"
                                        :value="f.id"
                                        :disabled="isFieldBlocked(f)"
                                        :class="isFieldBlocked(f) ? 'text-gray-400' : (getFieldBlockReason(f) || getFieldWindowWarning(f)) ? 'text-amber-600' : ''"
                                    >{{ f.name }} @ {{ loc.name }}{{ getFieldBlockReason(f) ? (isAdmin ? ` ⚠ ${getFieldBlockReason(f)}` : ` (${getFieldBlockReason(f)})`) : getFieldWindowWarning(f) ? ` ⚠ ${getFieldWindowWarning(f)}` : '' }}</option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-2">
                        <div>
                            <InputLabel for="m_date" value="Date" class="text-xs" />
                            <TextInput id="m_date" v-model="modalForm.date" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="m_start" value="Start Time" class="text-xs" />
                            <select id="m_start" v-model="modalForm.start_time"
                                class="mt-1 block w-full transition-all duration-500"
                                :class="highlightStartTime ? 'ring-2 ring-brand-400 border-brand-400 bg-brand-50' : ''"
                                @change="highlightStartTime = false" required>
                                <option value="">Select</option>
                                <option v-for="t in timeSlots" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="m_dur" value="Duration" class="text-xs" />
                            <select id="m_dur" v-model="modalDuration" class="mt-1 block w-full">
                                <option v-for="d in durationOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
                            </select>
                        </div>
                    </div>
                    <p v-if="modalForm.start_time && modalForm.end_time" class="text-[10px] text-gray-400">
                        {{ fmt12(modalForm.start_time) }} &ndash; {{ fmt12(modalForm.end_time) }}
                    </p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-2">
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

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="showModal = false" class="rounded-md px-4 py-3 text-sm text-gray-600 hover:text-gray-900 sm:px-3 sm:py-1.5 sm:text-xs">Cancel</button>
                    <PrimaryButton class="w-full sm:w-auto min-h-[44px] sm:min-h-0" :disabled="modalSubmitting || !modalForm.team_id || !modalForm.field_id || liveErrors.length > 0">Schedule</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirm Before Save Modal -->
        <Modal :show="showConfirmation" @close="cancelConfirmation" max-width="sm">
            <div class="p-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ editingEntryId ? 'Confirm Changes' : 'Confirm Schedule' }}</h3>
                <p class="mt-1 text-xs text-gray-500">Please review the details below.</p>

                <div class="mt-4 rounded-lg bg-gray-50 p-4 space-y-2 text-sm text-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: confirmationDetails.teamColor }"></span>
                        <strong>{{ confirmationDetails.teamName }}</strong>
                        <span class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-600">{{ confirmationDetails.type }}</span>
                    </div>
                    <p>
                        {{ new Date(confirmationDetails.date + 'T00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                    </p>
                    <p>
                        {{ fmt12(confirmationDetails.startTime) }} &ndash; {{ fmt12(confirmationDetails.endTime) }}
                    </p>
                    <p v-if="confirmationDetails.fieldName" class="text-gray-500">
                        {{ confirmationDetails.fieldName }}<span v-if="confirmationDetails.locationName"> @ {{ confirmationDetails.locationName }}</span>
                    </p>
                    <p v-if="confirmationDetails.title" class="italic text-gray-500">"{{ confirmationDetails.title }}"</p>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button @click="cancelConfirmation" class="rounded-md px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 sm:px-3 sm:py-1.5 sm:text-xs">
                        Go Back
                    </button>
                    <PrimaryButton class="w-full sm:w-auto min-h-[44px] sm:min-h-0" @click="confirmSchedule" :disabled="modalSubmitting">
                        {{ modalSubmitting ? 'Saving...' : 'Confirm' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Event Detail Modal -->
        <Modal :show="showEventDetail" @close="showEventDetail = false" max-width="sm">
            <div class="p-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ eventDetail.teamName || eventDetail.title }}</h3>
                <div class="mt-3 space-y-1.5 text-sm text-gray-700">
                    <p>
                        {{ new Date(eventDetail.date + 'T00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                    </p>
                    <p>{{ fmt12(eventDetail.startTime) }} &ndash; {{ fmt12(eventDetail.endTime) }}</p>
                    <p v-if="eventDetail.fieldName" class="text-gray-500">
                        {{ eventDetail.fieldName }}<span v-if="eventDetail.locationName"> @ {{ eventDetail.locationName }}</span>
                    </p>
                    <p>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-600">{{ eventDetail.type }}</span>
                        <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-medium uppercase"
                            :class="eventDetail.status === 'confirmed' ? 'bg-green-100 text-green-700' : eventDetail.status === 'tentative' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'">
                            {{ eventDetail.status }}
                        </span>
                    </p>
                </div>
                <div v-if="isManager || (isCoach && props.coachTeamIds.includes(eventDetail.teamId))" class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button @click="showEventDetail = false" class="rounded-md px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 sm:px-3 sm:py-1.5 sm:text-xs">
                        Cancel
                    </button>
                    <button v-if="eventDetail.status !== 'cancelled'" @click="cancelEvent" class="rounded-md border border-red-200 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 sm:border-0 sm:px-3 sm:py-1.5 sm:text-xs">
                        Delete Entry
                    </button>
                    <button @click="editEvent" class="rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 sm:px-3 sm:py-1.5 sm:text-xs">
                        Edit
                    </button>
                </div>
                <div v-else class="mt-4 flex justify-end">
                    <button @click="showEventDetail = false" class="rounded-md px-4 py-3 text-sm text-gray-600 hover:text-gray-900 sm:px-3 sm:py-1.5 sm:text-xs">Close</button>
                </div>
            </div>
        </Modal>
    </LeagueLayout>
</template>
