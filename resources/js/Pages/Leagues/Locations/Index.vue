<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    league: Object,
    locations: Array,
    userRole: String,
});

const isManager = ['superadmin', 'league_manager'].includes(props.userRole);

const deleteLocation = (location) => {
    if (confirm(`Delete "${location.name}"? This will also delete all fields at this location.`)) {
        router.delete(route('leagues.locations.destroy', [props.league.slug, location.id]));
    }
};
</script>

<template>
    <Head :title="`${league.name} - Locations`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('leagues.show', league.slug)" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; {{ league.name }}</Link>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Locations & Fields</h2>
                </div>
                <Link v-if="isManager" :href="route('leagues.locations.create', league.slug)">
                    <PrimaryButton>Add Location</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashMessage />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="locations.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">No locations yet. Add your first location to start managing fields.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="location in locations" :key="location.id" class="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div class="flex items-center justify-between p-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">{{ location.name }}</h3>
                                <p v-if="location.address" class="text-sm text-gray-500">
                                    {{ location.address }}{{ location.city ? `, ${location.city}` : '' }}{{ location.state ? `, ${location.state}` : '' }} {{ location.zip }}
                                </p>
                                <p class="mt-1 text-sm text-gray-400">{{ location.fields_count }} field(s)</p>
                            </div>
                            <div v-if="isManager" class="flex gap-2">
                                <Link :href="route('leagues.locations.edit', [league.slug, location.id])" class="text-sm text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </Link>
                                <button @click="deleteLocation(location)" class="text-sm text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
