<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border-l-4 border-brand-orange flex flex-col sm:flex-row items-start sm:items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Selamat bekerja, Bpk/Ibu {{ authStore.user?.name }}</h1>
        <p class="text-gray-500 mt-1">Anda memiliki 2 jadwal mengajar hari ini.</p>
      </div>
      <div class="mt-4 sm:mt-0 px-4 py-2 bg-brand-red/10 text-brand-red rounded-lg font-semibold text-sm">
        {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
      </div>
    </div>

    <!-- Schedule & Tasks Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Jadwal Hari Ini (Kiri) -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Jadwal Mengajar Hari Ini</h2>
        <div class="space-y-3">
          <div class="flex p-4 border rounded-xl items-center justify-between bg-gray-50">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-brand-red text-white rounded-lg font-bold">08:00</div>
              <div>
                <p class="font-bold text-gray-800">Matematika Lanjut</p>
                <p class="text-sm text-gray-500">Kelas 12-A • Ruang 101</p>
              </div>
            </div>
            <button class="px-4 py-2 bg-brand-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100 transition">Mulai Kelas</button>
          </div>
          <!-- Placeholder Data 2 -->
          <div class="flex p-4 border rounded-xl items-center justify-between bg-white opacity-60">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-gray-200 text-gray-600 rounded-lg font-bold">10:30</div>
              <div>
                <p class="font-bold text-gray-800">Fisika Terapan</p>
                <p class="text-sm text-gray-500">Kelas 11-C • Lab IPA</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Required (Kanan) -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Perlu Perhatian</h2>
        <ul class="space-y-4">
          <li class="flex items-start">
            <span class="flex-shrink-0 h-2 w-2 mt-2 rounded-full bg-brand-orange mr-3"></span>
            <div>
              <p class="text-sm font-semibold text-gray-800">Koreksi Tugas Aljabar</p>
              <p class="text-xs text-gray-500">15 siswa belum dinilai</p>
            </div>
          </li>
          <li class="flex items-start">
            <span class="flex-shrink-0 h-2 w-2 mt-2 rounded-full bg-brand-red mr-3"></span>
            <div>
              <p class="text-sm font-semibold text-gray-800">2 Pengajuan Izin Sakit</p>
              <p class="text-xs text-gray-500">Butuh validasi segera</p>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../../stores/auth';
const authStore = useAuthStore();
</script>