<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard Eksekutif</h1>
        <p class="text-gray-500 text-sm">Tinjauan Akademik Kepala Sekolah</p>
      </div>
      <div class="flex items-center gap-3 mt-4 sm:mt-0">
        <div
          class="px-4 py-2 bg-brand-orange/10 text-brand-orange rounded-lg font-semibold text-sm flex items-center"
        >
          <svg
            class="w-4 h-4 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            ></path>
          </svg>
          T.A: {{ dashboardData.academicYear }}
        </div>
        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          Unduh Laporan PDF
        </button>
      </div>
    </div>

    <!-- Analytic Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <YoYTrendChart />
      <GradeDistributionChart />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { principalService } from '../../services/modules/principal/principalService.js';
import YoYTrendChart from '../../components/YoYTrendChart.vue';
import GradeDistributionChart from '../../components/GradeDistributionChart.vue';

const dashboardData = ref({ academicYear: null });

const fetchDashboardStats = async () => {
  try {
    const response = await principalService.getDashboardStats();
    const payload = response.data.data;
    dashboardData.value.academicYear = payload.active_academic_year || null;
  } catch (error) {
    console.error('Gagal memuat data dashboard:', error);
  }
};

onMounted(() => {
  fetchDashboardStats();
});
</script>