<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="shrink-0 flex items-center">
              <Link :href="route('dashboard')" class="text-xl font-bold">Parcel Management</Link>
            </div>
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <Link :href="route('date-periods.index')" class="border-b-2" :class="{ 'border-blue-500': $page.url.startsWith('/date-periods') }">Date Periods</Link>
              <Link :href="route('rounds.index')" class="border-b-2" :class="{ 'border-blue-500': $page.url.startsWith('/rounds') }">Rounds</Link>
              <Link :href="route('parcel-types.index')" class="border-b-2" :class="{ 'border-blue-500': $page.url.startsWith('/parcel-types') }">Parcel Types</Link>
              <Link :href="route('activities.index')" class="border-b-2" :class="{ 'border-blue-500': $page.url.startsWith('/activities') }">Activities</Link>
              <Link :href="route('reports.index')" class="border-b-2" :class="{ 'border-blue-500': $page.url.startsWith('/reports') }">Reports</Link>
            </div>
          </div>
          <div class="hidden sm:flex sm:items-center sm:ms-6">
            <div class="ms-3 relative">
              <dropdown align="right" width="48">
                <template #trigger>
                  <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none">
                    {{ $page.props.auth.user.name }}
                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </template>
                <template #content>
                  <dropdown-link :href="route('profile.edit')">Profile</dropdown-link>
                  <dropdown-link :href="route('logout')" method="post">Log Out</dropdown-link>
                </template>
              </dropdown>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script lang="ts">
import Dropdown from '@/components/Dropdown.vue';
import DropdownLink from '@/components/DropdownLink.vue';

export default {
  components: {
    Dropdown,
    DropdownLink,
  },
};
</script>
