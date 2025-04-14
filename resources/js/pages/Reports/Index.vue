<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import moment from 'moment';
import { type BreadcrumbItem } from '@/types';
import { Pie, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
  CategoryScale,
  LinearScale,
  LineElement,
  PointElement,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
  Title,
  Tooltip,
  Legend,
  ArcElement,
  BarElement,
  CategoryScale,
  LinearScale,
  LineElement,
  PointElement
);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Reports',
    href: '/reports',
  },
];

const props = defineProps<{
  activities: Array<{
    id: number;
    activity_date: string;
    quantity: number;
    parcel_type: {
      id: number;
      name: string;
      rate: number;
      round_id: number;
      round: { id: number; name: string } | null;
    } | null;
    images?: Array<{ id: number; image_path: string }>;
  }>;
  rounds: Array<{ id: number; name: string }>;
  datePeriods: Array<{ id: number; name?: string; start_date: string; end_date: string }>;
}>();

// Debug: Log props
onMounted(() => {
  console.log('All props on mount:', props);
  console.log('datePeriods on mount:', props.datePeriods);
  console.log('Activities:', props.activities);
  console.log('Rounds:', props.rounds);
});

// Period filter
const safeDatePeriods = computed(() => {
  if (!props.datePeriods || !Array.isArray(props.datePeriods)) return [];
  return props.datePeriods.filter(
    period => period && typeof period === 'object' && 'id' in period
  );
});

const today = moment('2025-04-14');
const defaultPeriod = computed(() => {
  if (!safeDatePeriods.value.length) return null;
  return (
    safeDatePeriods.value.find(period =>
      today.isBetween(
        moment(period.start_date),
        moment(period.end_date),
        undefined,
        '[]'
      )
    ) || null
  );
});

const selectedPeriodIds = ref<string[]>(
  defaultPeriod.value ? [String(defaultPeriod.value.id)] : []
);

const showPeriodDropdown = ref(false);

const handleAllPeriodsChange = () => {
  if (selectedPeriodIds.value.includes('all')) {
    selectedPeriodIds.value = ['all'];
  } else if (
    safeDatePeriods.value.length &&
    selectedPeriodIds.value.length === safeDatePeriods.value.length
  ) {
    selectedPeriodIds.value = ['all'];
  } else {
    selectedPeriodIds.value = [];
  }
};

const handlePeriodChange = () => {
  if (selectedPeriodIds.value.includes('all')) {
    selectedPeriodIds.value = selectedPeriodIds.value.filter(
      id => id !== 'all'
    );
  }
  if (
    safeDatePeriods.value.length &&
    selectedPeriodIds.value.length === safeDatePeriods.value.length
  ) {
    selectedPeriodIds.value = ['all'];
  }
};

const filteredActivities = computed(() => {
  const includeAll =
    selectedPeriodIds.value.length === 0 ||
    selectedPeriodIds.value.includes('all');
  const periods = includeAll
    ? safeDatePeriods.value
    : safeDatePeriods.value.filter(period =>
        selectedPeriodIds.value.includes(String(period.id))
      );

  return (props.activities || []).filter(activity => {
    const activityDate = moment(activity.activity_date);
    const activityPeriod = safeDatePeriods.value.find(period =>
      activityDate.isBetween(
        moment(period.start_date),
        moment(period.end_date),
        undefined,
        '[]'
      )
    );

    return (
      includeAll ||
      (activityPeriod && periods.some(p => p.id === activityPeriod.id))
    );
  });
});

// Monetary sum by round
const monetarySumByRound = computed(() => {
  const roundSums: { [key: number]: { roundName: string; totalValue: number } } = {};
  let overallTotal = 0;

  filteredActivities.value.forEach(activity => {
    const roundId = activity.parcel_type?.round_id ?? 0;
    const round = activity.parcel_type?.round || props.rounds.find(r => r.id === roundId);
    const roundName = round ? round.name : `Unknown Round (ID: ${roundId})`;

    const value = (activity.parcel_type?.rate ?? 0) * (activity.quantity || 0);

    if (!roundSums[roundId]) {
      roundSums[roundId] = { roundName, totalValue: 0 };
    }

    roundSums[roundId].totalValue += value;
    overallTotal += value;
  });

  const result = Object.values(roundSums).map(item => ({
    ...item,
    totalValue: `£${item.totalValue.toFixed(2)}`,
  }));

  return {
    rounds: result,
    overallTotal: `£${overallTotal.toFixed(2)}`,
  };
});

