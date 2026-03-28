<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ league: Object, team: Object, userRole: String, icalUrl: String });
const isManager = ['superadmin', 'league_manager', 'division_manager'].includes(props.userRole);
</script>

<template>
    <Head :title="`${team.name} - ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 flex items-center gap-2 text-xl font-semibold leading-tight text-gray-800">
                                <span v-if="team.color_code" class="inline-block h-4 w-4 rounded-full" :style="{ backgroundColor: team.color_code }"></span>
                                {{ team.name }}
                            </h2>
                        </div>
                        </div>
<div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Team Info -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Division</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.division?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Season</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.division?.season?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Contact</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ team.contact_name || 'None' }}</dd>
                                <dd v-if="team.contact_email" class="text-sm text-gray-500">{{ team.contact_email }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- iCal Feed -->
                <div v-if="icalUrl" class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-900">Calendar Subscription</h3>
                        <p class="mt-1 text-xs text-gray-500">Subscribe to this team's schedule in Google Calendar, Apple Calendar, or Outlook.</p>
                        <div class="mt-2 flex items-center gap-2">
                            <input
                                type="text"
                                :value="icalUrl"
                                readonly
                                class="block w-full rounded-md border-gray-300 bg-gray-50 text-xs text-gray-600"
                                @click="$event.target.select()"
                            />
                            <button
                                @click="navigator.clipboard.writeText(icalUrl)"
                                class="whitespace-nowrap rounded-md bg-brand-50 px-3 py-2 text-xs font-medium text-brand-600 hover:bg-brand-100"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Schedule -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Upcoming Schedule</h3>
                    </div>

                    <div v-if="!team.schedule_entries || team.schedule_entries.length === 0" class="p-6 text-center text-gray-500">
                        No scheduled entries yet.
                    </div>

                    <ul v-else class="divide-y">
                        <li v-for="entry in team.schedule_entries" :key="entry.id" class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900">
                                        {{ new Date(entry.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500">
                                        {{ entry.start_time?.slice(0, 5) }} - {{ entry.end_time?.slice(0, 5) }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ entry.field?.name }} @ {{ entry.field?.location?.name }}
                                </div>
                            </div>
                            <div v-if="entry.title" class="mt-1 text-sm text-gray-500">{{ entry.title }}</div>
                        </li>
                    </ul>
                </div>

                <!-- Team Members -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Team Members</h3>
                    </div>

                    <div v-if="!team.users || team.users.length === 0" class="p-6 text-center text-gray-500">
                        No team members assigned yet.
                    </div>

                    <ul v-else class="divide-y">
                        <li v-for="user in team.users" :key="user.id" class="flex items-center justify-between px-6 py-4">
                            <div>
                                <span class="font-medium text-gray-900">{{ user.name }}</span>
                                <span class="ml-2 text-sm text-gray-500">{{ user.email }}</span>
                            </div>
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">{{ user.pivot?.role }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
