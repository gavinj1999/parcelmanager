<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
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

const props = defineProps({
  activities: { type: Array, default: () => [] },
  rounds: { type: Array, default: () => [] },
  datePeriods: { type: Array, default: () => [] },
});

// Debug: Log all props to verify what's being passed
onMounted(() => {
  console.log('All props on mount:', props);
  console.log('datePeriods on mount:', props.datePeriods);
  console.log('Activities:', props.activities);
  console.log('Rounds:', props.rounds);
});

// Period filter (checkbox dropdown)
const safeDatePeriods = computed(() => {
  if (!props.datePeriods || !Array.isArray(props.datePeriods)) return [];
  return props.datePeriods.filter(
    period => period && typeof period === 'object' && 'id' in period
  );
});

// Find the default period containing the current month (April 2025)
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

// Checkbox dropdown state
const showPeriodDropdown = ref(false);

// Handle "All Periods" checkbox
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

// Handle individual period checkbox
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

// Filter activities by selected periods
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

// Calculate monetary sum by round and total
const monetarySumByRound = computed(() => {
  const roundSums: { [key: number]: { roundName: string; totalValue: number } } =
    {};
  let overallTotal = 0;

  filteredActivities.value.forEach(activity => {
    const roundId = activity.parcel_type?.round_id ?? 0;
    const round = props.rounds.find(r => r.id === roundId);
    const roundName = round ? round.name : `Unknown Round (ID: ${roundId})`;

    const value =
      (activity.parcel_type?.rate ?? 0) * (activity.quantity || 0);

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

// Pie chart: Monthly breakdown of activity by parcel type
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
        color: '#D1D5DB', // Tailwind gray-300
      },
    },
    title: {
      display: true,
      text: 'Monthly Breakdown of Activity by Parcel Type',
      color: '#D1D5DB',
    },
  },
};

// Bar chart: Sum of monetary value by date period with trend line
const showTrendLine = ref(true); // New ref for trend line toggle

const barChartData = computed(() => {
  // Current date for filtering future periods
  const currentDate = moment('2025-04-14');

  // Sort periods by start_date to ensure chronological order
  const sortedPeriods = [...safeDatePeriods.value].sort((a, b) =>
    moment(a.start_date).diff(moment(b.start_date))
  );

  const periodValues: { [key: string]: number } = {};
  const periodData: { name: string; value: number; endDate: moment.Moment }[] =
    [];

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

    // Only include periods before or on current date, or with activity
    const isFuture = periodEndDate.isAfter(currentDate, 'day');
    if (!isFuture || totalValue > 0) {
      periodValues[periodName] = totalValue;
      periodData.push({ name: periodName, value: totalValue, endDate: periodEndDate });
    }
  });

  const labels = periodData.map(p => p.name);
  const data = periodData.map(p => p.value);

  // Calculate trend line (simple linear regression)
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
        hidden: !showTrendLine.value, // Toggle visibility
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
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6" style="max-width: 1600px;">
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

      <!-- Pie Chart: Monthly Breakdown of Activity by Parcel Type -->
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

      <!-- Bar Chart: Monetary Value by Date Period with Trend Line -->
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
    </div>
  </AppLayout>
</template>

<style scoped>
/* Ensure the charts are responsive */
canvas {
  max-width: 100%;
}
</style>
