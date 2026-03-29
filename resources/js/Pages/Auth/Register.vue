<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    turnstileSiteKey: { type: String, default: '' },
});

const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    league_name: '',
    league_description: '',
    league_timezone: browserTz || 'America/Chicago',
    'cf-turnstile-response': '',
});

const turnstileRef = ref(null);

onMounted(() => {
    if (props.turnstileSiteKey) {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad';
        script.async = true;
        window.onTurnstileLoad = () => {
            if (turnstileRef.value) {
                window.turnstile.render(turnstileRef.value, {
                    sitekey: props.turnstileSiteKey,
                    callback: (token) => { form['cf-turnstile-response'] = token; },
                    'expired-callback': () => { form['cf-turnstile-response'] = ''; },
                });
            }
        };
        document.head.appendChild(script);
    }
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            if (props.turnstileSiteKey && window.turnstile) {
                window.turnstile.reset();
                form['cf-turnstile-response'] = '';
            }
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div>
            <h2 class="text-xl font-bold text-gray-900">Get started with SkedBetter</h2>
            <p class="mt-1 text-sm text-gray-500">Create your account and set up your league</p>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <!-- Account Info -->
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Your Account</p>

                <div>
                    <InputLabel for="name" value="Full name" />
                    <TextInput id="name" type="text" class="mt-1.5 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                    <InputError class="mt-1.5" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email address" />
                    <TextInput id="email" type="email" class="mt-1.5 block w-full" v-model="form.email" required autocomplete="username" placeholder="you@example.com" />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" type="password" class="mt-1.5 block w-full" v-model="form.password" required autocomplete="new-password" />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm password" />
                        <TextInput id="password_confirmation" type="password" class="mt-1.5 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                        <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- League Info -->
            <div class="space-y-4 border-t border-gray-100 pt-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Your League</p>

                <div>
                    <InputLabel for="league_name" value="League name" />
                    <TextInput id="league_name" type="text" class="mt-1.5 block w-full" v-model="form.league_name" required placeholder="e.g. Springfield Youth Baseball" />
                    <InputError class="mt-1.5" :message="form.errors.league_name" />
                </div>

                <div>
                    <InputLabel for="league_description" value="Description (optional)" />
                    <textarea id="league_description" v-model="form.league_description" class="mt-1.5 block w-full rounded-md border-gray-300" rows="2" placeholder="Brief description of your league" />
                </div>

                <div>
                    <InputLabel for="league_timezone" value="Timezone" />
                    <select id="league_timezone" v-model="form.league_timezone" class="mt-1.5 block w-full">
                        <option value="America/New_York">Eastern (America/New_York)</option>
                        <option value="America/Chicago">Central (America/Chicago)</option>
                        <option value="America/Denver">Mountain (America/Denver)</option>
                        <option value="America/Los_Angeles">Pacific (America/Los_Angeles)</option>
                        <option value="America/Anchorage">Alaska</option>
                        <option value="Pacific/Honolulu">Hawaii</option>
                        <option v-if="!['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Anchorage','Pacific/Honolulu'].includes(browserTz)" :value="browserTz">{{ browserTz }} (detected)</option>
                    </select>
                </div>
            </div>

            <!-- Cloudflare Turnstile -->
            <div v-if="turnstileSiteKey" class="flex flex-col items-center">
                <div ref="turnstileRef"></div>
                <InputError class="mt-1.5" :message="form.errors.captcha" />
            </div>

            <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                {{ form.processing ? 'Creating...' : 'Create Account & Request League' }}
            </PrimaryButton>

            <p class="text-center text-xs text-gray-400">
                Your league will be reviewed by an administrator. You'll be notified once approved.
            </p>

            <p class="text-center text-sm text-gray-500">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-brand-600 hover:text-brand-700">Sign in</Link>
            </p>
        </form>
    </GuestLayout>
</template>
