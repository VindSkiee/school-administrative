<template>
  <div class="space-y-6">
    <div class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md">
      <h1 class="text-2xl md:text-3xl font-bold font-serif">Dashboard Eksekutif</h1>
      <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
        Tinjauan akademik dan operasional sekolah untuk Kepala Sekolah.
      </p>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
      <p class="text-gray-500 font-medium text-sm">Memuat statistik sekolah...</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Siswa</p>
            <p class="text-3xl font-black text-gray-800">{{ stats.total_students || 0 }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Siswa aktif terdaftar</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Guru</p>
            <p class="text-3xl font-black text-gray-800">{{ stats.total_teachers || 0 }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Tenaga pengajar</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-14 h-14 bg-orange-50 text-brand-orange rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Kelas Aktif</p>
            <p class="text-3xl font-black text-gray-800">{{ stats.total_classes || 0 }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Ruang kelas aktif</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-14 h-14 bg-red-50 text-brand-red rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tahun Ajaran</p>
            <p class="text-lg font-black text-gray-800 leading-tight">{{ stats.active_academic_year || '-' }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Periode aktif</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <YoYTrendChart />
        <GradeDistributionChart />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { principalService } from '../../services/modules/principal/principalService';
import { useToastStore } from '../../stores/toast';
import YoYTrendChart from '../../components/YoYTrendChart.vue';
import GradeDistributionChart from '../../components/GradeDistributionChart.vue';

const toastStore = useToastStore();

const isLoading = ref(true);
const stats = ref({});

const fetchStats = async () => {
  isLoading.value = true;
  try {
    const res = await principalService.getDashboardStats();
    stats.value = res.data?.data || res.data || {};
  } catch (error) {
    toastStore.error('Gagal memuat statistik dashboard.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchStats);
</script>
