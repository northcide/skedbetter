<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'All' },
    labelKey: { type: String, default: 'label' },
    valueKey: { type: String, default: 'value' },
    class: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const search = ref('');
const wrapper = ref(null);
const trigger = ref(null);
const dropdown = ref(null);
const searchInput = ref(null);
const highlightIndex = ref(-1);
const dropdownStyle = ref({});

const selectedLabel = computed(() => {
    if (!props.modelValue && props.modelValue !== 0) return props.placeholder;
    const found = props.options.find(o => String(val(o)) === String(props.modelValue));
    return found ? lbl(found) : props.placeholder;
});

const filtered = computed(() => {
    if (!search.value) return props.options;
    const q = search.value.toLowerCase();
    return props.options.filter(o => lbl(o).toLowerCase().includes(q));
});

function val(o) {
    return typeof o === 'object' ? o[props.valueKey] : o;
}

function lbl(o) {
    return typeof o === 'object' ? o[props.labelKey] : String(o);
}

function positionDropdown() {
    if (!trigger.value) return;
    const rect = trigger.value.getBoundingClientRect();
    dropdownStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 180)}px`,
        zIndex: 9999,
    };
}

function toggle() {
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        highlightIndex.value = -1;
        positionDropdown();
        nextTick(() => searchInput.value?.focus());
    }
}

function select(option) {
    emit('update:modelValue', option === null ? '' : val(option));
    open.value = false;
    search.value = '';
}

function onKeydown(e) {
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightIndex.value = Math.min(highlightIndex.value + 1, filtered.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightIndex.value = Math.max(highlightIndex.value - 1, -1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (highlightIndex.value === -1) {
            select(null);
        } else if (filtered.value[highlightIndex.value]) {
            select(filtered.value[highlightIndex.value]);
        }
    } else if (e.key === 'Escape') {
        open.value = false;
    }
}

function onClickOutside(e) {
    if (wrapper.value && !wrapper.value.contains(e.target) &&
        dropdown.value && !dropdown.value.contains(e.target)) {
        open.value = false;
    }
}

function onScroll() {
    if (open.value) positionDropdown();
}

watch(search, () => {
    highlightIndex.value = -1;
});

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    window.addEventListener('scroll', onScroll, true);
});
onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside);
    window.removeEventListener('scroll', onScroll, true);
});
</script>

<template>
    <div ref="wrapper" class="relative" :class="props.class">
        <!-- Trigger button -->
        <button ref="trigger" type="button" @click="toggle"
            class="flex w-full items-center justify-between rounded-md border border-gray-300 bg-white py-1.5 pl-2.5 pr-2 text-left text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            <span :class="modelValue ? 'text-gray-900' : 'text-gray-500'" class="truncate">{{ selectedLabel }}</span>
            <svg class="ml-1 h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>

        <!-- Dropdown (teleported to body to avoid overflow clipping) -->
        <Teleport to="body">
            <div v-if="open" ref="dropdown" :style="dropdownStyle" class="rounded-md border border-gray-200 bg-white shadow-lg">
                <!-- Search input -->
                <div class="border-b border-gray-100 p-1.5">
                    <input ref="searchInput" v-model="search" @keydown="onKeydown" type="text" placeholder="Search..."
                        class="w-full rounded border-gray-200 px-2 py-1 text-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>

                <!-- Options list -->
                <ul class="max-h-48 overflow-y-auto py-1">
                    <!-- "All" / placeholder option -->
                    <li @click="select(null)"
                        class="cursor-pointer px-2.5 py-1.5 text-sm"
                        :class="highlightIndex === -1 ? 'bg-brand-50 text-brand-700' : 'text-gray-500 hover:bg-gray-50'">
                        {{ placeholder }}
                    </li>

                    <li v-for="(option, i) in filtered" :key="val(option)" @click="select(option)"
                        class="cursor-pointer px-2.5 py-1.5 text-sm"
                        :class="[
                            highlightIndex === i ? 'bg-brand-50 text-brand-700' : 'text-gray-900 hover:bg-gray-50',
                            String(val(option)) === String(modelValue) ? 'font-medium' : '',
                        ]">
                        <slot name="option" :option="option">{{ lbl(option) }}</slot>
                    </li>

                    <li v-if="filtered.length === 0" class="px-2.5 py-2 text-xs text-gray-400">No matches</li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>
