<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ fieldTypes: Array });

const showAddForm = ref(false);
const editingId = ref(null);

const addForm = useForm({ name: '', description: '' });
const editForm = useForm({ name: '', description: '', is_active: true });

function submitAdd() {
    addForm.post(route('admin.field-types.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset();
            showAddForm.value = false;
        },
    });
}

function startEdit(ft) {
    editingId.value = ft.id;
    editForm.name = ft.name;
    editForm.description = ft.description || '';
    editForm.is_active = ft.is_active;
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
}

function submitEdit(ft) {
    editForm.put(route('admin.field-types.update', ft.id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function deleteType(ft) {
    if (!confirm(`Delete "${ft.name}"?`)) return;
    router.delete(route('admin.field-types.destroy', ft.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Admin - Field Types" />

    <AdminLayout>
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Field Types</h2>
            <PrimaryButton v-if="!showAddForm" size="sm" @click="showAddForm = true">Add Field Type</PrimaryButton>
        </div>

        <FlashMessage />

        <!-- Add Form -->
        <div v-if="showAddForm" class="mt-3 rounded-lg border border-brand-200 bg-brand-50 p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-900">New Field Type</h3>
            <form @submit.prevent="submitAdd" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input v-model="addForm.name" type="text" required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="e.g. Baseball Diamond" />
                    <InputError :message="addForm.errors.name" class="mt-1" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Description</label>
                    <input v-model="addForm.description" type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Optional description" />
                    <InputError :message="addForm.errors.description" class="mt-1" />
                </div>
                <div class="flex items-center gap-2">
                    <PrimaryButton :disabled="addForm.processing" size="sm">Save</PrimaryButton>
                    <button type="button" @click="showAddForm = false; addForm.reset()" class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Field Types List -->
        <div class="mt-3 rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-4 py-2">
                <h3 class="text-sm font-semibold text-gray-900">All Field Types ({{ fieldTypes.length }})</h3>
            </div>
            <div v-if="fieldTypes.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                No field types yet. Add one above.
            </div>
            <div v-else class="divide-y divide-gray-50">
                <div v-for="ft in fieldTypes" :key="ft.id" class="px-4 py-2.5">
                    <!-- View Mode -->
                    <div v-if="editingId !== ft.id" class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900" :class="{ 'opacity-50': !ft.is_active }">
                                {{ ft.name }}
                                <span v-if="!ft.is_active" class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-semibold text-gray-500">Inactive</span>
                            </p>
                            <p v-if="ft.description" class="text-xs text-gray-500">{{ ft.description }}</p>
                            <p class="text-[10px] text-gray-400">{{ ft.fields_count }} field{{ ft.fields_count !== 1 ? 's' : '' }} using this type</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="startEdit(ft)" class="text-[10px] text-gray-500 hover:text-gray-700">Edit</button>
                            <button @click="deleteType(ft)" class="text-[10px] text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <form v-else @submit.prevent="submitEdit(ft)" class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Name</label>
                            <input v-model="editForm.name" type="text" required
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            <InputError :message="editForm.errors.name" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Description</label>
                            <input v-model="editForm.description" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                <input v-model="editForm.is_active" type="checkbox" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                                Active
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <PrimaryButton :disabled="editForm.processing" size="sm">Save</PrimaryButton>
                            <button type="button" @click="cancelEdit" class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