// Pie chart
const pieChartData = computed(() => {
  const parcelTypeQuantities: { [key: string]: number } = {};

  filteredActivities.value.forEach(activity => {
    const parcelTypeName = activity.parcel_type?.name || 'Unknown';
    const quantity = activity.quantity || 0;

    if (!parcelTypeQuantities[parcelTypeName]) {
      parcelTypeQuantities[parcelTypeName] = 0;
    }
    parcelTypeQuantities[parcelTypeName] += quantity;
  });

  const labels = Object.keys(parcelTypeQuantities);
  const data = Object.values(parcelTypeQuantities);

  return {
    labels,
    datasets: [
      {
        label: 'Activity by Parcel Type',
        data,
        backgroundColor: [
          '#FF6384',
          '#36A2EB',
          '#FFCE56',
          '#4BC0C0',
          '#9966FF',
          '#FF9F40',
          '#C9CB3F',
          '#66BB6A',
        ],
      },
    ],
  };
});

const pieChartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        color: '#D1D5DB',
      },
    },
    title: {
      display: true,
      text: 'Monthly Breakdown of Activity by Parcel Type',
      color: '#D1D5DB',
    },
  },
};

// Bar chart
const showTrendLine = ref(true);

const barChartData = computed(() => {
  const currentDate = moment('2025-04-14');
  const sortedPeriods = [...safeDatePeriods.value].sort((a, b) =>
    moment(a.start_date).diff(moment(b.start_date))
  );

  const periodValues: { [key: string]: number } = {};
  const periodData: { name: string; value: number; endDate: moment.Moment }[] = [];

  sortedPeriods.forEach(period => {
    const activitiesInPeriod = (props.activities || []).filter(activity => {
      const activityDate = moment(activity.activity_date);
      return activityDate.isBetween(
        moment(period.start_date),
        moment(period.end_date),
        undefined,
        '[]'
      );
    });

    const totalValue = activitiesInPeriod.reduce((sum, activity) => {
      if (!activity.parcel_type || !activity.parcel_type.rate) return sum;
      return sum + activity.quantity * activity.parcel_type.rate;
    }, 0);

    const periodEndDate = moment(period.end_date);
    const periodName =
      period.name ||
      `${moment(period.start_date).format('DD/MM/YYYY')} - ${moment(
        period.end_date
      ).format('DD/MM/YYYY')}`;

    const isFuture = periodEndDate.isAfter(currentDate, 'day');
    if (!isFuture || totalValue > 0) {
      periodValues[periodName] = totalValue;
      periodData.push({ name: periodName, value: totalValue, endDate: periodEndDate });
    }
  });

  const labels = periodData.map(p => p.name);
  const data = periodData.map(p => p.value);

  const trendData = [];
  if (data.length > 1) {
    const n = data.length;
    let sumX = 0,
      sumY = 0,
      sumXY = 0,
      sumXX = 0;
    for (let i = 0; i < n; i++) {
      sumX += i;
      sumY += data[i];
      sumXY += i * data[i];
      sumXX += i * i;
    }
    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    for (let i = 0; i < n; i++) {
      trendData.push(intercept + slope * i);
    }
  } else {
    trendData.push(...data);
  }

  return {
    labels,
    datasets: [
      {
        type: 'bar',
        label: 'Monetary Value (£)',
        data,
        backgroundColor: '#36A2EB',
      },
      {
        type: 'line',
        label: 'Trend Line',
        data: showTrendLine.value ? trendData : [],
        borderColor: '#FF6384',
        borderWidth: 2,
        fill: false,
        tension: 0.1,
        hidden: !showTrendLine.value,
      },
    ],
  };
});

const barChartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        color: '#D1D5DB',
      },
    },
    title: {
      display: true,
      text: 'Monetary Value by Date Period',
      color: '#D1D5DB',
    },
  },
  scales: {
    x: {
      ticks: {
        color: '#D1D5DB',
      },
      grid: {
        color: '#4B5563',
      },
    },
    y: {
      ticks: {
        color: '#D1D5DB',
      },
      grid: {
        color: '#4B5563',
      },
      beginAtZero: true,
    },
  },
};

// Activity Summary
const activitySummary = computed(() => {
  const summary: { [key: string]: { date: string; count: number; images: string[] } } = {};

  filteredActivities.value.forEach(activity => {
    const date = moment(activity.activity_date).format('YYYY-MM-DD');
    if (!summary[date]) {
      summary[date] = {
        date,
        count: 0,
        images: activity.images?.map(img => `/storage/${img.image_path}`) || [],
      };
    }
    summary[date].count += activity.quantity || 0;
  });

  return Object.values(summary).sort((a, b) => moment(b.date).diff(moment(a.date)));
});

// Image Modal
const selectedImage = ref<string | null>(null);

const showImage = (imageUrl: string) => {
  selectedImage.value = imageUrl;
};

