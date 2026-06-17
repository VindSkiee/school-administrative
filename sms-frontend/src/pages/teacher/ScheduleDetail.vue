<template>
  <div class="space-y-6">
    <div
      class="bg-white p-4 md:p-8 rounded-3xl shadow-sm border border-gray-200 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6"
    >
      <!-- Left Section -->
      <div class="flex items-start gap-3 w-full lg:w-auto">
        <button
          @click="goBack"
          class="mt-1 text-gray-400 hover:text-brand-red transition-colors flex-shrink-0"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
        </button>

        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2 mb-2">
            <span
              class="px-2.5 py-1 bg-red-50 text-brand-red text-xs font-bold rounded-lg uppercase tracking-wider"
            >
              Kelas {{ scheduleInfo.school_class?.name || "Memuat Kelas..." }}
            </span>

            <span class="text-sm font-semibold text-gray-500">
              {{ scheduleInfo.start_time?.substring(0, 5) || "" }} -
              {{ scheduleInfo.end_time?.substring(0, 5) || "" }}
            </span>

            <span
              class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg uppercase tracking-wider"
            >
              {{ scheduleDayNameId }}
            </span>
          </div>

          <h1
            class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 font-serif break-words"
          >
            {{ scheduleInfo.subject?.name || "Memuat Mapel..." }}
          </h1>
        </div>
      </div>

      <!-- Right Section -->
      <div
        class="w-full lg:w-auto bg-gray-50 p-3 rounded-xl border border-gray-200 flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3"
      >
        <label class="text-sm font-semibold text-gray-600">
          Tanggal Pertemuan:
        </label>

        <input
          type="date"
          v-model="globalDate"
          class="w-full sm:w-auto min-w-[180px] px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-red/20 outline-none font-medium text-gray-800"
        />
      </div>
    </div>

    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="w-full px-4 py-3 rounded-xl text-sm font-semibold transition-all"
          :class="
            activeTab === tab.id
              ? 'bg-brand-red text-white shadow-sm'
              : 'bg-gray-50 text-gray-500 hover:text-gray-800 hover:bg-gray-100'
          "
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <div class="transition-all duration-300">
      <div v-if="isReportPublished" class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 mb-6">
        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        <p class="text-sm font-semibold text-amber-800">Rapor sudah diterbitkan. Mode lihat saja — absensi, materi, dan tugas dikunci.</p>
      </div>

      <div v-if="!globalDate" class="flex justify-center py-16">
        <div
          class="animate-spin h-10 w-10 border-4 border-gray-200 border-t-brand-red rounded-full"
        ></div>
      </div>

      <template v-else>
        <div>
          <div v-if="activeTab === 'attendance'">
            <div
              v-if="!isDateValidForSchedule"
              class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center shadow-sm max-w-3xl mx-auto"
            >
              <div
                class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4"
              >
                <svg
                  class="w-8 h-8"
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
              </div>
              <h3 class="text-xl font-bold text-red-800 mb-2">
                Tanggal Tidak Sesuai Jadwal!
              </h3>
              <p class="text-red-600 font-medium">
                Mata pelajaran ini hanya dijadwalkan pada hari
                <span class="font-extrabold uppercase">{{
                  scheduleDayNameId
                }}</span
                >.
              </p>
              <button
                @click="resetToTodayOrNearest"
                class="mt-6 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors"
              >
                Kembali ke Tanggal Valid Terdekat
              </button>
            </div>

            <div
              v-else-if="isDateInFuture"
              class="bg-blue-50 border border-blue-200 rounded-2xl p-8 text-center shadow-sm max-w-3xl mx-auto"
            >
              <div
                class="w-16 h-16 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4"
              >
                <svg
                  class="w-8 h-8"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  ></path>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-blue-800 mb-2">
                Belum Waktunya Absen! ⏳
              </h3>
              <p class="text-blue-600 font-medium">
                Data absensi hanya dapat diisi pada hari H atau mundur ke
                belakang.
              </p>
              <div
                class="flex flex-col md:flex-row gap-2 px-4 justify-center mt-2"
              >
                <button
                  @click="activeTab = 'materials'"
                  class="mt-6 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors"
                >
                  Buat Materi untuk Tanggal Ini
                </button>
                <button
                  @click="activeTab = 'assignments'"
                  class="mt-6 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm transition-colors"
                >
                  Buat Tugas untuk Tanggal Ini
                </button>
              </div>
            </div>

            <div v-else-if="globalDate">
              <AttendancePanel
                :scheduleId="scheduleId"
                :selectedDate="globalDate"
                :locked="isReportPublished"
              />
            </div>
          </div>

          <div v-if="activeTab === 'materials'">
            <MaterialPanel
              :scheduleId="scheduleId"
              :selectedDate="globalDate"
              :locked="isReportPublished"
            />
          </div>

          <div v-if="activeTab === 'assignments'">
            <AssignmentPanel
              :scheduleId="scheduleId"
              :selectedDate="globalDate"
              :locked="isReportPublished"
            />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onActivated } from "vue";
import { useRoute, useRouter } from "vue-router";
import AttendancePanel from "./schedulePanel/AttendancePanel.vue";
import AssignmentPanel from "./schedulePanel/AssignmentPanel.vue";
import MaterialPanel from "./schedulePanel/MaterialPanel.vue";

