<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const progress = ref({
  status: 'Not started',
  roundSelected: null,
  manifestCount: 0,
  currentStep: 0,
  totalSteps: 6,
});


const startAutomation = () => {
  router.post('/automate', {}, {
    onSuccess: () => {
      progress.value.status = 'Automation started';
    },
    onError: (errors) => {
      progress.value.status = `Failed to start: ${errors.message || 'Unknown error'}`;
    },
  });
};

// Listen for progress updates via Laravel Echo
if (window.Echo) {
  window.Echo.channel('automation-progress')
    .listen('AutomationProgressUpdated', (e) => {
      progress.value = e;
    });
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4 text-gray-100">Automation Progress</h1>
    <button
      @click="startAutomation"
      class="bg-blue-600 hover:bg-blue-700 text-gray-100 px-4 py-2 rounded mb-4"
      :disabled="progress.status !== 'Not started' && progress.status !== 'Completed' && !progress.status.includes('Failed')"
    >
      Start Automation
    </button>
    <div class="bg-gray-800 p-4 rounded-lg">
      <p><strong>Status:</strong> {{ progress.status }}</p>
      <p><strong>Round Selected:</strong> {{ progress.roundSelected || 'Not yet selected' }}</p>
      <p><strong>Number of Manifests:</strong> {{ progress.manifestCount }}</p>
      <p><strong>Progress:</strong> {{ progress.currentStep }} / {{ progress.totalSteps }} steps completed</p>
      <div class="w-full bg-gray-700 rounded-full h-4 mt-2">
        <div
          class="bg-blue-600 h-4 rounded-full"
          :style="{ width: `${(progress.currentStep / progress.totalSteps) * 100}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>
