<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ users: Array });

const search = ref('');

const filteredUsers = computed(() => {
    if (!search.value) return props.users;
    const q = search.value.toLowerCase();
    return props.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

function toggleActive(user) {
    const action = user.approved_at ? 'deactivate' : 'reactivate';
    if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} "${user.name}"?`)) return;
    router.post(route('admin.users.toggle-active', user.id), {}, { preserveScroll: true });
}

function deleteUser(user) {
    if (!confirm(`Permanently delete "${user.name}" (${user.email})? This will remove them from all leagues and cannot be undone.`)) return;
    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
}

function fmtDate(d) {
    if (!d) return 'Never';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Admin - Users" />

    <AdminLayout>
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Users ({{ users.length }})</h2>
            <input v-model="search" type="text" placeholder="Search..." class="w-48 rounded-md border-gray-300 text-xs" />
        </div>

        <FlashMessage />

        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <!-- Desktop table -->
            <table class="hidden sm:table min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                        <th class="px-3 py-2">User</th>
                        <th class="px-3 py-2">Leagues</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Last Login</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                            <p class="text-xs font-medium text-gray-900">{{ user.name }}</p>
                            <p class="text-[10px] text-gray-500">{{ user.email }}</p>
                        </td>
                        <td class="px-3 py-2">
                            <div v-if="user.leagues.length" class="flex flex-wrap gap-1">
                                <span v-for="l in user.leagues" :key="l.id" class="rounded bg-gray-100 px-1.5 py-0.5 text-[9px] text-gray-600">
                                    {{ l.name }} <span class="text-gray-400">({{ l.role }})</span>
                                </span>
                            </div>
                            <span v-else class="text-[10px] text-gray-400">None</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap gap-1">
                                <span v-if="user.approved_at" class="rounded-full bg-green-100 px-2 py-0.5 text-[9px] font-semibold text-green-700">Active</span>
                                <span v-else class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-semibold text-amber-700">Pending</span>
                                <span v-if="!user.email_verified_at" class="rounded-full bg-gray-100 px-2 py-0.5 text-[9px] font-semibold text-gray-500">Unverified</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-[10px] text-gray-500">{{ fmtDate(user.last_login_at) }}</td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="toggleActive(user)" class="text-[10px]"
                                    :class="user.approved_at ? 'text-red-500 hover:text-red-700' : 'text-brand-600 hover:text-brand-700'">
                                    {{ user.approved_at ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button @click="deleteUser(user)" class="text-[10px] text-red-400 hover:text-red-600">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Mobile cards -->
            <div class="sm:hidden divide-y divide-gray-50">
                <div v-for="user in filteredUsers" :key="'m-' + user.id" class="px-3 py-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <span v-if="user.approved_at" class="rounded-full bg-green-100 px-2 py-0.5 text-[9px] font-semibold text-green-700">Active</span>
                            <span v-else class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-semibold text-amber-700">Pending</span>
                            <span v-if="!user.email_verified_at" class="rounded-full bg-gray-100 px-2 py-0.5 text-[9px] font-semibold text-gray-500">Unverified</span>
                        </div>
                    </div>
                    <div v-if="user.leagues.length" class="mt-1 flex flex-wrap gap-1">
                        <span v-for="l in user.leagues" :key="l.id" class="rounded bg-gray-100 px-1.5 py-0.5 text-[9px] text-gray-600">{{ l.name }}</span>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between text-[10px] text-gray-400">
                        <span>Last login: {{ fmtDate(user.last_login_at) }}</span>
                        <div class="flex gap-3">
                            <button @click="toggleActive(user)"
                                :class="user.approved_at ? 'text-red-500' : 'text-brand-600'">
                                {{ user.approved_at ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button @click="deleteUser(user)" class="text-red-400">Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filteredUsers.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">No users found.</div>
        </div>
    </AdminLayout>
</template>
