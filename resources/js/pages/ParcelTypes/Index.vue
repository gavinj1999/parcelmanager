<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parcel Types',
        href: '/parcel-types',
    },
];

const props = defineProps({
    parcelTypes: Array,
    rounds: Array,
});

// Predefined parcel type names from the seeder
const parcelTypeNames = [
    'Postable',
    'Small Packet',
    'Packet',
    'Parcel',
    'Heavy',
    'Heavy / Large',
    'Hanging Garment',
    'Manifested Collections',
];

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({
    id: null,
    round_id: null,
    name: '',
    max_weight: '',
    max_length: '',
    rate: '',
});

const resetForm = () => {
    form.value = { id: null, round_id: null, name: '', max_weight: '', max_length: '', rate: '' };
};

const openAddModal = () => {
    resetForm();
    showAddModal.value = true;
};

const openEditModal = (parceltype) => {
    form.value = { ...parceltype };
    showEditModal.value = true;
};

const openDeleteModal = (parceltype) => {
    form.value = { id: parceltype.id };
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (form.value.id) {
        router.put(`/parcel-types/${form.value.id}`, form.value, {
            onSuccess: () => {
                showEditModal.value = false;
                resetForm();
            },
        });
    } else {
        router.post('/parcel-types', form.value, {
            onSuccess: () => {
                showAddModal.value = false;
                resetForm();
            },
        });
    }
};

const deleteType = () => {
    router.delete(`/parcel-types/${form.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            resetForm();
        },
    });
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Parcel Types</h1>
            <button @click="openAddModal" class="bg-blue-500 text-white px-4 py-2 rounded">Add Parcel Type</button>
            <table class="w-full mt-4 border">
                <thead>
                    <tr class="bg-gray-800 text-gray-100">
                        <th class="p-2 text-left">Round</th>
                        <th class="p-2 text-left">Name</th>
                        <th class="p-2 text-left">Max Weight (kg)</th>
                        <th class="p-2 text-left">Max Length (cm)</th>
                        <th class="p-2 text-left">Rate (£)</th>
                        <th class="p-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="parceltype in parcelTypes" :key="parceltype.id" class="border-t">
                        <td class="p-2">{{ parceltype.round.name }}</td>
                        <td class="p-2">{{ parceltype.name }}</td>
                        <td class="p-2">{{ parceltype.max_weight }}</td>
                        <td class="p-2">{{ parceltype.max_length }}</td>
                        <td class="p-2">{{ parceltype.rate }}</td>
                        <td class="p-2">
                            <button @click="openEditModal(parceltype)" class="text-blue-500 mr-2">Edit</button>
                            <button @click="openDeleteModal(parceltype)" class="text-red-500">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Add Modal -->
            <Modal :show="showAddModal" title="Add Parcel Type" @close="showAddModal = false">
                <form @submit.prevent="submitForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Round</label>
                        <select v-model="form.round_id" class="w-full border rounded p-2 bg-gray-800 text-gray-200"
                            required>
                            <option value="" disabled>Select a round</option>
                            <option v-for="round in rounds" :key="round.id" :value="round.id">
                                {{ round.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Name</label>
                        <select v-model="form.name" class="w-full border rounded p-2 bg-gray-800 text-gray-200"
                            required>
                            <option value="" disabled>Select a name</option>
                            <option v-for="name in parcelTypeNames" :key="name" :value="name">
                                {{ name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Max Weight (kg)</label>
                        <input v-model="form.max_weight" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Max Length (cm)</label>
                        <input v-model="form.max_length" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Rate (£)</label>
                        <input v-model="form.rate" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-gray-100 px-4 py-2 rounded">
                            Save
                        </button>
                    </div>
                </form>
            </Modal>

            <!-- Edit Modal -->
            <Modal :show="showEditModal" title="Edit Parcel Type" @close="showEditModal = false">
                <form @submit.prevent="submitForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Round</label>
                        <select v-model="form.round_id" class="w-full border rounded p-2 bg-gray-800 text-gray-200"
                            required>
                            <option value="" disabled>Select a round</option>
                            <option v-for="round in rounds" :key="round.id" :value="round.id">
                                {{ round.name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Name</label>
                        <select v-model="form.name" class="w-full border rounded p-2 bg-gray-800 text-gray-200"
                            required>
                            <option value="" disabled>Select a name</option>
                            <option v-for="name in parcelTypeNames" :key="name" :value="name">
                                {{ name }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Max Weight (kg)</label>
                        <input v-model="form.max_weight" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Max Length (cm)</label>
                        <input v-model="form.max_length" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-100">Rate (£)</label>
                        <input v-model="form.rate" type="number" step="0.01"
                            class="w-full border rounded p-2 bg-gray-800 text-gray-200" required />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-gray-100 px-4 py-2 rounded">
                            Update
                        </button>
                    </div>
                </form>
            </Modal>

            <!-- Delete Modal -->
            <Modal :show="showDeleteModal" title="Delete Parcel Type" @close="showDeleteModal = false">
                <p class="text-gray-100">Are you sure you want to delete this parcel type?</p>
                <div class="mt-4 flex justify-end">
                    <button @click="deleteType"
                        class="bg-red-600 hover:bg-red-700 text-gray-100 px-4 py-2 rounded mr-2">
                        Delete
                    </button>
                    <button @click="showDeleteModal = false"
                        class="bg-gray-600 hover:bg-gray-700 text-gray-100 px-4 py-2 rounded">
                        Cancel
                    </button>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
