<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import moment from 'moment';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Activity',
    href: '/activities',
  },
];

const props = defineProps({
  activities: { type: Object, default: () => ({ data: [] }) },
  parcelTypes: { type: Array, default: () => [] },
  rounds: { type: Array, default: () => [] },
  datePeriods: { type: Array, default: () => [] },
  date_periods: { type: Array, default: () => [] },
});

// Debug: Log all props to verify what's being passed
onMounted(() => {
  console.log('All props on mount:', props);
  console.log('datePeriods on mount:', props.datePeriods);
  console.log('date_periods on mount:', props.date_periods);
  console.log('Activities:', props.activities);
  console.log('Rounds:', props.rounds);
});

// Loading state to ensure props are ready
const isLoading = ref(true);
onMounted(() => {
  if (props.datePeriods?.length || props.date_periods?.length) {
    isLoading.value = false;
  } else {
    const unwatch = watch(
      () => [props.datePeriods, props.date_periods],
      ([newDatePeriods, newDatePeriodsSnake]) => {
        if (newDatePeriods?.length || newDatePeriodsSnake?.length) {
          isLoading.value = false;
          unwatch();
        }
      }
    );
    setTimeout(() => {
      isLoading.value = false;
    }, 2000);
  }
});

// Access flash messages from Inertia (server-side)
const flash = computed(() => usePage().props.flash);
const showFlash = ref(false);

// Local flash message state (client-side)
const localFlash = ref(null);
const showLocalFlash = ref(false);

// Default form state with dynamic today's date
const defaultForm = ref({
  activity_date: moment().format('YYYY-MM-DD'),
  round_id: null,
  quantities: [],
});

// HTML parsing state
const htmlInput = ref('');
const showUnknownModal = ref(false);
const unknownParcelTypes = ref([]);
const parsedQuantities = ref({});

// Loading states
const isParsing = ref(false);
const isSubmitting = ref(false);

// Initialize quantities when a round is selected
const selectedRound = computed(() => {
  return props.rounds.find(round => round.id === Number(defaultForm.value.round_id)) || null;
});

const updateQuantities = () => {
  if (selectedRound.value) {
    defaultForm.value.quantities = selectedRound.value.parcel_types.map(parcelType => ({
      parcel_type_id: parcelType.id,
      quantity: 0,
    }));
  } else {
    defaultForm.value.quantities = [];
  }
};

function activity_date(info) {
  return moment(info).format('DD/MM/YYYY');
}

// Watch for changes in round_id to update quantities
watch(() => defaultForm.value.round_id, () => {
  updateQuantities();
});

// Watch for server-side flash messages
watch(() => flash.value, (newFlash) => {
  if (newFlash?.success) {
    showFlash.value = true;
    setTimeout(() => {
      showFlash.value = false;
    }, 5000);
  }
});

// Watch for local flash messages
watch(localFlash, (newFlash) => {
  if (newFlash) {
    showLocalFlash.value = true;
    setTimeout(() => {
      showLocalFlash.value = false;
      localFlash.value = null;
    }, 5000);
  }
});

// Parse HTML and check for unknown parcel types
const parseHtml = async () => {
  if (isParsing.value) return;
  isParsing.value = true;

  try {
    if (!defaultForm.value.round_id) {
      localFlash.value = { error: 'Please select a round before parsing HTML' };
      return;
    }

    if (!selectedRound.value || !htmlInput.value) {
      return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlInput.value, 'text/html');
    const rows = doc.querySelectorAll('table.manifest-summary-inner tbody tr');

    const nameMapping = {
      'Parcels': 'Parcel',
      'Heavy/Large': 'Heavy / Large',
      'Manifested collections': 'Manifested Collections',
    };

    const extractedData = {};
    const unknownTypes = [];
    rows.forEach(row => {
      const type = row.querySelector('td:nth-child(1)')?.textContent.trim();
      const manifested = parseInt(row.querySelector('td:nth-child(2)')?.textContent.trim(), 10);

      if (!type || ['TOTAL', 'Next day', 'POD-Signature'].includes(type) || isNaN(manifested)) return;

      const dbName = nameMapping[type] || type;
      extractedData[dbName] = manifested;

      const exists = selectedRound.value.parcel_types.some(pt => pt.name === dbName);
      if (!exists) {
        unknownTypes.push({ name: dbName, quantity: manifested, max_weight: 0, max_length: 0, rate: 0 });
      }
    });

    if (unknownTypes.length > 0) {
      unknownParcelTypes.value = unknownTypes;
      parsedQuantities.value = extractedData;
      showUnknownModal.value = true;
    } else {
      updateQuantitiesWithParsedData(extractedData);
      submitDefaultForm();
    }
  } finally {
    isParsing.value = false;
  }
};

