<script setup>
import { ref } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
  reportData: Array,
  datePeriods: Array,
});

const chartData = ref({
  labels: props.datePeriods.map(p => p.name),
  datasets: [],
});

const chartOptions = ref({
  responsive: true,
  plugins: {
    legend: { position: 'top' },
    title: { display: true, text: 'Monetary Value by Period and Round' },
  },
  scales: {
    y: { beginAtZero: true, title: { display: true, text: 'Value (£)' } },
  },
});

const rounds = [...new Set(props.reportData.flatMap(p => p.rounds.map(r => r.round_name)))];
chartData.value.datasets = rounds.map(round => ({
  label: round,
  data: props.reportData.map(period => {
    const r = period.rounds.find(r => r.round_name === round);
    return r ? r.total_value : 0;
  }),
  backgroundColor: `#${Math.floor(Math.random() * 16777215).toString(16)}`,
}));
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Round Activity Report</h1>
    <div class="mb-6">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2">Period</th>
          <th v-for="round in reportData[0]?.rounds" :key="round.round_name" class="p-2">{{ round.round_name }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="period in reportData" :key="period.period" class="border-t">
          <td class="p-2">{{ period.period }}</td>
          <td v-for="round in period.rounds" :key="round.round_name" class="p-2">£{{ round.total_value }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
