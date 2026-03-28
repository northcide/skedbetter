<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    league: Object,
    team: Object,
    userRole: String,
    icalUrl: String,
    applicableRules: { type: Array, default: () => [] },
});

const isManager = ['superadmin', 'division_manager', 'division_manager'].includes(props.userRole);

const rulesByType = computed(() => {
    const groups = {};
    for (const rule of props.applicableRules) {
        const key = rule.type;
        if (!groups[key]) groups[key] = [];
        groups[key].push(rule);
    }
    return groups;
});

const hasFieldRules = computed(() =>
    (rulesByType.value.field_blocked?.length || 0) +
    (rulesByType.value.field_allowed?.length || 0) +
    (rulesByType.value.field_weekly_limit?.length || 0) > 0
);

const hasLimits = computed(() =>
    (rulesByType.value.weekly_limit?.length || 0) +
    (rulesByType.value.constraint?.length || 0) > 0
);

const typeBadge = (type) => ({
    weekly_limit: 'bg-amber-100 text-amber-700',
    field_blocked: 'bg-red-100 text-red-700',
    field_weekly_limit: 'bg-amber-100 text-amber-700',
    field_allowed: 'bg-green-100 text-green-700',
    blackout: 'bg-gray-200 text-gray-700',
    constraint: 'bg-brand-100 text-brand-700',
}[type] || 'bg-gray-100 text-gray-600');

const typeLabel = (type) => ({
    weekly_limit: 'Limit',
    field_blocked: 'Blocked',
    field_weekly_limit: 'Limit',
    field_allowed: 'Allowed',
    blackout: 'Blackout',
    constraint: 'Rule',
}[type] || 'Rule');
</script>

<template>
    <Head :title="`${team.name} - ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                    <span v-if="team.color_code" class="inline-block h-4 w-4 rounded-full" :style="{ backgroundColor: team.color_code }"></span>
                    {{ team.name }}
                </h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ team.division?.name }}
                    <span v-if="team.division?.season" class="text-gray-400">&middot; {{ team.division.season.name }}</span>
                </p>
            </div>
            <Link v-if="isManager" :href="route('leagues.teams.edit', [league.slug, team.id])" class="text-sm text-brand-600 hover:text-brand-700">Edit</Link>
        </div>

        <div class="mt-6 space-y-6">
            <!-- Info Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-4">
                    <p class="text-xs font-medium text-gray-400">Contact</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ team.contact_name || 'Not set' }}</p>
                    <p v-if="team.contact_email" class="text-xs text-gray-500">{{ team.contact_email }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-4">
                    <p class="text-xs font-medium text-gray-400">Members</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ team.users?.length || 0 }} assigned</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-4">
                    <p class="text-xs font-medium text-gray-400">Weekly Slot Limit</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ team.max_weekly_slots || 'No limit' }}</p>
                </div>
            </div>

            <!-- Applicable Rules -->
            <div v-if="applicableRules.length > 0" class="rounded-xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Scheduling Rules</h3>
                        <p class="text-xs text-gray-500">Rules from field restrictions, blackouts, and league constraints that affect this team</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                        {{ applicableRules.length }}
                    </span>
                </div>

                <div class="divide-y divide-gray-50">
                    <!-- Field Access -->
                    <div v-if="hasFieldRules" class="px-5 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Field Access</h4>
                        <div class="space-y-2">
                            <div v-for="rule in [...(rulesByType.field_blocked || []), ...(rulesByType.field_weekly_limit || []), ...(rulesByType.field_allowed || [])]" :key="rule.sourceDetail + rule.type" class="flex items-start gap-3">
                                <span class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-semibold" :class="typeBadge(rule.type)">
                                    {{ typeLabel(rule.type) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900">{{ rule.sourceDetail }}</p>
                                    <p class="text-xs text-gray-500">{{ rule.rule }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduling Limits -->
                    <div v-if="hasLimits" class="px-5 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Scheduling Limits</h4>
                        <div class="space-y-2">
                            <div v-for="rule in [...(rulesByType.weekly_limit || []), ...(rulesByType.constraint || [])]" :key="rule.rule" class="flex items-start gap-3">
                                <span class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-semibold" :class="typeBadge(rule.type)">
                                    {{ typeLabel(rule.type) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900">{{ rule.rule }}</p>
                                    <p class="text-xs text-gray-500">Source: {{ rule.sourceDetail }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Blackouts -->
                    <div v-if="rulesByType.blackout?.length" class="px-5 py-4">
                        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Blackout Periods</h4>
                        <div class="space-y-2">
                            <div v-for="rule in rulesByType.blackout" :key="rule.rule" class="flex items-start gap-3">
                                <span class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-[11px] font-semibold" :class="typeBadge('blackout')">
                                    {{ typeLabel('blackout') }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900">{{ rule.rule }}</p>
                                    <p class="text-xs text-gray-500">Applies to: {{ rule.sourceDetail }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- iCal -->
            <div v-if="icalUrl" class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900">Calendar Subscription</h3>
                <p class="mt-1 text-xs text-gray-500">Subscribe in Google Calendar, Apple Calendar, or Outlook.</p>
                <div class="mt-3 flex items-center gap-2">
                    <input type="text" :value="icalUrl" readonly class="block w-full rounded-lg border-gray-200 bg-gray-50 text-xs text-gray-500" @click="$event.target.select()" />
                    <button @click="navigator.clipboard.writeText(icalUrl)" class="whitespace-nowrap rounded-lg bg-brand-50 px-3 py-2 text-xs font-medium text-brand-600 hover:bg-brand-100">Copy</button>
                </div>
            </div>

            <!-- Schedule -->
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Upcoming Schedule</h3>
                </div>
                <div v-if="!team.schedule_entries?.length" class="px-5 py-8 text-center text-sm text-gray-400">No scheduled entries yet.</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="entry in team.schedule_entries" :key="entry.id" class="flex items-center justify-between px-5 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ new Date(entry.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}</span>
                            <span class="ml-2 text-sm text-gray-500">{{ entry.start_time?.slice(0, 5) }} - {{ entry.end_time?.slice(0, 5) }}</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ entry.field?.name }} @ {{ entry.field?.location?.name }}</span>
                    </li>
                </ul>
            </div>

            <!-- Members -->
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Team Members</h3>
                </div>
                <div v-if="!team.users?.length" class="px-5 py-8 text-center text-sm text-gray-400">No team members assigned yet.</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="user in team.users" :key="user.id" class="flex items-center justify-between px-5 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ user.name }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ user.email }}</span>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ user.pivot?.role }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </LeagueLayout>
</template>
