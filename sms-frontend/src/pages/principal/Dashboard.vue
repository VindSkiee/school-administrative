<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard Eksekutif</h1>
        <p class="text-gray-500">Tinjauan Akademik Kepala Sekolah</p>
      </div>
      <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        Unduh Laporan PDF
      </button>
    </div>

    <!-- Analytic Placeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-64 flex flex-col items-center justify-center text-center">
        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
        <p class="font-semibold text-gray-600">Grafik Tren Absensi Sekolah</p>
        <p class="text-sm text-gray-400">(Akan diintegrasikan dengan Chart.js di Phase 7)</p>
      </div>
      
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-64 flex flex-col items-center justify-center text-center">
        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
        <p class="font-semibold text-gray-600">Distribusi Nilai Rata-rata</p>
        <p class="text-sm text-gray-400">(Akan diintegrasikan dengan Chart.js di Phase 7)</p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Dashboard Kepala Sekolah bersifat Read-Only
</script>