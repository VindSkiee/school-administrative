<template>
  <div class="space-y-6">
    <div
      class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Jadwal Pelajaran
        </h1>
        <p class="text-gray-500 mt-1">
          Pilih hari untuk melihat kelas yang akan Anda ikuti.
        </p>
      </div>

      <div class="w-full md:w-56">
        <div class="w-full md:w-56">
          <BaseSelect
            v-model="selectedDay"
            :options="dayOptions"
            placeholder="Pilih Hari"
            @update:modelValue="fetchSchedules"
          />
        </div>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="flex flex-col justify-center items-center py-16"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-gray-100 border-t-brand-red mb-4"
      ></div>
      <p class="text-gray-500 font-medium">Menarik jadwal dari sistem...</p>
    </div>

    <div
      v-else-if="schedules.length === 0"
      class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm flex flex-col items-center"
    >
      <div
        class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-5"
      >
        <svg
          class="w-12 h-12 text-gray-400"
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
      <h3 class="text-xl font-bold text-gray-800">Asyik, Kelas Kosong! 🎉</h3>
      <p class="text-gray-500 mt-2">
        Anda tidak memiliki jadwal pelajaran pada hari
        <span class="font-bold text-gray-700">{{ selectedDayLabel }}</span
        >.
      </p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <div 
        v-for="schedule in schedules" 
        :key="schedule.id"
        @click="handleCardClick(schedule.id)"
        :class="[
          'bg-white border rounded-2xl p-5 sm:p-6 shadow-sm transition-all duration-300 relative overflow-hidden flex flex-col',
          loadingCardId === schedule.id ? 'opacity-70 cursor-wait' : '',
          isUnlocked ? 'border-gray-200 hover:shadow-lg hover:-translate-y-1 hover:border-brand-red/40 cursor-pointer group' : 'border-gray-100 bg-gray-50/50 opacity-75 cursor-not-allowed grayscale-[20%]'
        ]"
      >
        <div v-if="!isUnlocked" class="absolute top-4 right-4 bg-gray-200/80 backdrop-blur-sm text-gray-600 text-[10px] uppercase tracking-wider px-2.5 py-1 rounded-md font-bold flex items-center gap-1.5 z-10">
          🔒 Terkunci
        </div>
        <div v-if="isUnlocked" class="absolute left-0 top-0 bottom-0 w-1.5 bg-brand-red opacity-80 group-hover:opacity-100 transition-opacity"></div>
        <div
          v-if="loadingCardId === schedule.id"
          class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex items-center justify-center"
        >
          <div
            class="animate-spin rounded-full h-8 w-8 border-4 border-gray-100 border-t-brand-red"
          ></div>
        </div>
        <div
          class="absolute left-0 top-0 bottom-0 w-1.5 bg-brand-red opacity-80 group-hover:opacity-100 transition-opacity"
        ></div>

        <div class="flex justify-between items-start pl-2 mb-4">
          <div>
            <span
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-brand-red text-xs font-bold mb-3"
            >
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
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
              {{ formatTime(schedule.start_time) }} -
              {{ formatTime(schedule.end_time) }}
            </span>
            <h3
              class="text-xl sm:text-2xl font-bold text-gray-800 group-hover:text-brand-red transition-colors line-clamp-2"
            >
              {{ schedule.subject?.name || "Mata Pelajaran" }}
            </h3>
          </div>

          <div
            class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-brand-red group-hover:text-white transition-colors shrink-0"
          >
            <svg
              class="w-5 h-5"
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
          class="mt-auto pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between pl-2 gap-3"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-full bg-orange-100 text-brand-orange flex items-center justify-center font-bold text-sm border border-orange-200"
            >
              {{ schedule.teacher?.user?.name?.charAt(0) || "G" }}
            </div>
            <div>
              <p
                class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5"
              >
                Guru Pengajar
              </p>
              <p class="text-sm font-bold text-gray-700">
                {{ schedule.teacher?.user?.name || "-" }}
              </p>
            </div>
          </div>

          <div
            class="text-left sm:text-right bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100"
          >
            <p
              class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5"
            >
              {{ calculateDateForDay(selectedDay) }}
            </p>
            <p class="text-xs font-semibold text-brand-red">
              Masuk Kelas &rarr;
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
// Sesuaikan dengan letak service API Anda
import { studentScheduleService } from "../../services/modules/student/scheduleService";
import { useToastStore } from "../../stores/toast";
import BaseSelect from "../../components/BaseSelect.vue";
import { useStudentScheduleDetailStore } from '../../stores/studentScheduleDetail';

