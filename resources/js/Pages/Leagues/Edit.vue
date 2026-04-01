<script setup>
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    league: Object,
});

const form = useForm({
    name: props.league.name,
    description: props.league.description || '',
    timezone: props.league.timezone,
    contact_email: props.league.contact_email || '',
});

const submit = () => {
    form.put(route('leagues.update', props.league.slug));
};

// Public calendar link
const publicUrl = ref(props.league.public_token ? `${window.location.origin}/p/${props.league.public_token}` : null);
const copied = ref(false);
const tokenProcessing = ref(false);

function copyPublicUrl() {
    if (!publicUrl.value) return;
    navigator.clipboard.writeText(publicUrl.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

function generateToken() {
    tokenProcessing.value = true;
    router.post(route('leagues.public-token.generate', props.league.slug), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const token = page.props.league.public_token;
            publicUrl.value = token ? `${window.location.origin}/p/${token}` : null;
            tokenProcessing.value = false;
        },
        onError: () => { tokenProcessing.value = false; },
    });
}

function revokeToken() {
    if (!confirm('Expire the current public link? Anyone with the old link will no longer be able to view the calendar. A new link will be generated automatically.')) return;
    tokenProcessing.value = true;
    router.delete(route('leagues.public-token.revoke', props.league.slug), {
        preserveScroll: true,
        onSuccess: (page) => {
            const token = page.props.league.public_token;
            publicUrl.value = token ? `${window.location.origin}/p/${token}` : null;
            tokenProcessing.value = false;
        },
        onError: () => { tokenProcessing.value = false; },
    });
}
</script>

<template>
    <Head :title="`Edit ${league.name}`" />

    <LeagueLayout :league="league" :userRole="userRole || ''">
        

        
        <!-- Page Header -->
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Edit {{ league.name }}
                    </h2>
<div class="mt-4">
            <div class="">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-3 p-3">
                        <div>
                            <InputLabel for="name" value="League Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                rows="3"
                            />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="timezone" value="Timezone" />
                            <select
                                id="timezone"
                                v-model="form.timezone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="America/New_York">Eastern (America/New_York)</option>
                                <option value="America/Chicago">Central (America/Chicago)</option>
                                <option value="America/Denver">Mountain (America/Denver)</option>
                                <option value="America/Los_Angeles">Pacific (America/Los_Angeles)</option>
                                <option value="America/Anchorage">Alaska</option>
                                <option value="Pacific/Honolulu">Hawaii</option>
                            </select>
                            <InputError :message="form.errors.timezone" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="contact_email" value="Contact Email" />
                            <TextInput id="contact_email" v-model="form.contact_email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.contact_email" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('leagues.show', league.slug)" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Public Calendar Link -->
            <div class="mt-4 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-3">
                    <h3 class="text-sm font-semibold text-gray-900">Public Calendar Link</h3>
                    <p class="mt-1 text-xs text-gray-500">Share a read-only calendar link with parents, coaches, or the public. No login required.</p>

                    <div v-if="publicUrl" class="mt-3 space-y-3">
                        <div class="flex items-center gap-2">
                            <input type="text" :value="publicUrl" readonly class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-700" @focus="$event.target.select()" />
                            <button @click="copyPublicUrl" class="shrink-0 rounded-md bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200">
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                        <button @click="revokeToken" :disabled="tokenProcessing" class="text-xs font-medium text-red-600 hover:text-red-700 disabled:opacity-50">
                            Expire &amp; Regenerate Link
                        </button>
                    </div>

                    <div v-else class="mt-3">
                        <button @click="generateToken" :disabled="tokenProcessing" class="rounded-md bg-brand-600 px-3 py-2 text-xs font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                            Generate Public Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