// Update quantities after parsing
const updateQuantitiesWithParsedData = (extractedData) => {
  defaultForm.value.quantities = defaultForm.value.quantities.map(quantity => {
    const parcelType = selectedRound.value.parcel_types.find(pt => pt.id === quantity.parcel_type_id);
    const quantityValue = extractedData[parcelType.name] || 0;
    return {
      parcel_type_id: quantity.parcel_type_id,
      quantity: quantityValue,
    };
  });
  htmlInput.value = '';
};

// Handle unknown parcel types
const createUnknownParcelTypes = () => {
  router.post('/parcel-types/bulk', {
    round_id: defaultForm.value.round_id,
    parcel_types: unknownParcelTypes.value,
  }, {
    onSuccess: () => {
      router.reload({ only: ['rounds'], onSuccess: () => {
        updateQuantitiesWithParsedData(parsedQuantities.value);
        showUnknownModal.value = false;
        unknownParcelTypes.value = [];
        parsedQuantities.value = {};
        submitDefaultForm();
      }});
    },
  });
};

const ignoreUnknownParcelTypes = () => {
  const filteredData = { ...parsedQuantities.value };
  unknownParcelTypes.value.forEach(type => {
    delete filteredData[type.name];
  });
  updateQuantitiesWithParsedData(filteredData);
  showUnknownModal.value = false;
  unknownParcelTypes.value = [];
  parsedQuantities.value = {};
  submitDefaultForm();
};

// Modal form state (for add/edit/delete)
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({
  id: null,
  parcel_type_id: null,
  activity_date: '',
  quantity: '',
});

// State for Activity Details Modal
const showDetailsModal = ref(false);
const selectedDate = ref<string | null>(null);
const selectedRoundId = ref<number | null>(null);
const activitiesForDateAndRound = ref([]);

const resetForm = () => {
  form.value = { id: null, parcel_type_id: null, activity_date: '', quantity: '' };
};

const openAddModal = () => {
  resetForm();
  showAddModal.value = true;
};

const openEditModal = (activity) => {
  form.value = {
    id: activity.id,
    parcel_type_id: activity.parcel_type?.id ?? null,
    activity_date: activity.activity_date,
    quantity: activity.quantity,
  };
  showEditModal.value = true;
};

const openDeleteModal = (activity) => {
  form.value = { id: activity.id };
  showDeleteModal.value = true;
};

const openDetailsModal = async (date: string, roundId: number) => {
  selectedDate.value = date;
  selectedRoundId.value = roundId;
  try {
    const response = await fetch(`/activities/details?date=${date}&round_id=${roundId}`);
    const data = await response.json();
    activitiesForDateAndRound.value = Array.isArray(data) ? data : [];
    showDetailsModal.value = true;
  } catch (error) {
    console.error('Error fetching activities for modal:', error);
    activitiesForDateAndRound.value = [];
    showDetailsModal.value = true;
  }
};

const submitForm = () => {
  if (form.value.id) {
    router.put(`/activities/${form.value.id}`, form.value, {
      onSuccess: () => {
        showEditModal.value = false;
        resetForm();
      },
    });
  } else {
    router.post('/activities', form.value, {
      onSuccess: () => {
        showAddModal.value = false;
        resetForm();
      },
    });
  }
};

