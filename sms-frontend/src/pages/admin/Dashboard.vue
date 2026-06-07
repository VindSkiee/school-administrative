<template>
  <div class="space-y-6">
    <div
      class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border-l-4 border-brand-red flex flex-col sm:flex-row items-start sm:items-center justify-between transition-all"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Selamat datang, {{ authStore.user?.name }}!
        </h1>
        <p class="text-gray-500 mt-1">
          Pusat sistem administrasi sekolah hari ini.
        </p>
      </div>
      <div
        class="mt-4 sm:mt-0 px-4 py-2 bg-brand-orange/10 text-brand-orange rounded-lg font-semibold text-sm flex items-center"
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
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <div
        v-for="stat in computedStats"
        :key="stat.title"
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow"
      >
        <div>
          <p class="text-sm font-medium text-gray-500">{{ stat.title }}</p>
          <div
            v-if="isLoading"
            class="h-8 w-20 bg-gray-200 animate-pulse rounded mt-2"
          ></div>
          <p v-else class="text-3xl font-bold text-gray-800 mt-2">
            {{ stat.value }}
          </p>
        </div>
        <div :class="`p-3 rounded-xl ${stat.bgColor} ${stat.textColor}`">
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            v-html="stat.icon"
          ></svg>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div
        class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
      >
        <div
          class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50"
        >
          <h2 class="font-bold text-gray-800 font-serif">
            Aktivitas Sistem Terbaru
          </h2>
          <router-link
            :to="{ name: 'Log Aktivitas' }"
            class="text-sm text-brand-red font-semibold hover:underline"
          >
            Lihat Semua
          </router-link>
        </div>
        <div class="p-0 flex-1">
          <div v-if="isLoading" class="p-6 space-y-4">
            <div v-for="i in 4" :key="i" class="flex gap-4">
              <div
                class="w-10 h-10 bg-gray-200 rounded-full animate-pulse"
              ></div>
              <div class="flex-1 space-y-2 py-1">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          </div>

          <ul
            v-else-if="dashboardData.recentActivities.length > 0"
            class="divide-y divide-gray-100"
          >
            <li
              v-for="log in dashboardData.recentActivities"
              :key="log.id"
              class="px-6 py-4 hover:bg-gray-50 transition-colors flex items-start gap-4"
            >
              <div class="mt-1">
                <span
                  v-if="log.action === 'created'"
                  class="inline-flex p-2 bg-green-100 text-green-600 rounded-full"
                  ><svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4"
                    ></path></svg
                ></span>
                <span
                  v-else-if="log.action === 'updated'"
                  class="inline-flex p-2 bg-blue-100 text-blue-600 rounded-full"
                  ><svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    ></path></svg
                ></span>
                <span
                  v-else
                  class="inline-flex p-2 bg-red-100 text-red-600 rounded-full"
                  ><svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    ></path></svg
                ></span>
              </div>
              <div>
                <p class="text-sm text-gray-800">
                  <span class="font-semibold">{{
                    log.user?.name || "Sistem"
                  }}</span>
                  melakukan aksi
                  <span class="font-semibold capitalize text-brand-red">{{
                    formatActionLabel(log.action)
                  }}</span>
                  pada data
                  <span class="font-semibold">{{
                    translateModelName(log.loggable_type.split("\\").pop())
                  }}</span>
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                  {{ new Date(log.created_at).toLocaleString("id-ID") }}
                </p>
              </div>
            </li>
          </ul>

          <div v-else class="p-8 text-center text-gray-500">
            Belum ada aktivitas terekam hari ini.
          </div>
        </div>
      </div>

      <div
        class="bg-brand-red text-white rounded-2xl shadow-sm p-6 flex flex-col justify-between relative overflow-hidden"
      >
        <div
          class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"
        ></div>

        <div>
          <h2 class="font-bold text-xl font-serif">Aksi Cepat</h2>
          <p class="text-red-100 text-sm mt-2 line-clamp-3">
            Akses menu yang paling sering digunakan untuk mengelola aktivitas
            sekolah.
          </p>
        </div>

        <div class="space-y-3 mt-6 relative z-10">
          <router-link
            :to="{ name: 'Manajemen Pengguna' }"
            class="flex items-center justify-between w-full bg-white/10 hover:bg-white/20 px-4 py-3 rounded-xl transition-colors backdrop-blur-sm"
          >
            <span class="font-semibold text-sm">Kelola Pengguna</span>
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              ></path>
            </svg>
          </router-link>

          <router-link
            :to="{ name: 'Data Kelas' }"
            class="flex items-center justify-between w-full bg-white/10 hover:bg-white/20 px-4 py-3 rounded-xl transition-colors backdrop-blur-sm"
          >
            <span class="font-semibold text-sm">Alokasi Kelas</span>
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              ></path>
            </svg>
          </router-link>

          <router-link
            :to="{ name: 'Laporan Akademik' }"
            class="flex items-center justify-between w-full bg-white/10 hover:bg-white/20 px-4 py-3 rounded-xl transition-colors backdrop-blur-sm"
          >
            <span class="font-semibold text-sm">Pantau Rapor</span>
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              ></path>
            </svg>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "../../stores/auth";
import { dashboardService } from "../../services/modules/admin/dashboardService";

const authStore = useAuthStore();
const isLoading = ref(true);

const dashboardData = ref({
  academicYear: "Memuat...",
  stats: { students: 0, teachers: 0, classes: 0 },
  recentActivities: [],
});

const fetchDashboardData = async () => {
  isLoading.value = true;
  try {
    const response = await dashboardService.getStats();

    // Ambil data payload dari backend
    const payload = response.data.data;

    // Lakukan mapping dari snake_case (Laravel) ke camelCase (Vue)
    dashboardData.value = {
      academicYear: payload.academic_year || "Tidak ada Tahun Ajaran aktif",
      stats: {
        students: payload.stats?.students || 0,
        teachers: payload.stats?.teachers || 0,
        classes: payload.stats?.classes || 0,
      },
      // Pastikan selalu berupa array meskipun backend mengembalikan null
      recentActivities: payload.recent_activities || [],
    };
  } catch (error) {
    console.error("Gagal memuat data dashboard", error);
  } finally {
    isLoading.value = false;
  }
};

const translateModelName = (modelName) => {
  const dictionary = {
    User: "Pengguna",
    Student: "Siswa",
    Teacher: "Guru",
    SchoolClass: "Kelas",
    Subject: "Mata Pelajaran",
    Schedule: "Jadwal Pelajaran",
    AcademicYear: "Tahun Ajaran",
    Grade: "Nilai Akademik",
  };
  return dictionary[modelName] || modelName;
};

const formatActionLabel = (action) => {
  const normalized = String(action || '').toLowerCase();
  const map = {
    created: 'Membuat',
    updated: 'Memperbarui',
    deleted: 'Menghapus',
  };
  return map[normalized] || action;
};

const computedStats = computed(() => [
  {
    title: "Total Siswa Aktif",
    value: dashboardData.value.stats.students,
    bgColor: "bg-red-50",
    textColor: "text-brand-red",
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>',
  },
  {
    title: "Total Guru",
    value: dashboardData.value.stats.teachers,
    bgColor: "bg-orange-50",
    textColor: "text-brand-orange",
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
  },
  {
    title: "Kelas Terdaftar",
    value: dashboardData.value.stats.classes,
    bgColor: "bg-blue-50",
    textColor: "text-blue-600",
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>',
  },
]);

onMounted(() => {
  fetchDashboardData();
});
</script>
