<template>
  <div class="space-y-6">
    <div class="bg-brand-red rounded-2xl p-6 sm:p-8 shadow-md text-brand-white flex flex-col sm:flex-row items-start sm:items-center justify-between relative overflow-hidden">
      <!-- Decorative element -->
      <div class="absolute right-0 top-0 w-64 h-64 bg-brand-orange opacity-20 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>
      
      <div class="relative z-10">
        <h1 class="text-2xl font-bold">Halo, {{ authStore.user?.name }}! 👋</h1>
        <p class="text-red-100 mt-1">Kelas 12-A | Semester Ganjil</p>
      </div>
      <div class="mt-4 sm:mt-0 relative z-10">
        <div class="bg-white/20 backdrop-blur-sm px-6 py-3 rounded-xl border border-white/30 text-center">
          <p class="text-xs text-red-100 uppercase font-semibold tracking-wider">Persentase Hadir</p>
          <p class="text-2xl font-bold">98%</p>
        </div>
      </div>
    </div>

    <!-- Student specific widgets -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 text-brand-orange mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Tugas Terdekat (Deadline)
        </h2>
        <div class="p-4 bg-orange-50 border border-orange-100 rounded-xl">
          <p class="font-bold text-brand-orange">Esai Sejarah Kemerdekaan</p>
          <p class="text-sm text-gray-600 mt-1">Berakhir: Hari ini, 23:59 WIB</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
          Nilai Terbaru
        </h2>
        <div class="p-4 border rounded-xl flex justify-between items-center">
          <div>
            <p class="font-bold text-gray-800">Ujian Tengah Semester - Biologi</p>
            <p class="text-sm text-gray-500">Guru: Ibu Ratna</p>
          </div>
          <div class="text-xl font-bold text-brand-red">85</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';
const authStore = useAuthStore();
</script>