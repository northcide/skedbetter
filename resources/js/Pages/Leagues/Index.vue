<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    leagues: Array,
    canCreateLeague: Boolean,
});
</script>

<template>
    <Head title="Leagues" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Leagues
                </h2>
                <Link v-if="canCreateLeague" :href="route('leagues.create')">
                    <PrimaryButton>Create League</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashMessage />

        <div class="mt-4">
            <div class="">
                <div v-if="leagues.length === 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center text-gray-500">
                        <p class="text-lg">No leagues yet.</p>
                        <p v-if="canCreateLeague" class="mt-2">Create your first league to get started.</p>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="league in leagues"
                        :key="league.id"
                        :href="route('leagues.show', league.slug)"
                        class="block overflow-hidden rounded-lg bg-white shadow-sm transition hover:shadow-md"
                    >
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900">{{ league.name }}</h3>
                            <p v-if="league.description" class="mt-1 text-sm text-gray-500 line-clamp-2">
                                {{ league.description }}
                            </p>
                            <div class="mt-4 flex gap-4 text-sm text-gray-500">
                                <span>{{ league.teams_count }} teams</span>
                                <span>{{ league.locations_count }} locations</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