const closeModal = () => {
  selectedImage.value = null;
};
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 mx-auto" style="max-width: 1600px;">
      <h1 class="text-2xl font-bold mb-6 text-gray-100">Reports</h1>

      <!-- Date Period Filter -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-100">Filter by Date Periods</h2>
        <div class="relative">
          <button
            @click="showPeriodDropdown = !showPeriodDropdown"
            class="border rounded p-2 bg-gray-900 text-gray-200 focus:ring-2 focus:ring-blue-500 w-64 text-left"
            :disabled="!safeDatePeriods.length"
          >
            {{
              selectedPeriodIds.length === 0 || selectedPeriodIds.includes('all')
                ? 'All Periods'
                : `${selectedPeriodIds.length} Period(s) Selected`
            }}
            <span class="absolute right-2 top-1/2 transform -translate-y-1/2"
              >▼</span
            >
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
            <label
              v-for="period in safeDatePeriods"
              :key="period.id"
              class="block p-2 hover:bg-gray-700"
            >
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
          <div
            v-if="showPeriodDropdown && !safeDatePeriods.length"
            class="absolute z-10 mt-1 w-64 bg-gray-800 border rounded shadow-lg p-2 text-gray-400"
          >
            No periods available
          </div>
        </div>
      </div>

      <!-- Monetary Sum by Round -->
      <h2 class="text-lg font-semibold text-gray-100 mb-4">
        Monetary Sum by Round
      </h2>
      <div class="overflow-x-auto mb-8">
        <table class="w-full border bg-gray-900 rounded-lg">
          <thead>
            <tr class="bg-gray-800 text-gray-100">
              <th class="p-3 text-left">Round</th>
              <th class="p-3 text-left">Total Value (£)</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="round in monetarySumByRound.rounds"
              :key="round.roundName"
              class="border-t hover:bg-gray-800"
            >
              <td class="p-3">{{ round.roundName }}</td>
              <td class="p-3">{{ round.totalValue }}</td>
            </tr>
            <tr v-if="!monetarySumByRound.rounds.length" class="border-t">
              <td colspan="2" class="p-3 text-gray-400 text-center">
                No data available for selected periods
              </td>
            </tr>
            <tr v-else class="border-t font-bold">
              <td class="p-3">Total</td>
              <td class="p-3">{{ monetarySumByRound.overallTotal }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Activity Summary -->
      <h2 class="text-lg font-semibold text-gray-100 mb-4">
        Activity Summary
      </h2>
      <div class="overflow-x-auto mb-8">
        <table class="w-full border bg-gray-900 rounded-lg">
          <thead>
            <tr class="bg-gray-800 text-gray-100">
              <th class="p-3 text-left">Date</th>
              <th class="p-3 text-left">Total Quantity</th>
              <th class="p-3 text-left">Images</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="summary in activitySummary"
              :key="summary.date"
              class="border-t hover:bg-gray-800"
            >
              <td class="p-3">
                <button
                  class="text-blue-400 hover:underline"
                  @click="summary.images.length ? showImage(summary.images[0]) : null"
                  :disabled="!summary.images.length"
                >
                  {{ summary.date }}
                </button>
              </td>
              <td class="p-3">{{ summary.count }}</td>
              <td class="p-3">
                {{ summary.images.length ? `${summary.images.length} image(s)` : 'None' }}
              </td>
            </tr>
            <tr v-if="!activitySummary.length" class="border-t">
              <td colspan="3" class="p-3 text-gray-400 text-center">
                No activities available
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pie Chart -->
      <h2 class="text-lg font-semibold text-gray-100 mb-4">
        Monthly Breakdown of Activity by Parcel Type
      </h2>
      <div class="mb-8 bg-gray-900 p-6 rounded-lg">
        <Pie
          :data="pieChartData"
          :options="pieChartOptions"
          class="max-h-96"
        />
      </div>

      <!-- Bar Chart -->
      <h2 class="text-lg font-semibold text-gray-100 mb-4">
        Monetary Value by Date Period
      </h2>
      <div class="mb-8 bg-gray-900 p-6 rounded-lg">
        <div class="mb-4">
          <label class="text-gray-200">
            <input
              type="checkbox"
              v-model="showTrendLine"
              class="mr-2"
            />
            Show Trend Line
          </label>
        </div>
        <Bar
          :data="barChartData"
          :options="barChartOptions"
          class="max-h-96"
        />
      </div>

      <!-- Image Modal -->
      <div
        v-if="selectedImage"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
        @click="closeModal"
      >
        <div class="relative" @click.stop>
          <img
            :src="selectedImage"
            alt="Activity Image"
            class="max-w-full max-h-[80vh] rounded-lg"
          />
          <button
            class="absolute top-2 right-2 bg-gray-800 text-white rounded-full p-2"
            @click="closeModal"
          >
            ✕
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
canvas {
  max-width: 100%;
}
</style>
