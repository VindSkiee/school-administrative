<template>
  <div class="space-y-6">
    <div
      class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Jadwal Mengajar
        </h1>
        <p class="text-gray-500 mt-1">
          Pilih jadwal di bawah ini untuk mengisi absensi dan rekap nilai.
        </p>
      </div>

      <div class="w-full md:w-56">
        <BaseSelect
          v-model="selectedDay"
          :options="dayOptions"
          placeholder="Pilih Hari"
          @update:modelValue="fetchSchedules"
        />
      </div>
    </div>

    <div
      v-if="isReportPublished"
      class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3"
    >
      <svg
        class="w-5 h-5 text-amber-600 shrink-0"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
        ></path>
      </svg>
      <p class="text-sm font-semibold text-amber-800">
        Rapor sudah diterbitkan. Semua operasi absensi, materi, dan tugas
        dikunci.
      </p>
    </div>

    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <div
        class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-red"
      ></div>
    </div>

    <div
      v-else-if="schedules.length === 0"
      class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm flex flex-col items-center"
    >
      <div
        class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4"
      >
        <svg
          class="w-10 h-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">Tidak Ada Jadwal</h3>
      <p class="text-gray-500 text-sm mt-1">
        Anda tidak memiliki jadwal mengajar pada hari {{ selectedDayLabel }}.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="schedule in schedules"
        :key="schedule.id"
        @click="!isReportPublished && goToScheduleDetail(schedule.id)"
        class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm transition-all group flex flex-col h-full relative overflow-hidden"
        :class="
          isReportPublished
            ? 'opacity-60 cursor-not-allowed'
            : 'hover:shadow-md hover:border-brand-red/30 cursor-pointer'
        "
      >
        <div
          class="absolute left-0 top-0 bottom-0 w-1 bg-brand-red opacity-80"
        ></div>
        <div
          v-if="loadingCardId === schedule.id"
          class="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center"
        >
          <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-brand-red"
          ></div>
        </div>

        <div class="flex justify-between items-start mb-4 pl-2">
          <div>
            <span
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-50 text-brand-red text-xs font-bold mb-2"
            >
              <svg
                class="w-3.5 h-3.5"
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
              {{ formatTime(schedule.start_time) }} -
              {{ formatTime(schedule.end_time) }}
            </span>
            <h3
              class="text-xl font-bold text-gray-800 group-hover:text-brand-red transition-colors"
            >
              {{ schedule.subject?.name || "Mata Pelajaran" }}
            </h3>
          </div>
          <div
            class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-brand-red group-hover:text-white transition-colors"
          >
            <svg
              class="w-8 h-6"
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
          </div>
        </div>

        <div
          class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between pl-2"
        >
          <div class="flex items-center gap-2">
            <div>
              <p
                class="text-xs text-gray-500 font-medium uppercase tracking-wider"
              >
                Kelas
              </p>
              <p class="text-sm font-bold text-gray-800">
                {{ schedule.school_class?.name || "-" }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p
              class="text-[10px] text-gray-400 font-medium uppercase tracking-wider"
            >
              {{ calculateDateForDay(selectedDay) }}
            </p>

            <p
              class="text-sm font-semibold text-gray-600 flex items-center justify-end gap-1.5"
            >
              <template v-if="isReportPublished">
                <svg
                  class="w-4 h-4 text-red-600 shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                  ></path>
                </svg>
                <span class="text-red-600">Terkunci</span>
              </template>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import BaseSelect from "../../components/BaseSelect.vue";
import { attendanceService } from "../../services/modules/teacher/attendanceService";
import { useToastStore } from "../../stores/toast";
import { useAttendanceDetailStore } from "../../stores/attendanceDetail";
import { useReportStatus } from "../../composables/useReportStatus";

const router = useRouter();
const route = useRoute();
const toastStore = useToastStore();
const attendanceDetailStore = useAttendanceDetailStore();
const { isReportPublished } = useReportStatus("teacher");
const loadingCardId = ref(null);

const isLoading = ref(true);
const schedules = ref([]);

// Opsi Hari untuk BaseSelect
const dayOptions = [
  { value: "monday", label: "Senin" },
  { value: "tuesday", label: "Selasa" },
  { value: "wednesday", label: "Rabu" },
  { value: "thursday", label: "Kamis" },
  { value: "friday", label: "Jumat" },
  { value: "saturday", label: "Sabtu" },
];

const dayNamesEng = [
  "sunday",
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
];

// Menentukan default hari saat halaman dibuka
const getCurrentDay = () => {
  const today = dayNamesEng[new Date().getDay()];
  if (today === "saturday" || today === "sunday") return "monday";
  return today;
};

const selectedDay = ref(route.query.day || getCurrentDay());

const selectedDayLabel = computed(() => {
  const day = dayOptions.find((d) => d.value === selectedDay.value);
  return day ? day.label : "";
});

// LOGIKA PINTAR: Menghitung Tanggal (YYYY-MM-DD) berdasarkan Hari yang dipilih dari Dropdown
const calculateDateForDay = (targetDayString) => {
  const today = new Date();
  const currentDayIndex = today.getDay(); // 0 (Minggu) s/d 6 (Sabtu)
  const targetDayIndex = dayNamesEng.indexOf(targetDayString);

  // Cari selisih hari antara hari ini dan hari target di minggu yang sama
  let diff = targetDayIndex - currentDayIndex;

  // Modifikasi ini (opsional): Jika kita ingin defaultnya selalu menarik tanggal ke MASA LALU
  // jika harinya belum terjadi minggu ini, kita bisa kurang 7.
  // Tapi untuk saat ini, kita ikuti alur standar (Senin lalu, Jumat besok).

  const targetDate = new Date(today);
  targetDate.setDate(today.getDate() + diff);

  // Format ke YYYY-MM-DD
  const y = targetDate.getFullYear();
  const m = String(targetDate.getMonth() + 1).padStart(2, "0");
  const d = String(targetDate.getDate()).padStart(2, "0");

  return `${y}-${m}-${d}`;
};

// Fetch data dari API
const fetchSchedules = async () => {
  isLoading.value = true;
  try {
    const { data } = await attendanceService.getSchedules(selectedDay.value);
    schedules.value = data;
  } catch (error) {
    console.error("Gagal memuat jadwal:", error);
    toastStore.error("Gagal memuat jadwal mengajar. Silakan coba lagi.");
  } finally {
    isLoading.value = false;
  }
};

const formatTime = (timeString) => {
  if (!timeString) return "";
  return timeString.substring(0, 5);
};

// FIX: Prefetch SEMUA data ke store SEBELUM navigate.
// Strategi: loading di card → data siap → navigate → ScheduleDetail + children langsung tampil.
const goToScheduleDetail = async (scheduleId) => {
  if (loadingCardId.value) return;

  loadingCardId.value = scheduleId;
  try {
    const computedDate = calculateDateForDay(selectedDay.value);
    await attendanceDetailStore.prefetchAllData(scheduleId, computedDate);

    router.push({
      path: `/teacher/classes/${scheduleId}/detail`,
      query: { date: computedDate },
    });
  } catch (error) {
    console.error("Gagal memuat data jadwal:", error);
    toastStore.error("Gagal memuat data jadwal. Silakan coba lagi.");
  } finally {
    loadingCardId.value = null;
  }
};

onMounted(() => {
  fetchSchedules();
});
</script>
