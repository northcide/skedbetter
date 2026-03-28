<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    members: Array,
    invitations: Array,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager'].includes(props.userRole);

const form = useForm({
    email: '',
    role: 'coach',
});

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

const roleLabel = (role) => {
    const map = {
        league_manager: 'League Manager',
        division_manager: 'Division Manager',
        team_manager: 'Team Manager',
        coach: 'Coach',
    };
    return map[role] || role;
};

const roleBadge = (role) => {
    const map = {
        league_manager: 'bg-purple-100 text-purple-800',
        division_manager: 'bg-blue-100 text-blue-800',
        team_manager: 'bg-green-100 text-green-800',
        coach: 'bg-gray-100 text-gray-800',
    };
    return map[role] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`${league.name} - Members`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <div>
                        <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Members & Invitations</h2>
                    </div>
<FlashMessage />

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-8">
                <!-- Invite Form -->
                <div v-if="isManager" class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Invite Member</h3>
                    </div>
                    <form @submit.prevent="submit" class="flex items-end gap-4 p-6">
                        <div class="flex-1">
                            <InputLabel for="email" value="Email Address" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="coach@example.com" />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                        <div class="w-48">
                            <InputLabel for="role" value="Role" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="league_manager">League Manager</option>
                                <option value="division_manager">Division Manager</option>
                                <option value="team_manager">Team Manager</option>
                                <option value="coach">Coach</option>
                            </select>
                        </div>
                        <PrimaryButton :disabled="form.processing">Send Invite</PrimaryButton>
                    </form>
                </div>

                <!-- Current Members -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Members ({{ members.length }})</h3>
                    </div>

                    <ul class="divide-y">
                        <li v-for="member in members" :key="member.id" class="flex items-center justify-between px-6 py-4">
                            <div>
                                <span class="font-medium text-gray-900">{{ member.name }}</span>
                                <span class="ml-2 text-sm text-gray-500">{{ member.email }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5" :class="roleBadge(member.role)">
                                    {{ roleLabel(member.role) }}
                                </span>
                                <button v-if="isManager" @click="removeMember(member)" class="text-xs text-red-600 hover:text-red-900">Remove</button>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Pending Invitations -->
                <div v-if="isManager && invitations.length > 0" class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b p-6">
                        <h3 class="text-lg font-medium text-gray-900">Pending Invitations</h3>
                    </div>

                    <ul class="divide-y">
                        <li v-for="inv in invitations" :key="inv.id" class="flex items-center justify-between px-6 py-4">
                            <div>
                                <span class="font-medium text-gray-900">{{ inv.email }}</span>
                                <span class="ml-2 inline-flex rounded-full px-2 text-xs font-semibold leading-5" :class="roleBadge(inv.role)">
                                    {{ roleLabel(inv.role) }}
                                </span>
                                <span v-if="inv.accepted_at" class="ml-2 text-xs text-green-600">Accepted</span>
                                <span v-else-if="new Date(inv.expires_at) < new Date()" class="ml-2 text-xs text-red-500">Expired</span>
                                <span v-else class="ml-2 text-xs text-yellow-600">Pending</span>
                            </div>
                            <button v-if="!inv.accepted_at" @click="revokeInvitation(inv)" class="text-xs text-red-600 hover:text-red-900">Revoke</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
