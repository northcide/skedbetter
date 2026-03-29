<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    leagues: Array,
    pendingLeagues: { type: Array, default: () => [] },
    canCreateLeague: Boolean,
    isSuperadmin: Boolean,
});

const isLeagueManager = (league) => ['superadmin', 'league_admin', 'division_manager'].includes(league.user_role);

const deleteLeague = (league) => {
    if (confirm(`Delete "${league.name}"? This will delete all divisions, teams, fields, and schedule entries in this league. This cannot be undone.`)) {
        router.delete(route('leagues.destroy', league.slug));
    }
};
</script>

<template>
    <Head title="Leagues" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">My Leagues</h2>
                <Link v-if="canCreateLeague" :href="route('leagues.create')">
                    <PrimaryButton>Create League</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashMessage />

        <div class="mx-auto max-w-screen-2xl px-3 py-4 sm:px-4 lg:px-6">
            <!-- Pending league requests -->
            <div v-if="pendingLeagues.length" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-sm font-semibold text-amber-800">Pending League Requests</h3>
                <div v-for="pl in pendingLeagues" :key="pl.id" class="mt-2 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ pl.name }}</p>
                        <p class="text-xs text-amber-600">Waiting for admin approval</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Pending</span>
                </div>
            </div>

            <div v-if="leagues.length === 0 && pendingLeagues.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm text-gray-500">No leagues yet.</p>
                <p v-if="canCreateLeague" class="mt-1 text-sm text-gray-400">Create your first league to get started.</p>
            </div>

            <div v-else class="rounded-lg border border-gray-200 bg-white">
                <!-- Mobile: card layout -->
                <div class="divide-y divide-gray-100 sm:hidden">
                    <div v-for="league in leagues" :key="'m-' + league.id" class="px-4 py-3">
                        <Link :href="route('leagues.show', league.slug)" class="block">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900">{{ league.name }}</span>
                                <span v-if="league.is_active" class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Active</span>
                                <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">Inactive</span>
                            </div>
                            <p v-if="league.description" class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ league.description }}</p>
                            <div class="mt-1.5 flex gap-3 text-xs text-gray-400">
                                <span>{{ league.divisions_count }} divisions</span>
                                <span>{{ league.teams_count }} teams</span>
                                <span>{{ league.locations_count }} locations</span>
                                <span>{{ league.fields_count }} fields</span>
                            </div>
                        </Link>
                        <div class="mt-2 flex gap-4">
                            <Link :href="route('leagues.show', league.slug)" class="py-1 text-sm font-medium text-brand-600">Open</Link>
                            <Link v-if="isLeagueManager(league)" :href="route('leagues.edit', league.slug)" class="py-1 text-sm font-medium text-gray-500">Edit</Link>
                            <button v-if="isSuperadmin" @click="deleteLeague(league)" class="py-1 text-sm font-medium text-red-500">Delete</button>
                        </div>
                    </div>
                </div>

                <!-- Desktop: table -->
                <table class="hidden sm:table min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            <th class="px-4 py-2.5">League</th>
                            <th class="px-4 py-2.5 text-center">Divisions</th>
                            <th class="px-4 py-2.5 text-center">Teams</th>
                            <th class="px-4 py-2.5 text-center">Locations</th>
                            <th class="px-4 py-2.5 text-center">Fields</th>
                            <th class="px-4 py-2.5 text-center">Status</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="league in leagues" :key="league.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <Link :href="route('leagues.show', league.slug)" class="group">
                                    <span class="text-sm font-semibold text-gray-900 group-hover:text-brand-600">{{ league.name }}</span>
                                    <p v-if="league.description" class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ league.description }}</p>
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ league.divisions_count }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ league.teams_count }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ league.locations_count }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ league.fields_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="league.is_active" class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Active</span>
                                <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">Inactive</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('leagues.show', league.slug)" class="text-[11px] font-medium text-brand-600 hover:text-brand-700">Open</Link>
                                    <Link v-if="isLeagueManager(league)" :href="route('leagues.edit', league.slug)" class="text-[11px] font-medium text-gray-500 hover:text-gray-700">Edit</Link>
                                    <button v-if="isSuperadmin" @click="deleteLeague(league)" class="text-[11px] font-medium text-red-500 hover:text-red-700">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