const deleteActivity = () => {
  router.delete(`/activities/${form.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false;
      resetForm();
    },
  });
};

// Submit the default form
const submitDefaultForm = async () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;

  try {
    const payload = {
      activity_date: defaultForm.value.activity_date,
      round_id: defaultForm.value.round_id,
      quantities: defaultForm.value.quantities,
    };
    router.post('/activities/bulk', payload, {
      onSuccess: () => {
        updateQuantities();
        router.reload({ preserveState: true, preserveScroll: true });
      },
    });
  } finally {
    isSubmitting.value = false;
  }
};

// Calculate total value for an activity
const calculateTotalValue = (activity) => {
  if (!activity.parcel_type || !activity.parcel_type.rate) return 0;
  return activity.quantity * activity.parcel_type.rate;
};

// Period filter for Activity Summary (checkbox dropdown)
const safeDatePeriods = computed(() => {
  const periods = props.datePeriods?.length ? props.datePeriods : props.date_periods;
  if (!periods || !Array.isArray(periods)) return [];
  return periods.filter(period => period && typeof period === 'object' && 'id' in period);
});

// Find the default period containing today's date (2025-04-13)
const today = moment('2025-04-13');
const defaultPeriod = computed(() => {
  if (!safeDatePeriods.value.length) return null;
  return safeDatePeriods.value.find(period =>
    today.isBetween(moment(period.start_date), moment(period.end_date), undefined, '[]')
  ) || null;
});

const selectedPeriodIds = ref<string[]>(defaultPeriod.value ? [String(defaultPeriod.value.id)] : []);

// Checkbox dropdown state
const showPeriodDropdown = ref(false);

// Handle "All Periods" checkbox
const handleAllPeriodsChange = () => {
  if (selectedPeriodIds.value.includes('all')) {
    selectedPeriodIds.value = ['all'];
  } else if (safeDatePeriods.value.length && selectedPeriodIds.value.length === safeDatePeriods.value.length) {
    selectedPeriodIds.value = ['all'];
  } else {
    selectedPeriodIds.value = [];
  }
};

// Handle individual period checkbox
const handlePeriodChange = () => {
  if (selectedPeriodIds.value.includes('all')) {
    selectedPeriodIds.value = selectedPeriodIds.value.filter(id => id !== 'all');
  }
  if (safeDatePeriods.value.length && selectedPeriodIds.value.length === safeDatePeriods.value.length) {
    selectedPeriodIds.value = ['all'];
  }
};

// Filter activities by selected periods and group by date and round
const filteredActivitySummary = computed(() => {
  const includeAll = selectedPeriodIds.value.length === 0 || selectedPeriodIds.value.includes('all');
  const periods = includeAll
    ? safeDatePeriods.value
    : safeDatePeriods.value.filter(period => selectedPeriodIds.value.includes(String(period.id)));

  // Group activities by date and round
  const summaryByDateAndRound: { [key: string]: { date: string, roundId: number, roundName: string, totalQuantity: number, totalValue: number } } = {};

  const activities = props.activities?.data || [];
  activities.forEach(activity => {
    const date = moment(activity.activity_date);
    const dateStr = activity.activity_date;
    const roundId = activity.parcel_type?.round_id ?? 0;
    const round = props.rounds.find(r => r.id === roundId);
    const roundName = round ? round.name : `Unknown Round (ID: ${roundId})`;

    // Find the period this activity belongs to
    const activityPeriod = safeDatePeriods.value.find(period =>
      date.isBetween(moment(period.start_date), moment(period.end_date), undefined, '[]')
    );

    // Skip if the activity's period is not in the selected periods
    if (!includeAll && (!activityPeriod || !periods.some(p => p.id === activityPeriod.id))) {
      return;
    }

    const key = `${dateStr}-${roundId}`;
    if (!summaryByDateAndRound[key]) {
      summaryByDateAndRound[key] = {
        date: dateStr,
        roundId: roundId,
        roundName,
        totalQuantity: 0,
        totalValue: 0,
      };
    }

    summaryByDateAndRound[key].totalQuantity += activity.quantity;
    summaryByDateAndRound[key].totalValue += calculateTotalValue(activity);
  });

  // Convert to array and sort by date (descending)
  return Object.values(summaryByDateAndRound)
    .map(item => ({
      ...item,
      totalValue: `£${item.totalValue.toFixed(2)}`,
    }))
    .sort((a, b) => moment(b.date).diff(moment(a.date)));
});

// Compute monetary value per date period
const sortKey = ref('period');
const sortOrder = ref(1); // 1 for ascending, -1 for descending

// Compute monetary value per date period
const monetaryValuePerPeriod = computed(() => {
  const periods = safeDatePeriods.value
    .map(period => {
      const activitiesInPeriod = (props.activities?.data || []).filter(activity => {
        const activityDate = moment(activity.activity_date);
        return activityDate.isBetween(moment(period.start_date), moment(period.end_date), undefined, '[]');
      });

      const totalValue = activitiesInPeriod.reduce((sum, activity) => {
        if (!activity.parcel_type || !activity.parcel_type.rate) return sum;
        return sum + (activity.quantity * activity.parcel_type.rate);
      }, 0);

      return {
        period: period.name || `${moment(period.start_date).format('DD/MM/YYYY')} - ${moment(period.end_date).format('DD/MM/YYYY')}`,
        totalValue: `£${totalValue.toFixed(2)}`,
        activitiesInPeriod, // Store activities for filtering
      };
    })
    .filter(period => period.activitiesInPeriod.length > 0); // Only include periods with activities

  return periods;
});

const sortedMonetaryValuePerPeriod = computed(() => {
  return [...monetaryValuePerPeriod.value].sort((a, b) => {
    const aValue = sortKey.value === 'totalValue' ? a[sortKey.value].replace('£', '') : a[sortKey.value];
    const bValue = sortKey.value === 'totalValue' ? b[sortKey.value].replace('£', '') : b[sortKey.value];
    return sortOrder.value * (aValue.localeCompare ? aValue.localeCompare(bValue) : aValue - bValue);
  });
});

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value *= -1;
  } else {
    sortKey.value = key;
    sortOrder.value = 1;
  }
};

// Pagination handler
const changePage = (page) => {
  router.get('/activities', { page }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
  <Head title="Activities" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6" style="max-width: 1600px;">
      <h1 class="text-2xl font-bold mb-6 text-gray-100">Activities</h1>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-gray-400 text-center mb-4">
        Loading periods...
      </div>

      <div v-else>
        <!-- Server-side Flash Message -->
        <div v-if="showFlash && flash?.success" class="mb-6 p-4 bg-green-700 text-white rounded-lg flex items-center animate-fade-in">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <span>{{ flash.success }}</span>
          <button @click="showFlash = false" class="ml-auto text-white hover:text-gray-200">×</button>
        </div>

        <!-- Local Flash Message -->
        <div v-if="showLocalFlash && localFlash?.error" class="mb-6 p-4 bg-red-700 text-white rounded-lg flex items-center animate-fade-in">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ localFlash.error }}</span>
          <button @click="showLocalFlash = false; localFlash = null" class="ml-auto text-white hover:text-gray-200">×</button>
        </div>

        <!-- Side-by-Side Container -->
        <div class="flex flex-wrap -mx-4 mb-8">
          <!-- Paste Manifest HTML -->
          <div class="w-full md:w-1/2 px-4 mb-6 md:mb-0">
            <div class="p-6 bg-gray-800 rounded-xl shadow-lg">
              <h2 class="text-xl font-semibold text-gray-100 mb-4">Paste Manifest HTML</h2>
              <textarea
                v-model="htmlInput"
                placeholder="Paste the HTML table here..."
                class="w-full h-32 border rounded p-2 bg-gray-900 text-gray-200 placeholder-gray-400 mb-4 focus:ring-2 focus:ring-blue-500"
              ></textarea>
              <div class="flex justify-end relative group">
                <button
                  @click="parseHtml"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200 disabled:bg-gray-500 disabled:cursor-not-allowed"
                  :disabled="!htmlInput || !defaultForm.round_id || isParsing"
                >
                  {{ isParsing ? 'Parsing...' : 'Parse HTML' }}
                </button>
                <span
                  v-if="!htmlInput || !defaultForm.round_id"
                  class="absolute bottom-full mb-2 hidden group-hover:block px-2 py-1 text-sm text-gray-100 bg-gray-700 rounded"
                >
                  Select a round and enter HTML to enable
                </span>
              </div>
            </div>
          </div>

          <!-- Record Daily Activities -->
          <div class="w-full md:w-1/2 px-4">
            <div class="p-6 bg-gray-800 rounded-xl shadow-lg">
              <h2 class="text-xl font-semibold text-gray-100 mb-4">Record Daily Activities</h2>
              <div v-if="!props.rounds.length" class="mb-4 p-4 bg-gray-800 text-gray-100 rounded-lg">
                No rounds available. Please create a round first.
              </div>
              <form v-else @submit.prevent="submitDefaultForm">
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-100 mb-1">Date</label>
                  <input
                    v-model="defaultForm.activity_date"
                    type="date"
                    class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-medium text-gray-100 mb-1">Round</label>
                  <select
                    v-model="defaultForm.round_id"
                    class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="" disabled>Select a round</option>
                    <option v-for="round in rounds" :key="round.id" :value="round.id">
                      {{ round.name }}
                    </option>
                  </select>
                </div>
                <div v-if="selectedRound" class="mb-4">
                  <h3 class="text-sm font-medium text-gray-100 mb-2">Parcel Types (Round {{ selectedRound.name }})</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                      v-for="(quantity, index) in defaultForm.quantities"
                      :key="quantity.parcel_type_id"
                      class="flex items-center"
                    >
                      <label class="w-2/3 text-sm text-gray-100 truncate" :title="selectedRound.parcel_types[index].name">
                        {{ selectedRound.parcel_types[index].name }}
                      </label>
                      <input
                        v-model.number="defaultForm.quantities[index].quantity"
                        type="number"
                        min="0"
                        class="w-1/3 border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                        placeholder="0"
                      />
                    </div>
                  </div>
                </div>
                <div class="flex justify-end">
                  <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200 disabled:bg-gray-500 disabled:cursor-not-allowed"
                    :disabled="!defaultForm.round_id || isSubmitting"
                  >
                    {{ isSubmitting ? 'Saving...' : 'Record Activities' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Monetary Value per Date Period Table -->
        <h2 class="text-lg font-semibold text-gray-100 mb-4">Monetary Value per Date Period</h2>
        <div class="overflow-x-auto mb-8">
          <table class="w-full border bg-gray-900 rounded-lg">
            <thead>
              <tr class="bg-gray-800 text-gray-100">
                <th class="p-3 text-left cursor-pointer hover:bg-gray-700" @click="sortBy('period')">Date Period</th>
                <th class="p-3 text-left cursor-pointer hover:bg-gray-700" @click="sortBy('totalValue')">Total Value (£)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in sortedMonetaryValuePerPeriod" :key="period.period" class="border-t hover:bg-gray-800">
                <td class="p-3">{{ period.period }}</td>
                <td class="p-3">{{ period.totalValue }}</td>
              </tr>
              <tr v-if="!monetaryValuePerPeriod.length" class="border-t">
                <td colspan="2" class="p-3 text-gray-400 text-center">No data available</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Activity Summary Table with Checkbox Period Filter -->
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-100">Activity Summary</h2>
          <div class="flex items-center space-x-3">
            <div class="relative">
              <button
                @click="showPeriodDropdown = !showPeriodDropdown"
                class="border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500 w-64 text-left"
                :disabled="!safeDatePeriods.length"
              >
                {{ selectedPeriodIds.length === 0 || selectedPeriodIds.includes('all') ? 'All Periods' : `${selectedPeriodIds.length} Period(s) Selected` }}
                <span class="absolute right-2 top-1/2 transform -translate-y-1/2">▼</span>
              </button>
              <div
                v-if="showPeriodDropdown && safeDatePeriods.length"
                class="absolute z-10 mt-1 w-64 bg-gray-800 border rounded shadow-lg max-h-60 overflow-y-auto"
              >
                <label class="block p-2 hover:bg-gray-700">
                  <input
                    type="checkbox"
                    value="all"
                    v-model="selectedPeriodIds"
                    @change="handleAllPeriodsChange"
                    class="mr-2"
                  />
                  All Periods
                </label>
                <label v-for="period in safeDatePeriods" :key="period.id" class="block p-2 hover:bg-gray-700">
                  <input
                    type="checkbox"
                    :value="String(period.id)"
                    v-model="selectedPeriodIds"
                    @change="handlePeriodChange"
                    class="mr-2"
                  />
                  {{ period.name || 'Unknown Period' }}
                </label>
              </div>
              <div v-if="showPeriodDropdown && !safeDatePeriods.length" class="absolute z-10 mt-1 w-64 bg-gray-800 border rounded shadow-lg p-2 text-gray-400">
                No periods available
              </div>
            </div>
            <button @click="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200">
              Add Activity
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full border bg-gray-900 rounded-lg">
            <thead>
              <tr class="bg-gray-800 text-gray-100">
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Round</th>
                <th class="p-3 text-left">Total Quantity</th>
                <th class="p-3 text-left">Total Value (£)</th>
                <th class="p-3 text-left">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="summary in filteredActivitySummary" :key="`${summary.date}-${summary.roundId}`" class="border-t hover:bg-gray-800">
                <td class="p-3 cursor-pointer text-blue-400 hover:underline" @click="openDetailsModal(summary.date, summary.roundId)">
                  {{ activity_date(summary.date) }}
                </td>
                <td class="p-3">{{ summary.roundName }}</td>
                <td class="p-3">{{ summary.totalQuantity }}</td>
                <td class="p-3">{{ summary.totalValue }}</td>
                <td class="p-3">
                  <!-- Placeholder for future actions -->
                </td>
              </tr>
              <tr v-if="!filteredActivitySummary.length" class="border-t">
                <td colspan="5" class="p-3 text-gray-400 text-center">No activities for selected periods</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div class="mt-4 flex justify-center">
          <nav class="flex space-x-2">
            <button
              v-for="link in props.activities?.links || []"
              :key="link.label"
              @click="changePage(link.label)"
              class="px-3 py-1 rounded"
              :class="link.active ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-200'"
              :disabled="!link.url"
              v-html="link.label"
            ></button>
          </nav>
        </div>

        <!-- Modal for Unknown Parcel Types -->
        <Modal :show="showUnknownModal" title="Unknown Parcel Types Detected" @close="showUnknownModal = false">
          <div class="p-6">
            <p class="text-gray-100 mb-6">The following parcel types were found in the HTML but do not exist in the selected round. Please choose to create them or ignore them.</p>
            <div v-for="(type, index) in unknownParcelTypes" :key="type.name" class="mb-6 p-4 bg-gray-800 rounded-lg">
              <h4 class="text-gray-100 font-semibold mb-2">{{ type.name }} (Quantity: {{ type.quantity }})</h4>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-100 mb-1">Max Weight (kg)</label>
                  <input
                    v-model.number="unknownParcelTypes[index].max_weight"
                    type="number"
                    step="0.01"
                    class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-100 mb-1">Max Length (cm)</label>
                  <input
                    v-model.number="unknownParcelTypes[index].max_length"
                    type="number"
                    step="0.01"
                    class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-100 mb-1">Rate (£)</label>
                  <input
                    v-model.number="unknownParcelTypes[index].rate"
                    type="number"
                    step="0.01"
                    class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
              </div>
            </div>
            <div class="flex justify-end space-x-3">
              <button
                @click="showUnknownModal = false"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Cancel
              </button>
              <button
                @click="ignoreUnknownParcelTypes"
                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Ignore
              </button>
              <button
                @click="createUnknownParcelTypes"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Create
              </button>
            </div>
          </div>
        </Modal>

        <!-- Add Modal -->
        <Modal :show="showAddModal" title="Add Activity" @close="showAddModal = false">
          <div class="p-6">
            <form @submit.prevent="submitForm">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Parcel Type</label>
                <select
                  v-model="form.parcel_type_id"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="" disabled>Select a parcel type</option>
                  <option v-for="type in parcelTypes" :key="type.id" :value="type.id">
                    {{ type.name }} ({{ type.round?.name ?? 'No Round' }})
                  </option>
                </select>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Date</label>
                <input
                  v-model="form.activity_date"
                  type="date"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Quantity</label>
                <input
                  v-model="form.quantity"
                  type="number"
                  min="0"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  @click="showAddModal = false"
                  class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition duration-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" title="Edit Activity" @close="showEditModal = false">
          <div class="p-6">
            <form @submit.prevent="submitForm">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Parcel Type</label>
                <select
                  v-model="form.parcel_type_id"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="" disabled>Select a parcel type</option>
                  <option v-for="type in parcelTypes" :key="type.id" :value="type.id">
                    {{ type.name }} ({{ type.round?.name ?? 'No Round' }})
                  </option>
                </select>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Date</label>
                <input
                  v-model="form.activity_date"
                  type="date"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-100 mb-1">Quantity</label>
                <input
                  v-model="form.quantity"
                  type="number"
                  min="0"
                  class="w-full border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  @click="showEditModal = false"
                  class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition duration-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition duration-200"
                >
                  Update
                </button>
              </div>
            </form>
          </div>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="showDeleteModal" title="Delete Activity" @close="showDeleteModal = false">
          <div class="p-6">
            <p class="text-gray-100 mb-6">Are you sure you want to delete this activity?</p>
            <div class="flex justify-end space-x-3">
              <button
                @click="showDeleteModal = false"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Cancel
              </button>
              <button
                @click="deleteActivity"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Delete
              </button>
            </div>
          </div>
        </Modal>

        <!-- Activity Details Modal -->
        <Modal :show="showDetailsModal" :title="`Activities for ${selectedDate ? activity_date(selectedDate.value) : ''}`" @close="showDetailsModal = false">
          <div class="p-6">
            <div v-if="activitiesForDateAndRound.length">
              <div class="overflow-x-auto">
                <table class="w-full border bg-gray-900 rounded-lg">
                  <thead>
                    <tr class="bg-gray-800 text-gray-100">
                      <th class="p-3 text-left">Parcel Type</th>
                      <th class="p-3 text-left">Quantity</th>
                      <th class="p-3 text-left">Value (£)</th>
                      <th class="p-3 text-left">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="activity in activitiesForDateAndRound" :key="activity.id" class="border-t hover:bg-gray-800">
                      <td class="p-3">{{ activity.parcel_type?.name || 'Unknown' }}</td>
                      <td class="p-3">{{ activity.quantity }}</td>
                      <td class="p-3">{{ `£${calculateTotalValue(activity).toFixed(2)}` }}</td>
                      <td class="p-3 flex space-x-2">
                        <button
                          @click="openEditModal(activity)"
                          class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition duration-200"
                        >
                          Edit
                        </button>
                        <button
                          @click="openDeleteModal(activity)"
                          class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition duration-200"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-gray-400 text-center">
              No activities found for this date and round.
            </div>
            <div class="flex justify-end mt-6">
              <button
                @click="showDetailsModal = false"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition duration-200"
              >
                Close
              </button>
            </div>
          </div>
        </Modal>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-in;
}
</style>
