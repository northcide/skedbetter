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

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    'cf-turnstile-response': '',
});

const turnstileRef = ref(null);

onMounted(() => {
    if (props.turnstileSiteKey) {
        // Load Turnstile script
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
            // Reset turnstile
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
            <h2 class="text-xl font-bold text-gray-900">Create your account</h2>
            <p class="mt-1 text-sm text-gray-500">Get started with SkedBetter</p>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
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

            <!-- Cloudflare Turnstile -->
            <div v-if="turnstileSiteKey">
                <div ref="turnstileRef"></div>
                <InputError class="mt-1.5" :message="form.errors.captcha" />
            </div>

            <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                Create account
            </PrimaryButton>

            <p class="text-center text-sm text-gray-500">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-brand-600 hover:text-brand-700">
                    Sign in
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
