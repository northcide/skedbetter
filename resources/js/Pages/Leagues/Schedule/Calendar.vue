<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    league: Object,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager', 'division_manager'].includes(props.userRole);
const calendarRef = ref(null);
const errorMessages = ref([]);

const calendarOptions = ref({
    plugins: [resourceTimelinePlugin, interactionPlugin],
    initialView: 'resourceTimelineDay',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'resourceTimelineDay,resourceTimelineWeek',
    },
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    resourceAreaHeaderContent: 'Fields',
    resourceAreaWidth: '200px',
    height: 'auto',
    editable: isManager,
    selectable: isManager,
    eventResourceEditable: isManager,
    resources: {
        url: route('leagues.schedule.resources', props.league.slug),
        method: 'GET',
    },
    events: {
        url: route('leagues.schedule.events', props.league.slug),
        method: 'GET',
    },
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
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

    if (!fieldId || fieldId.startsWith('loc-')) {
        info.revert();
        return;
    }

    axios.patch(route('leagues.schedule.move', [props.league.slug, event.id]), {
        date,
        start_time: startTime,
        end_time: endTime,
        field_id: fieldId,
    }).catch((error) => {
        info.revert();
        if (error.response?.data?.errors) {
            errorMessages.value = error.response.data.errors;
            setTimeout(() => { errorMessages.value = []; }, 5000);
        }
    });
}

function handleEventResize(info) {
    handleEventDrop(info);
}

function handleSelect(info) {
    const resourceId = info.resource?.id;
    if (!resourceId || resourceId.startsWith('loc-')) return;

    const date = info.start.toISOString().slice(0, 10);
    const startTime = info.start.toTimeString().slice(0, 5);
    const endTime = info.end.toTimeString().slice(0, 5);

    router.visit(route('leagues.schedule.create', props.league.slug), {
        data: { date, start_time: startTime, end_time: endTime, field_id: resourceId },
    });
}

function handleEventClick(info) {
    router.visit(route('leagues.schedule.edit', [props.league.slug, info.event.id]));
}
</script>

<template>
    <Head :title="`${league.name} - Calendar`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Schedule Calendar</h2>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('leagues.schedule.index', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">
                                List View
                            </Link>
                            <Link v-if="isManager" :href="route('leagues.schedule.create', league.slug)">
                                <PrimaryButton>New Entry</PrimaryButton>
                            </Link>
                        </div>
                    </div>
<FlashMessage />

        <!-- Conflict errors from drag-drop -->
        <div v-if="errorMessages.length" class="fixed top-4 right-4 z-50 max-w-md rounded-lg bg-red-500 p-4 text-sm text-white shadow-lg">
            <p class="font-medium">Scheduling conflict:</p>
            <ul class="mt-1 list-disc pl-4">
                <li v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
            </ul>
        </div>

        <div class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white p-4 shadow-sm">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>

<style>
.fc {
    --fc-border-color: #e5e7eb;
    --fc-today-bg-color: #f0f9ff;
}
.fc .fc-resource-timeline-divider {
    width: 3px;
}
.fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-size: 0.75rem;
    padding: 2px 4px;
}
</style>
