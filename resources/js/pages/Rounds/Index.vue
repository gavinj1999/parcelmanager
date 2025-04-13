<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import { type BreadcrumbItem } from '@/types';
const props = defineProps({
  rounds: Array,
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({
  id: null,
  name: '',
  description: '',
  active: false,
});

const resetForm = () => {
  form.value = { id: null, name: '', description: '', active: false };
};

const openAddModal = () => {
  resetForm();
  showAddModal.value = true;
};

const openEditModal = (round) => {
  form.value = { ...round };
  showEditModal.value = true;
};

const openDeleteModal = (round) => {
  form.value = { id: round.id };
  showDeleteModal.value = true;
};

const submitForm = () => {
  if (form.value.id) {
    router.put(`/rounds/${form.value.id}`, form.value, {
      onSuccess: () => {
        showEditModal.value = false;
        resetForm();
      },
    });
  } else {
    router.post('/rounds', form.value, {
      onSuccess: () => {
        showAddModal.value = false;
        resetForm();
      },
    });
  }
};

const deleteRound = () => {
  router.delete(`/rounds/${form.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
      resetForm();
    },
  });
};
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rounds',
        href: '/rounds',
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Rounds</h1>
    <button @click="openAddModal" class="bg-blue-500 text-white px-4 py-2 rounded">Add Round</button>
    <table class="w-full mt-4 border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2">Name</th>
          <th class="p-2">Description</th>
          <th class="p-2">Active</th>
          <th class="p-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="round in rounds" :key="round.id" class="border-t">
          <td class="p-2">{{ round.name }}</td>
          <td class="p-2">{{ round.description || '-' }}</td>
          <td class="p-2">{{ round.active ? 'Yes' : 'No' }}</td>
          <td class="p-2">
            <button @click="openEditModal(round)" class="text-blue-500 mr-2">Edit</button>
            <button @click="openDeleteModal(round)" class="text-red-500">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Add Modal -->
    <Modal :show="showAddModal" title="Add Round" @close="showAddModal = false">
      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Description</label>
          <textarea v-model="form.description" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="mb-4">
          <label class="flex items-center">
            <input v-model="form.active" type="checkbox" class="mr-2" />
            <span>Active</span>
          </label>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
        </div>
      </form>
    </Modal>

    <!-- Edit Modal -->
    <Modal :show="showEditModal" title="Edit Round" @close="showEditModal = false">
      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Description</label>
          <textarea v-model="form.description" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="mb-4">
          <label class="flex items-center">
            <input v-model="form.active" type="checkbox" class="mr-2" />
            <span>Active</span>
          </label>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
        </div>
      </form>
    </Modal>

    <!-- Delete Modal -->
    <Modal :show="showDeleteModal" title="Delete Round" @close="showDeleteModal = false">
      <p>Are you sure you want to delete this round?</p>
      <div class="mt-4 flex justify-end">
        <button @click="deleteRound" class="bg-red-500 text-white px-4 py-2 rounded mr-2">Delete</button>
        <button @click="showDeleteModal = false" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
      </div>
    </Modal>
  </div>
</AppLayout>
</template>
