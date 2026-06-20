<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-full">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-sm font-bold text-gray-800">Tren Indeks Akademik (YoY)</h3>
        <p class="text-xs text-gray-400 mt-0.5">Rata-rata performa sekolah 5 tahun terakhir</p>
      </div>
      <div
        v-if="latestIndex !== null"
        class="px-3 py-1.5 rounded-lg text-xs font-bold"
        :class="trendClass"
      >
        Label Tren {{ trendLabel }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-100 border-t-indigo-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex-1 flex flex-col items-center justify-center text-center">
      <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-sm text-gray-500">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!chartSeries[0]?.data?.length" class="flex-1 flex flex-col items-center justify-center text-center">
      <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <p class="text-sm text-gray-500 font-medium">Belum ada data kinerja akademik.</p>
    </div>

    <!-- Chart -->
    <div v-else class="flex-1 min-h-0">
      <apexchart
        type="line"
        :height="280"
        :options="chartOptions"
        :series="chartSeries"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { principalService } from '../services/modules/principal/principalService';

const apexchart = VueApexCharts;

const isLoading = ref(true);
const error = ref(null);
const rawData = ref([]);

// Derived: latest index and trend direction
const latestIndex = computed(() => {
  const valid = rawData.value.filter(d => d.school_index !== null);
  return valid.length > 0 ? valid[valid.length - 1].school_index : null;
});

const previousIndex = computed(() => {
  const valid = rawData.value.filter(d => d.school_index !== null);
  return valid.length >= 2 ? valid[valid.length - 2].school_index : null;
});

const trendDelta = computed(() => {
  if (latestIndex.value === null || previousIndex.value === null) return 0;
  return +(latestIndex.value - previousIndex.value).toFixed(1);
});

const trendLabel = computed(() => {
  if (trendDelta.value > 0) return `+${trendDelta.value}`;
  if (trendDelta.value < 0) return `${trendDelta.value}`;
  return '0';
});

const trendClass = computed(() => {
  if (trendDelta.value > 0) return 'bg-emerald-50 text-emerald-600';
  if (trendDelta.value < 0) return 'bg-red-50 text-red-500';
  return 'bg-gray-50 text-gray-500';
});

// Chart data (Berubah menjadi Mixed Chart: Bar + Area)
const chartSeries = computed(() => [
  {
    name: 'Garis Vertikal',
    type: 'bar', // Berfungsi sebagai garis vertikal dari 0 ke titik nilai
    data: rawData.value.map(d => d.school_index),
  },
  {
    name: 'Indeks Sekolah',
    type: 'area', // Chart data utama
    data: rawData.value.map(d => d.school_index),
  }
]);

const chartOptions = computed(() => ({
  chart: {
    type: 'line', // Wajib 'line' untuk mengakomodasi mixed series
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  // Warna indigo yang sama untuk garis vertikal dan chart utama
  colors: ['#6366f1', '#6366f1'], 
  fill: {
    type: ['solid', 'gradient'], // Solid untuk garis vertikal, gradient untuk area
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.35,
      opacityTo: 0.05,
      stops: [0, 100],
    },
  },
  stroke: {
    curve: 'smooth',
    width: [0, 3], // 0px untuk bar agar tidak ada border tebal, 3px untuk area utama
  },
  plotOptions: {
    bar: {
      columnWidth: '3px', // Membuat bar chart terlihat seperti garis vertikal tipis
      borderRadius: 0,
    }
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: rawData.value.map(d => d.academic_year),
    labels: {
      style: { fontSize: '11px', colors: '#9ca3af' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    min: 0,
    max: 100,
    tickAmount: 5, // Akan menghasilkan interval pasti: 0, 20, 40, 60, 80, 100
    labels: {
      style: { fontSize: '11px', colors: '#9ca3af' },
      formatter: (val) => val.toFixed(0),
    },
  },
  grid: {
    // Garis horizontal acuan dengan opacity sangat rendah
    borderColor: 'rgba(156, 163, 175, 0.2)', 
    strokeDashArray: 0,
    padding: { left: 8, right: 8 },
    yaxis: {
      lines: { show: true }
    }
  },
  tooltip: {
    theme: 'light',
    shared: true,
    intersect: false,
    enabledOnSeries: [1], // Hanya memunculkan tooltip untuk series 'area' agar rapi
    y: {
      formatter: (val) => val !== null ? val.toFixed(1) : 'N/A',
    },
  },
  markers: {
    size: [0, 5], // Hilangkan marker untuk bar, tampilkan untuk area (index 1)
    colors: ['#6366f1'],
    strokeColors: '#fff',
    strokeWidth: 2,
    hover: { size: 7 },
  },
}));

const fetchData = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const res = await principalService.getYearOverYear();
    rawData.value = res.data?.data || res.data || [];
  } catch (e) {
    error.value = 'Gagal memuat data tren akademik.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchData);
</script>
