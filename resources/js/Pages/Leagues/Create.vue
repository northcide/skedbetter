<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    isSuperadmin: { type: Boolean, default: false },
});

const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;

const form = useForm({
    name: '',
    description: '',
    timezone: browserTz || 'America/Chicago',
    admin_email: '',
});

const submit = () => {
    form.post(route('leagues.store'));
};
</script>

<template>
    <Head title="Create League" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-900">Create League</h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-4">

            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="League Name" class="text-xs" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" class="text-xs" />
                        <textarea id="description" v-model="form.description" class="mt-1 block w-full" rows="2" />
                    </div>

                    <div>
                        <InputLabel for="timezone" value="Timezone" class="text-xs" />
                        <select id="timezone" v-model="form.timezone" class="mt-1 block w-full">
                            <option value="America/New_York">Eastern (America/New_York)</option>
                            <option value="America/Chicago">Central (America/Chicago)</option>
                            <option value="America/Denver">Mountain (America/Denver)</option>
                            <option value="America/Los_Angeles">Pacific (America/Los_Angeles)</option>
                            <option value="America/Anchorage">Alaska</option>
                            <option value="Pacific/Honolulu">Hawaii</option>
                            <option v-if="!['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Anchorage','Pacific/Honolulu'].includes(browserTz)" :value="browserTz">{{ browserTz }} (detected)</option>
                        </select>
                    </div>

                    <div v-if="isSuperadmin">
                        <InputLabel for="admin_email" value="League Admin Email" class="text-xs" />
                        <TextInput id="admin_email" v-model="form.admin_email" type="email" class="mt-1 block w-full" required placeholder="admin@example.com" />
                        <p class="mt-1 text-[11px] text-gray-400">This person will be invited as the League Admin after setup.</p>
                        <InputError :message="form.errors.admin_email" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <Link :href="route('leagues.index')" class="text-xs text-gray-500 hover:text-gray-700">Cancel</Link>
                        <PrimaryButton :disabled="form.processing">Create League</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
