<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    currentSeason: Object,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager'].includes(props.userRole);
</script>

<template>
    <Head :title="league.name" />

    <LeagueLayout :league="league" :userRole="userRole">
        <FlashMessage />

        <div class="space-y-4">
            <!-- Stats row -->
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg border border-gray-100 bg-white px-3 py-3">
                    <p class="text-xl font-bold text-gray-900">{{ league.teams_count }}</p>
                    <p class="text-[11px] font-medium text-gray-400">Teams</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-white px-3 py-3">
                    <p class="text-xl font-bold text-gray-900">{{ league.locations_count }}</p>
                    <p class="text-[11px] font-medium text-gray-400">Locations</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-white px-3 py-3">
                    <p class="text-xl font-bold text-gray-900">{{ league.divisions_count }}</p>
                    <p class="text-[11px] font-medium text-gray-400">Divisions</p>
                </div>
            </div>

            <!-- Season + CTA -->
            <div v-if="currentSeason" class="flex items-center justify-between rounded-lg border border-gray-100 bg-white px-4 py-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Current Season</p>
                    <p class="text-sm font-semibold text-gray-900">{{ currentSeason.name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ new Date(currentSeason.start_date).toLocaleDateString() }} &ndash;
                        {{ new Date(currentSeason.end_date).toLocaleDateString() }}
                    </p>
                </div>
                <Link :href="route('leagues.schedule.calendar', league.slug)">
                    <PrimaryButton>Open Calendar</PrimaryButton>
                </Link>
            </div>
            <div v-else class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center">
                <p class="text-xs text-gray-500">No season set up yet.</p>
                <Link v-if="isManager" :href="route('leagues.onboarding', league.slug)" class="mt-1 inline-block text-xs font-medium text-brand-600 hover:text-brand-700">Run setup wizard</Link>
            </div>

            <!-- Quick Actions -->
            <div v-if="isManager" class="rounded-lg border border-gray-100 bg-white px-4 py-3">
                <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Quick Actions</p>
                <div class="flex flex-wrap gap-1.5">
                    <Link :href="route('leagues.schedule.create', league.slug)" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 transition hover:bg-gray-50">New Entry</Link>
                    <Link :href="route('leagues.schedule.bulk', league.slug)" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 transition hover:bg-gray-50">Bulk Schedule</Link>
                    <Link :href="route('leagues.teams.create', league.slug)" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 transition hover:bg-gray-50">Add Team</Link>
                    <Link :href="route('leagues.members.index', league.slug)" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 transition hover:bg-gray-50">Invite Member</Link>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
