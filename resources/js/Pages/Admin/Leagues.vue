<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ leagues: Array });

const processing = ref({});

const pendingLeagues = computed(() => props.leagues.filter(l => !l.approved_at));
const activeLeagues = computed(() => props.leagues.filter(l => l.approved_at && l.is_active));
const inactiveLeagues = computed(() => props.leagues.filter(l => l.approved_at && !l.is_active));

function approve(league) {
    processing.value[league.id] = 'approving';
    router.post(route('admin.leagues.approve', league.id), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value[league.id] = null; },
    });
}

function reject(league) {
    if (!confirm(`Reject and delete "${league.name}"?`)) return;
    processing.value[league.id] = 'rejecting';
    router.delete(route('admin.leagues.reject', league.id), {
        preserveScroll: true,
        onFinish: () => { processing.value[league.id] = null; },
    });
}

function toggleActive(league) {
    const action = league.is_active ? 'deactivate' : 'reactivate';
    if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} "${league.name}"?`)) return;
    router.post(route('admin.leagues.toggle-active', league.id), {}, { preserveScroll: true });
}

function deleteLeague(league) {
    if (!confirm(`Permanently delete "${league.name}"? This cannot be undone. All teams, fields, schedules, and data will be removed.`)) return;
    router.delete(route('admin.leagues.destroy', league.id), { preserveScroll: true });
}

function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Admin - Leagues" />

    <AdminLayout>
        <h2 class="text-base font-semibold text-gray-900">Leagues</h2>

        <FlashMessage />

        <!-- Pending -->
        <div v-if="pendingLeagues.length" class="mt-3 rounded-lg border border-amber-200 bg-amber-50">
            <div class="border-b border-amber-200 px-4 py-2">
                <h3 class="text-sm font-semibold text-amber-800">Pending Approval ({{ pendingLeagues.length }})</h3>
            </div>
            <div class="divide-y divide-amber-100">
                <div v-for="league in pendingLeagues" :key="league.id" class="flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ league.name }}</p>
                        <p v-if="league.requester" class="text-xs text-gray-500">
                            Requested by {{ league.requester.name }} ({{ league.requester.email }})
                        </p>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="text-[10px] text-gray-400">{{ fmtDate(league.created_at) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="reject(league)" :disabled="processing[league.id]"
                            class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50">
                            Reject
                        </button>
                        <PrimaryButton @click="approve(league)"
                            :disabled="processing[league.id]" size="sm">
                            {{ processing[league.id] === 'approving' ? '...' : 'Approve' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Leagues -->
        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-4 py-2">
                <h3 class="text-sm font-semibold text-gray-900">Active Leagues ({{ activeLeagues.length }})</h3>
            </div>
            <div v-if="activeLeagues.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">No active leagues.</div>
            <div v-else class="divide-y divide-gray-50">
                <div v-for="league in activeLeagues" :key="league.id" class="flex items-center justify-between px-4 py-2.5">
                    <div>
                        <Link :href="route('leagues.show', league.slug)" class="text-sm font-medium text-gray-900 hover:text-brand-600">{{ league.name }}</Link>
                        <div class="flex gap-3 text-[10px] text-gray-400">
                            <span>{{ league.divisions_count }} div</span>
                            <span>{{ league.teams_count }} teams</span>
                            <span>{{ league.users_count }} members</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleActive(league)" class="text-[10px] text-gray-500 hover:text-gray-700">Deactivate</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Leagues -->
        <div v-if="inactiveLeagues.length" class="mt-3 rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-4 py-2">
                <h3 class="text-sm font-semibold text-gray-500">Inactive ({{ inactiveLeagues.length }})</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="league in inactiveLeagues" :key="league.id" class="flex items-center justify-between px-4 py-2.5 opacity-60">
                    <div>
                        <Link :href="route('leagues.show', league.slug)" class="text-sm font-medium text-gray-900 hover:text-brand-600">{{ league.name }}</Link>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="toggleActive(league)" class="text-[10px] text-brand-600 hover:text-brand-700">Reactivate</button>
                        <button @click="deleteLeague(league)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