const studentDetailStore = useStudentScheduleDetailStore();
const loadingCardId = ref(null);

const router = useRouter();
const toastStore = useToastStore();

const isLoading = ref(true);
const schedules = ref([]);

const dayOptions = [
  { value: "monday", label: "Senin" },
  { value: "tuesday", label: "Selasa" },
  { value: "wednesday", label: "Rabu" },
  { value: "thursday", label: "Kamis" },
  { value: "friday", label: "Jumat" },
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

// Cari tau hari ini hari apa. Jika Sabtu/Minggu, alihkan otomatis ke Senin.
const getCurrentDay = () => {
  const today = dayNamesEng[new Date().getDay()];
  if (today === "saturday" || today === "sunday") return "monday";
  return today;
};

const selectedDay = ref(getCurrentDay());

const selectedDayLabel = computed(() => {
  const day = dayOptions.find((d) => d.value === selectedDay.value);
  return day ? day.label : "";
});

// Kalkulasi tanggal presisi (berguna untuk masuk ke mode Absen/Detail)
const calculateDateForDay = (targetDayString) => {
  const today = new Date();
  const currentDayIndex = today.getDay();
  const targetDayIndex = dayNamesEng.indexOf(targetDayString);

  let diff = targetDayIndex - currentDayIndex;

  const targetDate = new Date(today);
  targetDate.setDate(today.getDate() + diff);

  const y = targetDate.getFullYear();
  const m = String(targetDate.getMonth() + 1).padStart(2, "0");
  const d = String(targetDate.getDate()).padStart(2, "0");

  return `${y}-${m}-${d}`;
};

const formatTime = (timeString) => {
  if (!timeString) return "";
  return timeString.substring(0, 5); // Potong detik, 07:00:00 -> 07:00
};

// Ambil data dari server
const fetchSchedules = async () => {
  isLoading.value = true;
  try {
    const response = await studentScheduleService.getSchedules(
      selectedDay.value,
    );
    schedules.value = response.data.data;
  } catch (error) {
    console.error("Gagal memuat jadwal:", error);
    toastStore.error("Gagal memuat jadwal pelajaran.");
  } finally {
    isLoading.value = false;
  }
};

// Pindah ke halaman detail kelas (Siapkan rutenya di router.js nanti!)
const goToScheduleDetail = async (scheduleId) => {
  if (loadingCardId.value) return; // Cegah double-click

  const computedDate = calculateDateForDay(selectedDay.value);
  
  try {
    loadingCardId.value = scheduleId; // Aktifkan loading di kartu ini
    
    // Tarik data ke dalam Pinia Store terlebih dahulu
    await studentDetailStore.prefetchAllData(scheduleId, computedDate);
    
    // Pindah halaman dengan aman karena data dipastikan sudah matang di store
    router.push({ 
      path: `/student/schedules/${scheduleId}/detail`, 
      query: { date: computedDate } 
    });
  } catch (error) {
    toastStore.error("Gagal memuat detail kelas. Silakan coba lagi.");
  } finally {
    loadingCardId.value = null; // Matikan loading
  }
};

const getLocalTodayString = () => {
  const today = new Date();
  const y = today.getFullYear();
  const m = String(today.getMonth() + 1).padStart(2, '0');
  const d = String(today.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

// Computed property untuk mengecek apakah hari yang dipilih = hari ini
const isUnlocked = computed(() => {
  const selectedDateStr = calculateDateForDay(selectedDay.value);
  const todayStr = getLocalTodayString();
  
  // Tanggal yang dipilih KURANG DARI atau SAMA DENGAN hari ini (Masa lalu & Hari ini)
  return selectedDateStr <= todayStr;
});

const handleCardClick = (scheduleId) => {
  if (isUnlocked.value) {
    goToScheduleDetail(scheduleId);
  } else {
    toastStore.warning("Jadwal ini belum dimulai. Anda belum bisa memasuki ruang kelas ini.");
  }
};

onMounted(() => {
  fetchSchedules();
});
</script>