import { attendanceService } from "../../services/modules/teacher/attendanceService";
import { useToastStore } from "../../stores/toast";
import { useAttendanceDetailStore } from "../../stores/attendanceDetail";
import { useReportStatus } from '../../composables/useReportStatus';

const attendanceDetailStore = useAttendanceDetailStore();
const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { isReportPublished } = useReportStatus('teacher');

// PERF FIX: use local reactive state for tab and date — no URL sync needed
const activeTab = ref(route.query.tab || "attendance");
const globalDate = ref(route.query.date || "");

// Capture schedule_id in a local ref — prevents undefined during keep-alive deactivation
// when the route changes but this component is still in the DOM transitioning out
const scheduleId = ref(route.params.schedule_id);

// 🔥 TRACING FIX 1: Gunakan `computed` alih-alih `ref`
// Ini memastikan variabel ini terus membaca (live-sync) dari Store tanpa terputus.
const scheduleInfo = computed(() => attendanceDetailStore.scheduleInfo || {});

const tabs = [
  { id: "attendance", label: "Absensi & Kehadiran" },
  { id: "materials", label: "Materi Belajar" },
  { id: "assignments", label: "Tugas & Penilaian" },
];

const dayMap = {
  sunday: "Minggu",
  monday: "Senin",
  tuesday: "Selasa",
  wednesday: "Rabu",
  thursday: "Kamis",
  friday: "Jumat",
  saturday: "Sabtu",
};

const dayNamesEng = [
  "sunday",
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
];

const scheduleDayNameId = computed(() => {
  return dayMap[scheduleInfo.value?.day_of_week?.toLowerCase()] || "...";
});

const todayString = new Date().toISOString().split("T")[0];

const isDateInFuture = computed(() => {
  if (!globalDate.value) return false;
  return globalDate.value > todayString;
});

const selectedDayNameId = computed(() => {
  if (!globalDate.value) return "";
  const [y, m, d] = globalDate.value.split("-");
  const dateObj = new Date(y, m - 1, d);
  const dayIndex = dateObj.getDay();
  return dayMap[dayNamesEng[dayIndex]];
});

const isDateValidForSchedule = computed(() => {
  if (!scheduleInfo.value?.day_of_week || !globalDate.value) return true;

  const [y, m, d] = globalDate.value.split("-");
  const dateObj = new Date(y, m - 1, d);
  const selectedDayEng = dayNamesEng[dateObj.getDay()];

  return selectedDayEng === scheduleInfo.value.day_of_week.toLowerCase();
});

const resetToTodayOrNearest = () => {
  if (!scheduleInfo.value?.day_of_week) return;

  const targetDayIndex = dayNamesEng.indexOf(
    scheduleInfo.value.day_of_week.toLowerCase(),
  );
  let dateObj = new Date();

  while (dateObj.getDay() !== targetDayIndex) {
    dateObj.setDate(dateObj.getDate() - 1);
  }

  const y = dateObj.getFullYear();
  const m = String(dateObj.getMonth() + 1).padStart(2, "0");
  const d = String(dateObj.getDate()).padStart(2, "0");

  globalDate.value = `${y}-${m}-${d}`;
};

// Navigate back to attendance schedule with the same day pre-selected
const goBack = () => {
  const day = scheduleInfo.value?.day_of_week?.toLowerCase() || '';
  router.push({ path: '/teacher/schedules/today', query: day ? { day } : {} });
};

// FIX: Gunakan local ref untuk track scheduleInfo loading, bukan loadedScheduleId.
// loadedScheduleId diperlukan oleh children (prefetchAllData) — jangan direset di parent.
const _infoLoadedFor = ref(null);

const fetchScheduleData = async () => {
  try {
    const targetId = String(scheduleId.value);
    // FIX: Jika AttendanceSchedule sudah prefetch data ini, skip fetch
    if (attendanceDetailStore.isDataLoaded(targetId)) {
      _infoLoadedFor.value = targetId;
      return;
    }
    if (_infoLoadedFor.value !== targetId) {
      const response = await attendanceService.getScheduleDetail(scheduleId.value);
      attendanceDetailStore.scheduleInfo = response.data;
      _infoLoadedFor.value = targetId;
    }
  } catch (error) {
    console.error("Gagal memuat detail jadwal:", error);
    toastStore.error("Jadwal tidak ditemukan atau Anda tidak memiliki akses.");
    router.push("/teacher/attendance");
  }
};

// onMounted: fire saat komponen pertama kali dibuat (termasuk di dalam keep-alive)
onMounted(async () => {
  await fetchScheduleData();
  if (!route.query.date) {
    resetToTodayOrNearest();
  }
});

// FIX: onActivated fire saat komponen di-activate dari cache (keep-alive)
// Menangani: ScheduleDetail → StudentProfile → Back (data masih ada, skip fetch)
onActivated(async () => {
  await fetchScheduleData();
  if (!route.query.date && !globalDate.value) {
    resetToTodayOrNearest();
  }
});

// PERF FIX: removed router.replace on date change — prevents component remount via Vue Router
// Date changes are handled locally via reactive state; child panels watch the selectedDate prop
</script>