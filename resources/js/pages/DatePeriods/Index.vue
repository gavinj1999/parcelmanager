<script setup lang="ts">
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
const props = defineProps({
  datePeriods: Array,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({
  id: null,
  name: '',
  start_date: '',
  end_date: '',
});

const resetForm = () => {
  form.value = { id: null, name: '', start_date: '', end_date: '' };
};

const openAddModal = () => {
  resetForm();
  showAddModal.value = true;
};

const openEditModal = (period) => {
  form.value = { ...period };
  showEditModal.value = true;
};

const openDeleteModal = (period) => {
  form.value = { id: period.id };
  showDeleteModal.value = true;
};

const submitForm = () => {
  if (form.value.id) {
    router.put(`/date-periods/${form.value.id}`, form.value, {
      onSuccess: () => {
        showEditModal.value = false;
        resetForm();
      },
    });
  } else {
    router.post('/date-periods', form.value, {
      onSuccess: () => {
        showAddModal.value = false;
        resetForm();
      },
    });
  }
};

const deletePeriod = () => {
  router.delete(`/date-periods/${form.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
      resetForm();
    },
  });
};
</script>

<template>
    <Head title="Date Periods" />
    <AppLayout :breadcrumbs="breadcrumbs">
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Date Periods</h1>
    <button @click="openAddModal" class="bg-blue-500 text-white px-4 py-2 rounded">Add Period</button>
    <table class="w-full mt-4 border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2">Name</th>
          <th class="p-2">Start Date</th>
          <th class="p-2">End Date</th>
          <th class="p-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="period in datePeriods" :key="period.id" class="border-t">
          <td class="p-2">{{ period.name }}</td>
          <td class="p-2">{{ period.start_date }}</td>
          <td class="p-2">{{ period.end_date }}</td>
          <td class="p-2">
            <button @click="openEditModal(period)" class="text-blue-500 mr-2">Edit</button>
            <button @click="openDeleteModal(period)" class="text-red-500">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Add Modal -->
    <Modal :show="showAddModal" title="Add Date Period" @close="showAddModal = false">
      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Start Date</label>
          <input v-model="form.start_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">End Date</label>
          <input v-model="form.end_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
        </div>
      </form>
    </Modal>

    <!-- Edit Modal -->
    <Modal :show="showEditModal" title="Edit Date Period" @close="showEditModal = false">
      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Start Date</label>
          <input v-model="form.start_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">End Date</label>
          <input v-model="form.end_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
        </div>
      </form>
    </Modal>

    <!-- Delete Modal -->
    <Modal :show="showDeleteModal" title="Delete Date Period" @close="showDeleteModal = false">
      <p>Are you sure you want to delete this period?</p>
      <div class="mt-4 flex justify-end">
        <button @click="deletePeriod" class="bg-red-500 text-white px-4 py-2 rounded mr-2">Delete</button>
        <button @click="showDeleteModal = false" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
      </div>
    </Modal>
  </div>
</AppLayout>
</template>
