<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-1 pt-0.5 border-b-2 border-brand-500 text-xs font-medium text-brand-700 focus:outline-none transition'
        : 'inline-flex items-center px-1 pt-0.5 border-b-2 border-transparent text-xs font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
