<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
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
    turnstile_site_key: props.settings.turnstile_site_key || '',
    turnstile_secret_key: '',
    stripe_key: props.settings.stripe_key || '',
    stripe_secret: '',
    stripe_webhook_secret: '',
    stripe_starter_monthly_price: props.settings.stripe_starter_monthly_price || '',
    stripe_starter_annual_price: props.settings.stripe_starter_annual_price || '',
    stripe_standard_monthly_price: props.settings.stripe_standard_monthly_price || '',
    stripe_standard_annual_price: props.settings.stripe_standard_annual_price || '',
    stripe_pro_monthly_price: props.settings.stripe_pro_monthly_price || '',
    stripe_pro_annual_price: props.settings.stripe_pro_annual_price || '',
});

const testForm = useForm({ test_email: '' });

const save = () => form.post(route('admin.settings.update'));
const sendTest = () => testForm.post(route('admin.settings.test-email'));
</script>

<template>
    <Head title="Platform Settings" />

    <AdminLayout>
        <h2 class="text-base font-semibold text-gray-900">Platform Settings</h2>

        <FlashMessage />

        <div class="mt-3 max-w-3xl">
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

                <!-- Cloudflare Turnstile (CAPTCHA) -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Cloudflare Turnstile (CAPTCHA)</h3>
                    <p class="mt-1 text-[11px] text-gray-400">Protects the public registration form. Get keys from <a href="https://dash.cloudflare.com/turnstile" target="_blank" class="text-brand-600 hover:underline">Cloudflare Turnstile</a>.</p>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="ts_site" value="Site Key" class="text-xs" />
                            <TextInput id="ts_site" v-model="form.turnstile_site_key" type="text" class="mt-1 block w-full" placeholder="0x4AAAAAAA..." />
                        </div>
                        <div>
                            <InputLabel for="ts_secret" value="Secret Key" class="text-xs" />
                            <TextInput id="ts_secret" v-model="form.turnstile_secret_key" type="password" class="mt-1 block w-full" :placeholder="settings.turnstile_secret_key_set ? '••••••••' : ''" />
                        </div>
                    </div>
                    <p v-if="!settings.turnstile_site_key" class="mt-2 text-[11px] text-amber-600">CAPTCHA is disabled until keys are configured.</p>
                </div>

                <!-- Stripe Payments -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900">Stripe Payments</h3>
                    <p class="mt-1 text-[11px] text-gray-400">Connect Stripe for subscription billing. Get keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-brand-600 hover:underline">Stripe Dashboard</a>.</p>

                    <div class="mt-3 space-y-3">
                        <div>
                            <InputLabel for="stripe_key" value="Publishable Key" class="text-xs" />
                            <TextInput id="stripe_key" v-model="form.stripe_key" type="text" class="mt-1 block w-full" placeholder="pk_live_..." />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="stripe_secret" value="Secret Key" class="text-xs" />
                                <TextInput id="stripe_secret" v-model="form.stripe_secret" type="password" class="mt-1 block w-full" :placeholder="settings.stripe_secret_set ? '••••••••' : 'sk_live_...'" />
                            </div>
                            <div>
                                <InputLabel for="stripe_wh" value="Webhook Secret" class="text-xs" />
                                <TextInput id="stripe_wh" v-model="form.stripe_webhook_secret" type="password" class="mt-1 block w-full" :placeholder="settings.stripe_webhook_secret_set ? '••••••••' : 'whsec_...'" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-semibold text-gray-500">Price IDs</h4>
                        <p class="text-[11px] text-gray-400">Copy the Price ID (starts with <code class="text-[10px]">price_</code>) for each plan from your Stripe products.</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="s_starter_m" value="Starter Monthly" class="text-xs" />
                                <TextInput id="s_starter_m" v-model="form.stripe_starter_monthly_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                            <div>
                                <InputLabel for="s_starter_a" value="Starter Annual" class="text-xs" />
                                <TextInput id="s_starter_a" v-model="form.stripe_starter_annual_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="s_standard_m" value="Standard Monthly" class="text-xs" />
                                <TextInput id="s_standard_m" v-model="form.stripe_standard_monthly_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                            <div>
                                <InputLabel for="s_standard_a" value="Standard Annual" class="text-xs" />
                                <TextInput id="s_standard_a" v-model="form.stripe_standard_annual_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="s_pro_m" value="Pro Monthly" class="text-xs" />
                                <TextInput id="s_pro_m" v-model="form.stripe_pro_monthly_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                            <div>
                                <InputLabel for="s_pro_a" value="Pro Annual" class="text-xs" />
                                <TextInput id="s_pro_a" v-model="form.stripe_pro_annual_price" type="text" class="mt-1 block w-full" placeholder="price_..." />
                            </div>
                        </div>
                    </div>
                    <p v-if="!settings.stripe_key" class="mt-2 text-[11px] text-amber-600">Stripe is disabled until keys are configured.</p>
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
    </AdminLayout>
</template>
