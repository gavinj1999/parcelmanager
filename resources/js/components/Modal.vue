<script setup>
import { ref } from 'vue';

const props = defineProps({
  show: Boolean,
  title: String,
});

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-md text-gray-100">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-100">{{ title }}</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-200">&times;</button>
      </div>
      <div>
        <slot />
      </div>
    </div>
  </div>
</template>


