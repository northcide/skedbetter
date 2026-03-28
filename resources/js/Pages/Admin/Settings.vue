<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ settings: Object });

const form = useForm({
    mail_mailer: props.settings.mail_mailer || 'smtp',
    mail_from_address: props.settings.mail_from_address || '',
    mail_from_name: props.settings.mail_from_name || 'SkedBetter',
    smtp_host: props.settings.smtp_host || '',
    smtp_port: props.settings.smtp_port || '587',
    smtp_username: props.settings.smtp_username || '',
    smtp_password: '',
    smtp_encryption: props.settings.smtp_encryption || 'tls',
    graph_tenant_id: props.settings.graph_tenant_id || '',
    graph_client_id: props.settings.graph_client_id || '',
    graph_client_secret: '',
    magic_link_expiry_minutes: props.settings.magic_link_expiry_minutes || '60',
});

const testForm = useForm({ test_email: '' });

const save = () => form.post(route('admin.settings.update'));
const sendTest = () => testForm.post(route('admin.settings.test-email'));
</script>

<template>
    <Head title="Platform Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-900">Platform Settings</h2>
        </template>

        <FlashMessage />

        <div class="mx-auto max-w-3xl px-4 py-4 sm:px-6">
            <form @submit.prevent="save" class="space-y-5">
                <!-- General -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">General</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="from_addr" value="From Email Address" class="text-xs" />
                            <TextInput id="from_addr" v-model="form.mail_from_address" type="email" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.mail_from_address" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="from_name" value="From Name" class="text-xs" />
                            <TextInput id="from_name" v-model="form.mail_from_name" type="text" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <div class="mt-3">
                        <InputLabel for="expiry" value="Magic Link Expiry (minutes)" class="text-xs" />
                        <select id="expiry" v-model="form.magic_link_expiry_minutes" class="mt-1 block w-full">
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">60 minutes</option>
                            <option value="120">2 hours</option>
                            <option value="480">8 hours</option>
                            <option value="1440">24 hours</option>
                        </select>
                    </div>
                </div>

                <!-- Mail Transport -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Mail Transport</h3>
                    <div class="mt-3">
                        <InputLabel for="mailer" value="Transport Type" class="text-xs" />
                        <select id="mailer" v-model="form.mail_mailer" class="mt-1 block w-full">
                            <option value="smtp">SMTP</option>
                            <option value="microsoft-graph">Microsoft Graph (Office 365)</option>
                            <option value="log">Log (testing only)</option>
                        </select>
                    </div>

                    <!-- SMTP Settings -->
                    <div v-if="form.mail_mailer === 'smtp'" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-semibold text-gray-500">SMTP Configuration</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <InputLabel for="smtp_host" value="Host" class="text-xs" />
                                <TextInput id="smtp_host" v-model="form.smtp_host" type="text" class="mt-1 block w-full" placeholder="smtp.office365.com" />
                            </div>
                            <div>
                                <InputLabel for="smtp_port" value="Port" class="text-xs" />
                                <TextInput id="smtp_port" v-model="form.smtp_port" type="text" class="mt-1 block w-full" placeholder="587" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="smtp_user" value="Username" class="text-xs" />
                                <TextInput id="smtp_user" v-model="form.smtp_username" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="smtp_pass" value="Password" class="text-xs" />
                                <TextInput id="smtp_pass" v-model="form.smtp_password" type="password" class="mt-1 block w-full" :placeholder="settings.smtp_password_set ? '••••••••' : ''" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="smtp_enc" value="Encryption" class="text-xs" />
                            <select id="smtp_enc" v-model="form.smtp_encryption" class="mt-1 block w-full">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="null">None</option>
                            </select>
                        </div>
                    </div>

                    <!-- Microsoft Graph Settings -->
                    <div v-if="form.mail_mailer === 'microsoft-graph'" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-semibold text-gray-500">Microsoft Graph Configuration</h4>
                        <p class="text-[11px] text-gray-400">Requires an Azure AD app registration with Mail.Send permission. The From Address must be a valid mailbox.</p>
                        <div>
                            <InputLabel for="g_tenant" value="Tenant ID" class="text-xs" />
                            <TextInput id="g_tenant" v-model="form.graph_tenant_id" type="text" class="mt-1 block w-full" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                        </div>
                        <div>
                            <InputLabel for="g_client" value="Client ID" class="text-xs" />
                            <TextInput id="g_client" v-model="form.graph_client_id" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="g_secret" value="Client Secret" class="text-xs" />
                            <TextInput id="g_secret" v-model="form.graph_client_secret" type="password" class="mt-1 block w-full" :placeholder="settings.graph_client_secret_set ? '••••••••' : ''" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">Save Settings</PrimaryButton>
                </div>
            </form>

            <!-- Test Email -->
            <div class="mt-5 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">Test Email</h3>
                <form @submit.prevent="sendTest" class="mt-3 flex items-end gap-3">
                    <div class="flex-1">
                        <InputLabel for="test_email" value="Send test to" class="text-xs" />
                        <TextInput id="test_email" v-model="testForm.test_email" type="email" class="mt-1 block w-full" placeholder="you@example.com" required />
                    </div>
                    <PrimaryButton :disabled="testForm.processing">Send Test</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
