<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const show = ref(false);
const message = computed(() => page.props.flash?.success || page.props.flash?.error);
const isError = computed(() => !!page.props.flash?.error);

watch(message, (val) => {
    if (val) {
        show.value = true;
        setTimeout(() => { show.value = false; }, 3000);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
    >
        <div
            v-if="show && message"
            class="fixed top-4 right-4 z-50 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
            :class="isError ? 'bg-red-500' : 'bg-green-500'"
        >
            {{ message }}
        </div>
    </Transition>
</template>
