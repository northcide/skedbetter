<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    plans: { type: Array, default: () => [] },
});

const billingAnnual = ref(false);
const formatLimit = (val) => val === null ? 'Unlimited' : val;

// Lightbox
const lightboxSrc = ref(null);
const lightboxAlt = ref('');
const openLightbox = (src, alt) => { lightboxSrc.value = src; lightboxAlt.value = alt; };
const closeLightbox = () => { lightboxSrc.value = null; };

// Scroll animation
const observed = ref(new Set());
let observer = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                observed.value.add(e.target.dataset.anim);
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('[data-anim]').forEach(el => observer.observe(el));
});

onUnmounted(() => observer?.disconnect());
const isVisible = (id) => observed.value.has(id);
</script>

<template>
    <Head title="SkedBetter - Field Scheduling for Sports Leagues">
        <meta head-key="description" name="description" content="SkedBetter is the modern field scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection, drag-and-drop calendars, booking windows, and zero double-bookings." />
        <meta head-key="og:title" property="og:title" content="SkedBetter - Schedule Fields, Not Headaches" />
        <meta head-key="og:description" property="og:description" content="The modern scheduling platform for youth sports leagues. Visual calendars, conflict detection, mobile-friendly, and built for coaches and league admins." />
    </Head>

    <div class="min-h-screen">
        <!-- Nav -->
        <nav class="absolute inset-x-0 top-0 z-50">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-2.5">
                    <ApplicationLogo class="h-8 w-8 text-white" />
                    <span class="text-xl font-bold tracking-tight text-white">SkedBetter</span>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="#pricing" class="hidden text-sm font-medium text-white/70 transition hover:text-white sm:block">Pricing</a>
                    <a href="#features" class="hidden text-sm font-medium text-white/70 transition hover:text-white sm:block">Features</a>
                    <Link v-if="canLogin" :href="route('login')" class="text-sm font-medium text-white/70 transition hover:text-white">
                        Sign in
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-brand-950 shadow-sm transition hover:bg-accent-400">
                        Start Free Trial
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero — asymmetric split -->
        <div class="relative overflow-hidden bg-brand-950">
            <!-- Subtle field pattern overlay -->
            <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; stroke=&quot;%23fff&quot; stroke-width=&quot;1&quot;%3E%3Crect x=&quot;0&quot; y=&quot;0&quot; width=&quot;60&quot; height=&quot;60&quot;/%3E%3Cline x1=&quot;30&quot; y1=&quot;0&quot; x2=&quot;30&quot; y2=&quot;60&quot;/%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;10&quot;/%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative mx-auto grid max-w-7xl gap-12 px-6 pb-20 pt-28 sm:pt-36 lg:grid-cols-2 lg:items-center lg:gap-16 lg:pb-28">
                <!-- Left: copy -->
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-accent-400">Field scheduling for sports leagues</p>
                    <h1 class="mt-4 font-display text-4xl leading-tight text-white sm:text-5xl lg:text-6xl">
                        Schedule fields.<br>
                        <span class="text-accent-400">Not headaches.</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-relaxed text-brand-200/80">
                        Coaches book field time from their phones. Conflicts get caught before they happen. No more spreadsheets, no more double-bookings, no more angry emails at 10pm.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-accent-500 px-6 py-3 text-base font-bold text-brand-950 shadow-lg transition hover:bg-accent-400 hover:shadow-xl">
                            Start Your Free Trial
                        </Link>
                        <a href="#how-it-works" class="text-base font-medium text-brand-300 underline decoration-brand-300/30 underline-offset-4 transition hover:text-white hover:decoration-white/50">
                            See how it works
                        </a>
                    </div>
                    <p class="mt-4 text-xs text-brand-400/60">14-day free trial. No credit card to start.</p>
                </div>

                <!-- Right: screenshot placeholder -->
                <div class="relative hidden lg:block">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-1.5 shadow-2xl backdrop-blur">
                        <!-- Browser chrome -->
                        <div class="flex items-center gap-1.5 rounded-t-lg bg-brand-900/80 px-3 py-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-400/60"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400/60"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-green-400/60"></span>
                            <span class="ml-3 rounded bg-brand-800/60 px-8 py-0.5 text-[10px] text-brand-400">skedbetter.com</span>
                        </div>
                        <!-- Screenshot area -->
                        <div class="rounded-b-lg bg-brand-900/40 overflow-hidden">
                            <img src="/images/calendar-week-view.webp" alt="SkedBetter calendar week view with color-coded team bookings across multiple fields" class="w-full h-auto" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="hidden aspect-[4/3] items-center justify-center p-8" style="display:none;">
                                <div class="text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-800/60">
                                        <svg class="h-6 w-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="3" y1="10" x2="21" y2="10" /><line x1="9" y1="4" x2="9" y2="10" /><line x1="15" y1="4" x2="15" y2="10" /></svg>
                                    </div>
                                    <p class="text-sm font-medium text-brand-300">Screenshot coming soon</p>
                                    <p class="mt-1 text-xs text-brand-500">Color-coded team bookings across multiple fields</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative glow -->
                    <div class="absolute -inset-4 -z-10 rounded-2xl bg-accent-500/10 blur-3xl"></div>
                </div>
            </div>
        </div>

        <!-- Who It's For -->
        <div class="bg-white py-20" data-anim="who" id="who">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center" :class="{ 'animate-fade-in-up': isVisible('who') }">
                    <h2 class="font-display text-3xl text-gray-900 sm:text-4xl">Scheduling shouldn't be a one-person job</h2>
                </div>

                <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="rounded-2xl bg-brand-50 p-7">
                        <p class="text-2xl font-bold text-brand-700">League Admins</p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600">You're juggling 40 teams across 12 fields and 3 divisions. You need a bird's-eye view and the power to set rules that coaches actually follow.</p>
                        <div class="mt-4 h-40 rounded-lg bg-white/60 overflow-hidden cursor-pointer" @click="openLightbox('/images/divisions-teams-overview.webp', 'Divisions and teams page showing hierarchical view with nested teams and coach assignments')">
                            <img src="/images/divisions-teams-overview.webp" alt="Divisions and teams page showing hierarchical view with nested teams and coach assignments" class="max-w-none w-[200%] h-auto rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>
                    <div class="rounded-2xl bg-field-50 p-7">
                        <p class="text-2xl font-bold text-field-700">Facility Managers</p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600">You manage the fields, not the league politics. Blackout rain-soaked fields, set available hours, and let the system handle who books what.</p>
                        <div class="mt-4 h-40 rounded-lg bg-white/60 overflow-hidden cursor-pointer" @click="openLightbox('/images/field-management.webp', 'Field management with blackout rules and time slots')">
                            <img src="/images/field-management.webp" alt="Field management with blackout rules and time slots" class="max-w-none w-[200%] h-auto rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>
                    <div class="rounded-2xl bg-accent-50 p-7">
                        <p class="text-2xl font-bold text-accent-700">Coaches</p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-600">You just want to grab a field for Tuesday practice without calling anyone. Open the app, tap a time slot, done. It syncs to your phone calendar.</p>
                        <div class="mt-4 h-40 rounded-lg bg-white/60 overflow-hidden cursor-pointer" @click="openLightbox('/images/coach-roster-invites.webp', 'Coach roster showing team assignments with email invite links for magic-link onboarding')">
                            <img src="/images/coach-roster-invites.webp" alt="Coach roster showing team assignments with email invite links for magic-link onboarding" class="max-w-none w-[200%] h-auto rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features — bento grid -->
        <div class="bg-gray-50 py-20" data-anim="features" id="features">
            <div class="mx-auto max-w-7xl px-6">
                <div :class="{ 'animate-fade-in-up': isVisible('features') }">
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand-600">What you get</p>
                    <h2 class="mt-2 font-display text-2xl text-gray-900 whitespace-nowrap sm:text-3xl lg:text-4xl">The scheduling toolkit<br class="lg:hidden"> your league has been missing</h2>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Large card: Visual Calendar -->
                    <div class="sm:col-span-2 lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-6">
                        <div class="grid gap-6 lg:grid-cols-2 lg:items-center">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Drag-and-drop calendar</h3>
                                <p class="mt-2 text-sm leading-relaxed text-gray-600">See every field side-by-side in a resource timeline. Click to book, drag to move, resize to adjust. Day, week, and month views. Color-coded by team.</p>
                            </div>
                            <div class="h-48 rounded-xl bg-gray-100 overflow-hidden cursor-pointer" @click="openLightbox('/images/drag-drop-calendar.webp', 'Drag-and-drop calendar with conflict warning overlay')">
                                <img src="/images/drag-drop-calendar.webp" alt="Drag-and-drop calendar with conflict warning overlay" class="w-full h-full object-cover object-top rounded-xl" onerror="this.parentElement.style.display='none';">
                            </div>
                        </div>
                    </div>

                    <!-- Small card -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Conflict prevention</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Five layers of detection: field overlaps, team overlaps, blackout periods, weekly limits, and custom rules. Catches problems before they happen.</p>
                        <div class="mt-3 h-36 rounded-lg bg-gray-50 overflow-hidden cursor-pointer" @click="openLightbox('/images/conflict-detection.webp', 'Conflict detection warning preventing a double-booking')">
                            <img src="/images/conflict-detection.webp" alt="Conflict detection warning preventing a double-booking" class="w-full h-full object-cover object-top rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>

                    <!-- Small card -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Division rules</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Set scheduling rules per division — booking windows, max event limits, and which fields each division can access. Fine-grained control without the complexity.</p>
                        <div class="relative mt-3 h-36 rounded-lg bg-gray-50 overflow-hidden cursor-pointer" @click="openLightbox('/images/division-schedule-rules.webp', 'Division scheduling rules with field access controls')">
                            <img src="/images/division-schedule-rules.webp" alt="Division scheduling rules with field access controls" class="max-w-none w-[200%] h-auto rounded-lg absolute bottom-0 left-0" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>

                    <!-- Small card: Booking Windows -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Booking windows</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Control when each division can start booking. Give older divisions first access, then open it up to everyone. Fair, transparent, automatic.</p>
                        <div class="mt-3 h-36 rounded-lg bg-gray-50 overflow-hidden cursor-pointer" @click="openLightbox('/images/booking-window-priority.webp', 'New Booking Window form assigning early access to older divisions like 10U and 12U Baseball')">
                            <img src="/images/booking-window-priority.webp" alt="New Booking Window form assigning early access to older divisions like 10U and 12U Baseball" class="w-full h-full object-cover object-top rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>

                    <!-- Large card: Notifications -->
                    <div class="sm:col-span-2 lg:col-span-1 rounded-2xl border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Instant notifications</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">Coaches get an email the moment a schedule is created, moved, or cancelled. No more "I didn't know practice was moved."</p>
                        <div class="mt-4 space-y-2">
                            <div class="rounded-lg bg-green-50 px-3 py-2 text-xs text-green-700">New: Tigers practice at Riverside Field A, Tue 5:30 PM</div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">Updated: Moved to Field B (was Field A)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works — alternating, not numbered circles -->
        <div class="bg-white py-20" data-anim="how" id="how-it-works">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center" :class="{ 'animate-fade-in-up': isVisible('how') }">
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand-600">Get started in minutes</p>
                    <h2 class="mt-2 font-display text-2xl text-gray-900 whitespace-nowrap sm:text-3xl lg:text-4xl">Three steps to<br class="lg:hidden"> organized scheduling</h2>
                </div>

                <div class="mt-16 space-y-12 lg:space-y-0 lg:grid lg:grid-cols-3 lg:gap-0">
                    <!-- Step 1 -->
                    <div class="relative text-center lg:px-8">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">1</div>
                        <div class="hidden lg:block absolute top-5 left-[calc(50%+28px)] w-[calc(100%-56px)] border-t-2 border-dashed border-brand-200"></div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900">Set up your league</h3>
                        <p class="mt-2 text-sm text-gray-600">Add your fields, divisions, and teams. Import from a spreadsheet or type them in. Takes about 5 minutes.</p>
                        <div class="relative mt-4 mx-auto max-w-xs h-40 rounded-lg bg-gray-50 overflow-hidden shadow-sm cursor-pointer" @click="openLightbox('/images/team-setup.webp', 'League setup wizard showing season configuration with step-by-step progress')">
                            <img src="/images/team-setup.webp" alt="League setup wizard showing season configuration with step-by-step progress" class="max-w-none w-[200%] h-auto rounded-lg" style="margin-left: auto; display: block;" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center lg:px-8">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">2</div>
                        <div class="hidden lg:block absolute top-5 left-[calc(50%+28px)] w-[calc(100%-56px)] border-t-2 border-dashed border-brand-200"></div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900">Configure your rules</h3>
                        <p class="mt-2 text-sm text-gray-600">Booking windows, field time slots, blackout dates, weekly limits. Set it once, the system enforces it.</p>
                        <div class="mt-4 mx-auto max-w-xs h-40 rounded-lg bg-gray-50 overflow-hidden shadow-sm cursor-pointer" @click="openLightbox('/images/field-slotting-rules.webp', 'Field slot rules with time slots, day selection, and division access controls')">
                            <img src="/images/field-slotting-rules.webp" alt="Field slot rules with time slots, day selection, and division access controls" class="max-w-none w-[200%] h-auto rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center lg:px-8">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-accent-500 text-lg font-bold text-brand-950">3</div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900">Coaches start booking</h3>
                        <p class="mt-2 text-sm text-gray-600">Invite your coaches. They pick a field and time from their phone. Conflicts are blocked automatically. Done.</p>
                        <div class="mt-4 mx-auto max-w-xs h-40 rounded-lg bg-gray-50 overflow-hidden shadow-sm cursor-pointer" @click="openLightbox('/images/coach-booking.webp', 'Mobile day view showing color-coded team bookings on a Saturday calendar')">
                            <img src="/images/coach-booking.webp" alt="Mobile day view showing color-coded team bookings on a Saturday calendar" class="w-full h-full object-cover object-center rounded-lg" loading="lazy" onerror="this.parentElement.style.display='none';">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sports strip -->
        <div class="border-y border-gray-100 bg-gray-50/50 py-10">
            <div class="mx-auto max-w-7xl px-6 text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-gray-400">Built for youth sports leagues</p>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-x-10 gap-y-3">
                    <span class="text-lg font-bold text-gray-300">Baseball</span>
                    <span class="text-lg font-bold text-gray-300">Softball</span>
                    <span class="text-lg font-bold text-gray-300">Soccer</span>
                    <span class="text-lg font-bold text-gray-300">Football</span>
                    <span class="text-lg font-bold text-gray-300">Lacrosse</span>
                    <span class="text-lg font-bold text-gray-300">Field Hockey</span>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white py-20" data-anim="pricing" id="pricing">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center" :class="{ 'animate-fade-in-up': isVisible('pricing') }">
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand-600">Pricing</p>
                    <h2 class="mt-2 font-display text-3xl text-gray-900 sm:text-4xl">One price, all features</h2>
                    <p class="mt-4 text-base text-gray-500">Every plan includes every feature. Pick the size that fits your league.</p>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <span class="text-sm font-medium" :class="!billingAnnual ? 'text-gray-900' : 'text-gray-400'">Monthly</span>
                        <button type="button" @click="billingAnnual = !billingAnnual"
                            class="relative h-6 w-11 rounded-full transition-colors" :class="billingAnnual ? 'bg-brand-600' : 'bg-gray-300'">
                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="billingAnnual ? 'left-[22px]' : 'left-0.5'" />
                        </button>
                        <span class="text-sm font-medium" :class="billingAnnual ? 'text-gray-900' : 'text-gray-400'">Annual</span>
                        <span v-if="billingAnnual" class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Save ~17%</span>
                    </div>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div v-for="plan in plans" :key="plan.slug"
                        class="relative flex flex-col rounded-2xl p-7 transition-shadow"
                        :class="plan.slug === 'standard'
                            ? 'border-2 border-accent-400 bg-accent-50/30 shadow-xl shadow-accent-500/10'
                            : 'border border-gray-200 bg-white'">
                        <span v-if="plan.slug === 'standard'" class="absolute -top-3 right-6 rounded-full bg-accent-500 px-3 py-0.5 text-[11px] font-bold text-brand-950">Most Popular</span>
                        <h3 class="text-lg font-bold text-gray-900">{{ plan.name }}</h3>
                        <div class="mt-3">
                            <span class="font-display text-4xl text-gray-900">${{ billingAnnual ? plan.annual_price : plan.monthly_price }}</span>
                            <span class="text-sm text-gray-400">{{ billingAnnual ? '/yr' : '/mo' }}</span>
                        </div>
                        <ul class="mt-5 flex-1 space-y-2.5">
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                Up to {{ formatLimit(plan.limits.teams) }} teams
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                Up to {{ formatLimit(plan.limits.fields) }} fields
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                Up to {{ formatLimit(plan.limits.divisions) }} divisions
                            </li>
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                All features included
                            </li>
                        </ul>
                        <Link v-if="canRegister" :href="route('register')"
                            class="mt-6 block w-full rounded-lg py-2.5 text-center text-sm font-bold transition"
                            :class="plan.slug === 'standard'
                                ? 'bg-accent-500 text-brand-950 hover:bg-accent-400'
                                : 'bg-gray-900 text-white hover:bg-gray-800'">
                            Start Free Trial
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="relative overflow-hidden bg-brand-950 py-20">
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; stroke=&quot;%23fff&quot; stroke-width=&quot;1&quot;%3E%3Crect x=&quot;0&quot; y=&quot;0&quot; width=&quot;60&quot; height=&quot;60&quot;/%3E%3Cline x1=&quot;30&quot; y1=&quot;0&quot; x2=&quot;30&quot; y2=&quot;60&quot;/%3E%3Ccircle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;10&quot;/%3E%3C/g%3E%3C/svg%3E');"></div>
            <div class="relative mx-auto max-w-3xl px-6 text-center">
                <h2 class="font-display text-3xl text-white sm:text-4xl">Stop managing schedules in spreadsheets</h2>
                <p class="mt-4 text-lg text-brand-300/80">Your coaches will thank you. Your fields will actually get used. And you'll never get another "wait, that field was already booked" text.</p>
                <div class="mt-8">
                    <Link v-if="canRegister" :href="route('register')" class="inline-block rounded-lg bg-accent-500 px-8 py-3.5 text-base font-bold text-brand-950 shadow-lg transition hover:bg-accent-400 hover:shadow-xl">
                        Start Your Free Trial
                    </Link>
                </div>
                <p class="mt-3 text-xs text-brand-500/60">14-day trial. No credit card required. Cancel anytime.</p>
            </div>
        </div>

        <!-- Footer — multi-column -->
        <footer class="border-t border-gray-200 bg-white py-12">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
                    <!-- Brand -->
                    <div class="col-span-2 sm:col-span-1">
                        <div class="flex items-center gap-2">
                            <ApplicationLogo class="h-6 w-6 text-brand-600" />
                            <span class="text-base font-bold text-gray-900">SkedBetter</span>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-gray-400">Field scheduling for sports leagues that actually works.</p>
                    </div>
                    <!-- Product -->
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Product</p>
                        <ul class="mt-3 space-y-2">
                            <li><a href="#features" class="text-sm text-gray-600 hover:text-gray-900">Features</a></li>
                            <li><a href="#pricing" class="text-sm text-gray-600 hover:text-gray-900">Pricing</a></li>
                            <li><Link v-if="canRegister" :href="route('register')" class="text-sm text-gray-600 hover:text-gray-900">Sign Up</Link></li>
                        </ul>
                    </div>
                    <!-- Company -->
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Company</p>
                        <ul class="mt-3 space-y-2">
                            <li><a href="mailto:support@skedbetter.com" class="text-sm text-gray-600 hover:text-gray-900">Contact</a></li>
                        </ul>
                    </div>
                    <!-- Legal -->
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Legal</p>
                        <ul class="mt-3 space-y-2">
                            <li><span class="text-sm text-gray-400">Privacy Policy</span></li>
                            <li><span class="text-sm text-gray-400">Terms of Service</span></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 border-t border-gray-100 pt-6 text-center text-xs text-gray-400">
                    &copy; {{ new Date().getFullYear() }} SkedBetter. All rights reserved.
                </div>
            </div>
        </footer>

        <!-- Lightbox Modal -->
        <Teleport to="body">
            <Transition name="lightbox">
                <div v-if="lightboxSrc" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" @click.self="closeLightbox">
                    <button @click="closeLightbox" class="absolute top-4 right-4 text-white/70 hover:text-white transition">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <img :src="lightboxSrc" :alt="lightboxAlt" class="max-h-[90vh] max-w-[90vw] rounded-xl shadow-2xl object-contain">
                </div>
            </Transition>
        </Teleport>

        <!-- JSON-LD Structured Data -->
        <component :is="'script'" type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "SkedBetter",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "description": "Field scheduling platform for sports leagues with conflict detection, booking windows, and mobile-friendly calendars.",
            "url": "https://skedbetter.com",
            "screenshot": "https://skedbetter.com/images/og-image.png",
            "offers": {
                "@type": "AggregateOffer",
                "lowPrice": "19",
                "highPrice": "99",
                "priceCurrency": "USD",
                "offerCount": "3"
            },
            "creator": {
                "@type": "Organization",
                "name": "SkedBetter",
                "url": "https://skedbetter.com"
            }
        }
        </component>

        <component :is="'script'" type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [
                {
                    "@type": "Question",
                    "name": "What sports does SkedBetter support?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "SkedBetter works for any sport that uses fields or facilities — baseball, softball, soccer, football, lacrosse, field hockey, and more."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can coaches book their own field time?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Yes. League admins set the rules — booking windows, field access, weekly limits — and coaches book available slots directly from their phone or computer."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How does SkedBetter prevent double-bookings?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "SkedBetter uses five layers of conflict detection: field overlaps, team overlaps, blackout periods, weekly limits, and custom rules. Conflicts are caught before they happen."
                    }
                }
            ]
        }
        </component>
    </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out both;
}
.lightbox-enter-active, .lightbox-leave-active {
    transition: opacity 0.2s ease;
}
.lightbox-enter-from, .lightbox-leave-to {
    opacity: 0;
}
</style>
