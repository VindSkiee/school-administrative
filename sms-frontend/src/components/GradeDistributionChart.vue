<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-full">
    <div class="mb-6">
      <h3 class="text-sm font-bold text-gray-800">Distribusi Nilai Siswa</h3>
      <p class="text-xs text-gray-400 mt-0.5">
        {{ activeYearLabel || 'Kategori predikat tahun ajaran aktif' }}
      </p>
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
    <div v-else-if="totalStudents === 0" class="flex-1 flex flex-col items-center justify-center text-center">
      <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
      </svg>
      <p class="text-sm text-gray-500 font-medium">Belum ada data nilai siswa.</p>
    </div>

    <!-- Chart + Legend -->
    <div v-else class="flex-1 flex flex-col items-center min-h-0">
      <div class="w-full max-w-[240px]">
        <apexchart
          type="donut"
          :height="220"
          :options="chartOptions"
          :series="chartSeries"
        />
      </div>

      <!-- Custom Legend -->
      <div class="grid grid-cols-2 gap-x-6 gap-y-2 mt-4 w-full">
        <div
          v-for="item in legendItems"
          :key="item.category"
          class="flex items-center gap-2"
        >
          <span
            class="w-3 h-3 rounded-full shrink-0"
            :style="{ backgroundColor: item.color }"
          ></span>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-gray-700 truncate">
              {{ item.label }}
            </p>
            <p class="text-[10px] text-gray-400">
              {{ item.count }} siswa · {{ item.percentage }}%
            </p>
          </div>
        </div>
      </div>
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
const activeYearLabel = ref('');

// Category config
const categoryConfig = {
  A: { label: 'Sangat Baik (A)', color: '#10b981' }, // Emerald
  B: { label: 'Baik (B)',        color: '#6366f1' }, // Indigo
  C: { label: 'Kurang (C)',      color: '#9ca3af' }, // Gray
  D: { label: 'Sangat Kurang (D)', color: '#f59e0b' }, // Amber
};

const totalStudents = computed(() => {
  return rawData.value.reduce((sum, d) => sum + d.count, 0);
});

const chartSeries = computed(() => {
  return rawData.value.map(d => d.count);
});

const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  colors: rawData.value.map(d => categoryConfig[d.category]?.color || '#9ca3af'),
  labels: rawData.value.map(d => categoryConfig[d.category]?.label || d.category),
  stroke: {
    width: 2,
    colors: ['#ffffff'],
  },
  dataLabels: {
    enabled: false,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          name: { show: false },
          value: { show: false },
          total: {
            show: true,
            label: 'Total Siswa',
            fontSize: '11px',
            fontWeight: 600,
            color: '#9ca3af',
            formatter: () => String(totalStudents.value),
          },
        },
      },
    },
  },
  legend: { show: false }, // Using custom legend below
  tooltip: {
    theme: 'light',
    y: {
      formatter: (val) => `${val} siswa`,
    },
  },
}));

const legendItems = computed(() => {
  return rawData.value.map(d => ({
    category: d.category,
    label: categoryConfig[d.category]?.label || d.category,
    color: categoryConfig[d.category]?.color || '#9ca3af',
    count: d.count,
    percentage: d.percentage,
  }));
});

const fetchData = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const res = await principalService.getGradeDistribution();
    const payload = res.data?.data || res.data || {};
    // Response structure: { distribution: [...], academic_year_name: '...' }
    rawData.value = payload.distribution || payload || [];
    activeYearLabel.value = payload.academic_year_name
      ? `Tahun Ajaran: ${payload.academic_year_name}`
      : 'Kategori predikat tahun ajaran aktif';
  } catch (e) {
    error.value = 'Gagal memuat distribusi nilai.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchData);
</script>
