<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object,
    members: Array,
    invitations: Array,
    teams: { type: Array, default: () => [] },
    userRole: String,
});

const isManager = ['superadmin', 'league_admin', 'division_manager'].includes(props.userRole);
const copiedMemberId = ref(null);

const form = useForm({ email: '', name: '', role: 'coach', team_id: '' });

const submit = () => {
    form.post(route('leagues.invitations.store', props.league.slug), {
        onSuccess: () => form.reset(),
    });
};

const revokeInvitation = (inv) => {
    if (confirm('Revoke this invitation?')) {
        router.delete(route('leagues.invitations.destroy', [props.league.slug, inv.id]));
    }
};

const removeMember = (member) => {
    if (confirm(`Remove ${member.name} from the league?`)) {
        router.delete(route('leagues.members.destroy', [props.league.slug, member.id]));
    }
};

const sendMagicLink = (member) => {
    router.post(route('leagues.members.send-magic-link', [props.league.slug, member.id]));
};

const copyMagicLink = async (member) => {
    try {
        const res = await axios.post(route('leagues.members.generate-magic-link', [props.league.slug, member.id]));
        await navigator.clipboard.writeText(res.data.url);
        copiedMemberId.value = member.id;
        setTimeout(() => { copiedMemberId.value = null; }, 2000);
    } catch (e) {
        alert('Failed to generate link');
    }
};

const roleLabel = (role) => ({
    league_admin: 'League Admin',
    division_manager: 'Division Manager',
    coach: 'Coach',
}[role] || role);

const roleBadge = (role) => ({
    league_admin: 'bg-purple-100 text-purple-800',
    division_manager: 'bg-blue-100 text-blue-800',
    coach: 'bg-gray-100 text-gray-800',
}[role] || 'bg-gray-100 text-gray-800');
</script>

<template>
    <Head :title="`${league.name} - Members`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        <h2 class="text-lg font-semibold text-gray-900">Members & Invitations</h2>

        <FlashMessage />

        <div class="mt-4 space-y-3">
            <!-- Invite Form -->
            <div v-if="isManager" class="rounded-lg border border-gray-200 bg-white p-3">
                <h3 class="text-xs font-semibold text-gray-900">Add Member</h3>
                <form @submit.prevent="submit" class="mt-2 space-y-2">
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Full name" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="email@example.com" />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="role" value="Role" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full">
                                <option value="league_admin">League Admin</option>
                                <option value="division_manager">Division Manager</option>
                                <option value="coach">Coach</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="form.role === 'coach'" class="flex items-end gap-2">
                        <div class="flex-1">
                            <InputLabel for="team_id" value="Assign to Team" />
                            <select id="team_id" v-model="form.team_id" class="mt-1 block w-full" required>
                                <option value="">-- Select Team --</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}{{ t.division ? ` (${t.division.name})` : '' }}</option>
                            </select>
                            <InputError :message="form.errors.team_id" class="mt-1" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <PrimaryButton :disabled="form.processing">Add Member</PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Members -->
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Members ({{ members.length }})</h3>
                </div>

                <ul class="divide-y divide-gray-50">
                    <li v-for="member in members" :key="member.id" class="flex items-center justify-between px-4 py-3">
                        <div class="min-w-0">
                            <span class="text-sm font-medium text-gray-900">{{ member.name }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ member.email }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="roleBadge(member.role)">
                                {{ roleLabel(member.role) }}
                            </span>
                            <template v-if="isManager">
                                <button @click="sendMagicLink(member)" class="rounded border border-gray-200 px-2 py-1 text-[10px] font-medium text-brand-600 hover:bg-brand-50" title="Send login link via email">
                                    Send Link
                                </button>
                                <button @click="copyMagicLink(member)" class="rounded border border-gray-200 px-2 py-1 text-[10px] font-medium transition" :class="copiedMemberId === member.id ? 'bg-green-50 text-green-700 border-green-200' : 'text-gray-600 hover:bg-gray-50'" title="Copy login link to clipboard">
                                    {{ copiedMemberId === member.id ? 'Copied!' : 'Copy Link' }}
                                </button>
                                <button @click="removeMember(member)" class="text-[10px] text-red-500 hover:text-red-700">Remove</button>
                            </template>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pending Invitations -->
            <div v-if="isManager && invitations.length > 0" class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Pending Invitations</h3>
                </div>

                <ul class="divide-y divide-gray-50">
                    <li v-for="inv in invitations" :key="inv.id" class="flex items-center justify-between px-4 py-3">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ inv.email }}</span>
                            <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="roleBadge(inv.role)">{{ roleLabel(inv.role) }}</span>
                            <span v-if="inv.accepted_at" class="ml-2 text-[10px] text-green-600">Accepted</span>
                            <span v-else-if="new Date(inv.expires_at) < new Date()" class="ml-2 text-[10px] text-red-500">Expired</span>
                            <span v-else class="ml-2 text-[10px] text-yellow-600">Pending</span>
                        </div>
                        <button v-if="!inv.accepted_at" @click="revokeInvitation(inv)" class="text-[10px] text-red-500 hover:text-red-700">Revoke</button>
                    </li>
                </ul>
            </div>
        </div>
    </LeagueLayout>
</template>
