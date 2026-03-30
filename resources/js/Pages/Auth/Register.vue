<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    turnstileSiteKey: { type: String, default: '' },
    plans: { type: Array, default: () => [] },
});

const browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
const billingAnnual = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    league_name: '',
    league_description: '',
    league_timezone: browserTz || 'America/Chicago',
    plan: 'standard',
    billing_period: 'monthly',
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

const selectPlan = (slug) => {
    form.plan = slug;
};

const toggleBilling = () => {
    billingAnnual.value = !billingAnnual.value;
    form.billing_period = billingAnnual.value ? 'annual' : 'monthly';
};

const formatLimit = (val) => val === null ? 'Unlimited' : val;

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
            <p class="mt-1 text-sm text-gray-500">Create your account, pick a plan, and set up your league</p>
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

            <!-- Plan Selection -->
            <div class="space-y-4 border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Choose a Plan</p>
                    <button type="button" @click="toggleBilling"
                        class="flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-medium"
                        :class="billingAnnual ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                        <span :class="!billingAnnual ? 'font-bold' : ''">Monthly</span>
                        <div class="relative h-4 w-8 rounded-full transition-colors" :class="billingAnnual ? 'bg-green-500' : 'bg-gray-300'">
                            <div class="absolute top-0.5 h-3 w-3 rounded-full bg-white shadow transition-all" :class="billingAnnual ? 'left-[18px]' : 'left-0.5'" />
                        </div>
                        <span :class="billingAnnual ? 'font-bold' : ''">Annual</span>
                        <span v-if="billingAnnual" class="text-green-600">Save ~17%</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <button v-for="plan in plans" :key="plan.slug" type="button"
                        @click="selectPlan(plan.slug)"
                        class="relative rounded-lg border-2 p-3 text-left transition-all"
                        :class="form.plan === plan.slug
                            ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500'
                            : 'border-gray-200 hover:border-gray-300'">
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-semibold text-gray-900">{{ plan.name }}</span>
                            <span v-if="plan.slug === 'standard'" class="rounded-full bg-brand-100 px-2 py-0.5 text-[9px] font-semibold text-brand-700">Popular</span>
                        </div>
                        <div class="mt-1">
                            <span class="text-2xl font-bold text-gray-900">${{ billingAnnual ? plan.annual_price : plan.monthly_price }}</span>
                            <span class="text-xs text-gray-500">{{ billingAnnual ? '/yr' : '/mo' }}</span>
                        </div>
                        <ul class="mt-2 space-y-1">
                            <li class="text-[11px] text-gray-500">{{ formatLimit(plan.limits.teams) }} teams</li>
                            <li class="text-[11px] text-gray-500">{{ formatLimit(plan.limits.fields) }} fields</li>
                            <li class="text-[11px] text-gray-500">{{ formatLimit(plan.limits.divisions) }} divisions</li>
                        </ul>
                        <div v-if="form.plan === plan.slug" class="absolute right-2 top-2">
                            <svg class="h-4 w-4 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        </div>
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.plan" />
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
                {{ form.processing ? 'Creating...' : 'Start Your 14-Day Free Trial' }}
            </PrimaryButton>

            <p class="text-center text-xs text-gray-400">
                14-day free trial. You'll be redirected to Stripe to enter payment details. Cancel anytime.
            </p>

            <p class="text-center text-sm text-gray-500">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-brand-600 hover:text-brand-700">Sign in</Link>
            </p>
        </form>
    </GuestLayout>
</template>
