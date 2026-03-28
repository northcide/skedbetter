<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    title: { type: String, default: 'Bulk Add' },
    fields: { type: Array, default: () => [{ key: 'name', label: 'Name', required: true }] },
    // fields: [{ key: 'name', label: 'Name', required: true, type: 'text', placeholder: '' }]
});

const emit = defineEmits(['close', 'submit']);

const step = ref('count'); // 'count' or 'form'
const count = ref(5);
const rows = ref([]);
const submitting = ref(false);

watch(() => props.show, (val) => {
    if (val) { step.value = 'count'; count.value = 5; rows.value = []; }
});

function generateRows() {
    rows.value = Array.from({ length: count.value }, () => {
        const row = {};
        props.fields.forEach(f => { row[f.key] = ''; });
        return row;
    });
    step.value = 'form';
}

function addRow() {
    const row = {};
    props.fields.forEach(f => { row[f.key] = ''; });
    rows.value.push(row);
}

function removeRow(i) {
    if (rows.value.length > 1) rows.value.splice(i, 1);
}

function submit() {
    // Filter out empty rows (where all required fields are empty)
    const filled = rows.value.filter(r => props.fields.some(f => f.required && r[f.key]));
    if (filled.length === 0) return;
    submitting.value = true;
    emit('submit', filled, () => { submitting.value = false; });
}
</script>

<template>
    <Modal :show="show" @close="emit('close')" max-width="2xl">
        <div class="p-3">
            <h3 class="text-sm font-semibold text-gray-900">{{ title }}</h3>

            <!-- Step 1: Choose count -->
            <div v-if="step === 'count'" class="mt-3">
                <p class="text-xs text-gray-500">How many do you want to add?</p>
                <div class="mt-2 flex items-center gap-2">
                    <select v-model.number="count" class="w-24">
                        <option v-for="n in [2,3,4,5,6,7,8,10,12,15,20]" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <PrimaryButton @click="generateRows">Next</PrimaryButton>
                </div>
            </div>

            <!-- Step 2: Fill in rows -->
            <div v-if="step === 'form'" class="mt-2">
                <!-- Header -->
                <div class="flex items-center gap-1 px-1 mb-1">
                    <span class="w-5 text-[9px] text-gray-400">#</span>
                    <span v-for="f in fields" :key="f.key" class="flex-1 text-[10px] font-semibold text-gray-500">{{ f.label }}</span>
                    <span class="w-6"></span>
                </div>

                <!-- Rows -->
                <div class="max-h-[400px] overflow-y-auto space-y-0.5">
                    <div v-for="(row, i) in rows" :key="i" class="flex items-center gap-1">
                        <span class="w-5 text-[10px] text-gray-400 text-right shrink-0">{{ i + 1 }}</span>
                        <template v-for="f in fields" :key="f.key">
                            <input
                                v-model="row[f.key]"
                                :type="f.type || 'text'"
                                :placeholder="f.placeholder || f.label"
                                :required="f.required"
                                class="flex-1 rounded border-gray-200 px-1.5 py-1 text-xs focus:border-brand-500 focus:ring-brand-500"
                            />
                        </template>
                        <button @click="removeRow(i)" class="w-6 shrink-0 text-center text-red-400 hover:text-red-600" v-if="rows.length > 1">
                            <svg class="h-3 w-3 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between">
                    <button @click="addRow" class="text-[10px] text-brand-600 hover:text-brand-700">+ Add row</button>
                    <div class="flex gap-2">
                        <button @click="emit('close')" class="text-[10px] text-gray-500 hover:text-gray-700 px-2 py-1">Cancel</button>
                        <PrimaryButton @click="submit" :disabled="submitting">{{ submitting ? 'Saving...' : 'Add All' }}</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
