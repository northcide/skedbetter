<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const mode = ref('magic'); // 'magic' or 'password'
const page = usePage();

const magicForm = useForm({ email: '' });
const passwordForm = useForm({ email: '', password: '', remember: false });

const sendMagicLink = () => {
    magicForm.post(route('auth.magic-link.request'));
};

const submitPassword = () => {
    passwordForm.post(route('login'), {
        onFinish: () => passwordForm.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <div>
            <h2 class="text-xl font-bold text-gray-900">Welcome back</h2>
            <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
        </div>

        <FlashMessage />

        <div v-if="page.props.flash?.success" class="mt-3 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-700">
            {{ page.props.flash.success }}
        </div>

        <div v-if="page.props.flash?.error" class="mt-3 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">
            {{ page.props.flash.error }}
        </div>

        <div v-if="status" class="mt-3 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <!-- Tab Switcher -->
        <div class="mt-5 flex rounded-lg border border-gray-200 p-0.5">
            <button
                @click="mode = 'magic'"
                class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                :class="mode === 'magic' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:text-gray-700'"
            >Email Link</button>
            <button
                @click="mode = 'password'"
                class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                :class="mode === 'password' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:text-gray-700'"
            >Password</button>
        </div>

        <!-- Magic Link Login -->
        <form v-if="mode === 'magic'" @submit.prevent="sendMagicLink" class="mt-5 space-y-4">
            <div>
                <InputLabel for="magic_email" value="Email address" />
                <TextInput
                    id="magic_email"
                    type="email"
                    class="mt-1.5 block w-full"
                    v-model="magicForm.email"
                    required
                    autofocus
                    placeholder="you@example.com"
                />
                <InputError class="mt-1.5" :message="magicForm.errors.email" />
            </div>

            <PrimaryButton class="w-full justify-center" :disabled="magicForm.processing">
                {{ magicForm.processing ? 'Sending...' : 'Send Login Link' }}
            </PrimaryButton>

            <p class="text-center text-xs text-gray-400">We'll email you a secure link to sign in. No password needed.</p>
        </form>

        <!-- Password Login -->
        <form v-if="mode === 'password'" @submit.prevent="submitPassword" class="mt-5 space-y-4">
            <div>
                <InputLabel for="pw_email" value="Email address" />
                <TextInput
                    id="pw_email"
                    type="email"
                    class="mt-1.5 block w-full"
                    v-model="passwordForm.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <InputError class="mt-1.5" :message="passwordForm.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="pw_password" value="Password" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-brand-600 hover:text-brand-700"
                    >Forgot password?</Link>
                </div>
                <TextInput
                    id="pw_password"
                    type="password"
                    class="mt-1.5 block w-full"
                    v-model="passwordForm.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-1.5" :message="passwordForm.errors.password" />
            </div>

            <div>
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="passwordForm.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <PrimaryButton class="w-full justify-center" :disabled="passwordForm.processing">
                Sign in
            </PrimaryButton>

            <p class="text-center text-xs text-gray-400">Password login is available for league managers and admins.</p>
        </form>

        <div class="mt-4 text-center">
            <span class="text-xs text-gray-400">Don't have an account?</span>
            <Link :href="route('register')" class="ml-1 text-xs font-medium text-brand-600 hover:text-brand-700">Sign up</Link>
        </div>
    </GuestLayout>
</template>
