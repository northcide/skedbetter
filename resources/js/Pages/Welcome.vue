<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="SkedBetter - Field Scheduling Made Simple">
        <meta head-key="description" name="description" content="SkedBetter is the modern field scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection, drag-and-drop calendars, booking windows, and zero double-bookings." />
        <meta head-key="og:title" property="og:title" content="SkedBetter - Schedule Fields, Not Headaches" />
        <meta head-key="og:description" property="og:description" content="The modern scheduling platform for youth sports leagues. Visual calendars, conflict detection, mobile-friendly, and built for coaches and league admins." />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800">
        <!-- Nav -->
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-2">
                <ApplicationLogo class="h-8 w-8 text-brand-300" />
                <span class="text-xl font-bold tracking-tight text-white">SkedBetter</span>
            </div>
            <div v-if="canLogin" class="flex items-center gap-3">
                <Link :href="route('login')" class="rounded-lg px-4 py-2 text-sm font-medium text-brand-200 transition hover:text-white">
                    Sign in
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50">
                    Get Started
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <div class="mx-auto max-w-7xl px-6 pb-24 pt-16 sm:pt-24">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-6xl">
                    Schedule fields.<br>
                    <span class="text-brand-300">Not headaches.</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-brand-200/80">
                    The modern scheduling platform for sports leagues. Manage fields, teams, and time slots with conflict detection, drag-and-drop calendars, and zero double-bookings.
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-white px-6 py-3 text-base font-semibold text-brand-700 shadow-lg transition hover:bg-brand-50 hover:shadow-xl">
                        Start for Free
                    </Link>
                    <Link :href="route('login')" class="rounded-lg border border-brand-400/30 px-6 py-3 text-base font-semibold text-brand-200 transition hover:border-brand-300 hover:text-white">
                        Sign In
                    </Link>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">Everything your league needs</h2>
                    <p class="mt-4 text-lg text-gray-500">Built for non-technical users. Loved by league managers.</p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="3" y1="10" x2="21" y2="10" /><line x1="9" y1="4" x2="9" y2="10" /><line x1="15" y1="4" x2="15" y2="10" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Visual Calendar</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Drag-and-drop resource timeline. See every field and time slot at a glance. Click to book, drag to reschedule.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Conflict Prevention</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Five-layer conflict detection: field overlaps, team overlaps, blackout periods, weekly limits, and custom constraints.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-field-100 text-field-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Multi-League</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Fully isolated leagues with their own teams, fields, and rules. Role-based access from league managers down to coaches.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Recurring Schedules</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Bulk schedule recurring practices and games. Weekly or biweekly patterns with automatic conflict skipping.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Notifications</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Coaches get notified instantly when schedules are created, changed, or cancelled. Email and in-app.</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">Calendar Sync</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">iCal feeds for every team. Coaches subscribe in Google Calendar or Apple Calendar for automatic updates.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="bg-gray-50 py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">How it works</h2>
                    <p class="mt-4 text-lg text-gray-500">Get your league scheduling online in minutes.</p>
                </div>

                <div class="mt-16 grid grid-cols-1 gap-12 sm:grid-cols-3">
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-xl font-bold text-white">1</div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">Create Your League</h3>
                        <p class="mt-2 text-sm text-gray-600">Sign up, name your league, and add your divisions, teams, and fields. Import from CSV or add them one by one.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-xl font-bold text-white">2</div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">Set Your Rules</h3>
                        <p class="mt-2 text-sm text-gray-600">Configure booking windows, field restrictions, blackout dates, and weekly limits. The system enforces them automatically.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-xl font-bold text-white">3</div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">Schedule Away</h3>
                        <p class="mt-2 text-sm text-gray-600">Coaches book field time from their phones. Conflict detection prevents double-bookings. Everyone stays in sync via calendar feeds.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="bg-brand-900 py-16">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white">Ready to simplify your league scheduling?</h2>
                <p class="mt-4 text-lg text-brand-200/80">Join leagues that have eliminated scheduling conflicts and spreadsheet chaos.</p>
                <div class="mt-8">
                    <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-white px-8 py-3.5 text-base font-semibold text-brand-700 shadow-lg transition hover:bg-brand-50 hover:shadow-xl">
                        Get Started Free
                    </Link>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-brand-800 bg-brand-950 py-8 text-center text-sm text-brand-400">
            <p>SkedBetter &mdash; Field scheduling made simple.</p>
            <p class="mt-2 text-brand-500/50 text-xs">&copy; {{ new Date().getFullYear() }} SkedBetter. All rights reserved.</p>
        </div>

        <!-- JSON-LD Structured Data -->
        <component :is="'script'" type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "SkedBetter",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "description": "Field scheduling platform for sports leagues with conflict detection, booking windows, and mobile-friendly calendars.",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "USD"
            }
        }
        </component>
    </div>
</template>
