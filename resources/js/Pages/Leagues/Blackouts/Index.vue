<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ league: Object, rules: Array, userRole: String });
const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);

const deleteRule = (rule) => {
    if (confirm(`Delete blackout rule "${rule.name}"?`)) {
        router.delete(route('leagues.blackouts.destroy', [props.league.slug, rule.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Blackout Rules`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div class="flex items-center justify-between">
                        <div>
                            <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Blackout Rules</h2>
                        </div>
                        <Link v-if="isManager" :href="route('leagues.blackouts.create', league.slug)">
                            <PrimaryButton>Add Blackout</PrimaryButton>
                        </Link>
                    </div>
<FlashMessage />

        <div class="mt-4">
            <div class="">
                <div v-if="rules.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No blackout rules configured.</p>
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Scope</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Dates</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Recurrence</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th v-if="isManager" class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="rule in rules" :key="rule.id">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900">{{ rule.name }}</div>
                                    <div v-if="rule.reason" class="text-sm text-gray-500">{{ rule.reason }}</div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-500">
                                    <span class="capitalize">{{ rule.scope_type }}</span>
                                    <span v-if="rule.scope_label && rule.scope_type !== 'league'" class="block text-[10px] text-gray-400 truncate max-w-[200px]" :title="rule.scope_label">{{ rule.scope_label }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500">
                                    {{ new Date(rule.start_date).toLocaleDateString() }} &ndash; {{ new Date(rule.end_date).toLocaleDateString() }}
                                    <span v-if="rule.start_time" class="block text-gray-400">{{ rule.start_time?.slice(0,5) }} - {{ rule.end_time?.slice(0,5) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-500 capitalize">{{ rule.recurrence }}</td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <span :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                        {{ rule.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td v-if="isManager" class="whitespace-nowrap px-3 py-2 text-right text-sm">
                                    <Link :href="route('leagues.blackouts.edit', [league.slug, rule.id])" class="text-brand-600 hover:text-brand-700">Edit</Link>
                                    <button @click="deleteRule(rule)" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
