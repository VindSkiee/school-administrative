<template>
  <div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border-l-4 border-brand-red flex flex-col sm:flex-row items-start sm:items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Selamat datang, {{ authStore.user?.name }}!</h1>
        <p class="text-gray-500 mt-1">Ini adalah ringkasan sistem EduPlatform hari ini.</p>
      </div>
      <div class="mt-4 sm:mt-0 px-4 py-2 bg-brand-orange/10 text-brand-orange rounded-lg font-semibold text-sm">
        Tahun Ajaran: 2026/2027 (Aktif)
      </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="stat in stats" :key="stat.title" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-gray-100">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">{{ stat.title }}</p>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ stat.value }}</p>
          </div>
          <div :class="`p-3 rounded-xl ${stat.bgColor} ${stat.textColor}`">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="stat.icon"></svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';
const authStore = useAuthStore();

// Mock Data
const stats = [
  { title: 'Total Siswa Aktif', value: '1,240', bgColor: 'bg-red-50', textColor: 'text-brand-red', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>' },
  { title: 'Total Guru', value: '85', bgColor: 'bg-orange-50', textColor: 'text-brand-orange', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>' },
  { title: 'Kelas Terdaftar', value: '36', bgColor: 'bg-blue-50', textColor: 'text-blue-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>' },
  { title: 'Tugas Menunggu', value: '12', bgColor: 'bg-green-50', textColor: 'text-green-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' },
];
</script>