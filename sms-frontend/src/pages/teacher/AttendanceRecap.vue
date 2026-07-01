<template>
  <div class="space-y-6">
    <div
      class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md"
    >
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div>
          <h1 class="text-2xl md:text-3xl font-bold font-serif">
            Rekap Kehadiran
          </h1>
          <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
            Pantau kehadiran di semua kelas dan perbaiki pertemuan yang
            terlewat.
          </p>
        </div>
        <BasePopoverInfo>
          <p class="font-bold text-gray-800 mb-2">Panduan Rekap Kehadiran</p>
          <ul class="space-y-1.5 list-disc list-inside">
            <li>
              <strong>Progress bar:</strong> Menunjukkan berapa pertemuan yang
              sudah diisi vs total pertemuan.
            </li>
            <li>
              <strong>Status kosong:</strong> Pertemuan yang belum diisi
              absensinya ditandai dengan badge kuning.
            </li>
            <li>
              <strong>Navigasi cepat:</strong> Klik tombol "Isi Absensi" pada
              pertemuan kosong untuk langsung ke form absensi.
            </li>
            <li>
              <strong>Urutan:</strong> Jadwal dengan pertemuan kosong paling
              banyak ditampilkan di atas.
            </li>
          </ul>
        </BasePopoverInfo>
      </div>
    </div>

    <div
      class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4 items-stretch sm:items-end w-full"
    >
      <div class="flex-1 min-w-0 w-full">
        <label
          class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1"
        >
          Tahun Ajaran
        </label>
        <BaseSelect
          v-model="selectedAcademicYearId"
          :options="academicYearOptions"
          placeholder="Pilih Tahun Ajaran"
        />
      </div>
    </div>

    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center py-16"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"
      ></div>
      <p class="text-gray-500 font-medium text-sm">Memuat rekap kehadiran...</p>
    </div>

    <div
      v-else-if="!selectedAcademicYearId"
      class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-200"
    >
      <div
        class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"
      >
        <svg
          class="w-10 h-10"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">Pilih Tahun Ajaran</h3>
      <p class="text-gray-500 mt-1 text-sm max-w-md mx-auto">
        Pilih tahun ajaran di atas untuk melihat rekap kehadiran semua jadwal
        mengajar Anda.
      </p>
    </div>

    <div
      v-else-if="sortedSchedules.length === 0"
      class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-200"
    >
      <div
        class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"
      >
        <svg
          class="w-10 h-10"
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
      <p class="text-gray-500 mt-1 text-sm max-w-md mx-auto">
        Tidak ditemukan jadwal mengajar untuk tahun ajaran ini.
      </p>
    </div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div
          v-for="schedule in sortedSchedules"
          :key="schedule.id"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col"
        >
          <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-medium text-gray-900 truncate">
                  {{ schedule.subject_name }}
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                  Kelas {{ schedule.class_name }} ·
                  {{ translateDay(schedule.day_of_week) }}
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span
                  v-if="schedule.missing_sessions > 0"
                  class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-medium rounded-md"
                >
                  {{ schedule.missing_sessions }} kosong
                </span>
                <span
                  class="px-2 py-0.5 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-medium tabular-nums rounded-md"
                >
                  {{ schedule.completed_sessions }}/{{
                    schedule.total_sessions
                  }}
                </span>
              </div>
            </div>

            <div class="mt-4">
              <div
                class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden"
              >
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="
                    schedule.missing_sessions > 0
                      ? 'bg-amber-400'
                      : 'bg-green-500'
                  "
                  :style="{
                    width:
                      schedule.total_sessions > 0
                        ? `${(schedule.completed_sessions / schedule.total_sessions) * 100}%`
                        : '0%',
                  }"
                ></div>
              </div>
              <p
                class="text-[11px] text-gray-500 mt-1.5 text-right tabular-nums"
              >
                {{ schedule.completed_sessions }} dari
                {{ schedule.total_sessions }} pertemuan terisi
              </p>
            </div>
          </div>

          <div
            class="p-2 space-y-1 max-h-64 overflow-y-auto custom-scrollbar bg-white"
          >
            <div
              v-for="session in schedule.sessions"
              :key="session.date"
              class="flex items-center justify-between p-2.5 rounded-lg transition-colors group hover:bg-gray-50"
              :class="getSessionBgClass(session)"
            >
              <div class="flex items-center gap-3 min-w-0">
                <svg
                  class="w-4 h-4 shrink-0"
                  :class="getSessionIcon(session).color"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    :d="getSessionIcon(session).path"
                  ></path>
                </svg>
                <div class="min-w-0">
                  <p
                    class="text-sm font-medium text-gray-900 truncate"
                    :class="getSessionTextClass(session)"
                  >
                    Pertemuan {{ session.meeting_number }}
                  </p>
                  <p
                    class="text-xs text-gray-500"
                    :class="getSessionTextClass(session)"
                  >
                    {{ formatDate(session.date) }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span
                  class="text-[11px] font-medium px-2 py-0.5 rounded-md"
                  :class="getStatusLabelClass(session)"
                >
                  {{ getStatusLabel(session) }}
                </span>
                <button
                  v-if="session.status === 'missing'"
                  @click="goToAttendance(schedule.id, session.date)"
                  class="px-2.5 py-1 bg-brand-red hover:bg-brand-red/90 text-white text-xs font-medium rounded-md transition-colors shadow-sm whitespace-nowrap"
                >
                  Isi Absensi
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useToastStore } from "../../stores/toast";
import { attendanceRecapService } from "../../services/modules/teacher/attendanceRecapService";
import api from "../../services/api";
import BaseSelect from "../../components/BaseSelect.vue";
import BasePopoverInfo from "../../components/BasePopoverInfo.vue";

const router = useRouter();
const toastStore = useToastStore();

const isLoading = ref(false);
const selectedAcademicYearId = ref("");
const schedules = ref([]);
const academicYears = ref([]);

const academicYearOptions = computed(() =>
  academicYears.value.map((y) => ({
    value: y.id,
    label: `${y.name || y.year || "Tahun Ajaran"} — Semester ${y.semester === "odd" ? "Ganjil" : "Genap"}${y.is_active ? " (Aktif)" : ""}`,
  })),
);

const sortedSchedules = computed(() => {
  return [...schedules.value].sort(
    (a, b) => b.missing_sessions - a.missing_sessions,
  );
});

const translateDay = (day) => {
  const days = {
    monday: "Senin",
    tuesday: "Selasa",
    wednesday: "Rabu",
    thursday: "Kamis",
    friday: "Jumat",
    saturday: "Sabtu",
  };
  return days[day] || day;
};

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr + "T00:00:00");
  const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];
  return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
};

const getSessionIcon = (session) => {
  switch (session.status) {
    case "completed":
      return {
        // Icon: Check Circle
        path: "M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
        color: "text-green-500",
      };
    case "holiday":
      return {
        // Icon: Sun (Merepresentasikan libur/hari cerah)
        path: "M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z",
        color: "text-yellow-500",
      };
    case "upcoming":
      return {
        // Icon: Calendar
        path: "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5",
        color: "text-blue-500",
      };
    default:
      return {
        // Icon: Exclamation Triangle (Warning)
        path: "M12 9v2.25m0 4.5h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z",
        color: "text-gray-400",
      };
  }
};

const getStatusLabel = (session) => {
  switch (session.status) {
    case "completed":
      return "Selesai";
    case "holiday":
      return "Libur";
    case "upcoming":
      return "Mendatang";
    default:
      return "Kosong";
  }
};

const getSessionBgClass = (session) => {
  switch (session.status) {
    case "completed":
      return "bg-green-50 border border-green-100";
    case "holiday":
      return "bg-yellow-50 border border-yellow-100";
    case "upcoming":
      return "bg-blue-50 border border-blue-100";
    default:
      return "bg-amber-50 border border-amber-200";
  }
};

const getSessionTextClass = (session) => {
  switch (session.status) {
    case "completed":
      return "text-green-800";
    case "holiday":
      return "text-yellow-800";
    case "upcoming":
      return "text-blue-800";
    default:
      return "text-amber-800";
  }
};

const getStatusLabelClass = (session) => {
  switch (session.status) {
    case "completed":
      return "bg-green-100 text-green-700";
    case "holiday":
      return "bg-yellow-100 text-yellow-700";
    case "upcoming":
      return "bg-blue-100 text-blue-700";
    default:
      return "bg-amber-100 text-amber-700";
  }
};

const goToAttendance = (scheduleId, date) => {
  router.push({
    name: "TeacherScheduleDetail",
    params: { schedule_id: scheduleId },
    query: { date, tab: "attendance" },
  });
};

const fetchRecap = async () => {
  if (!selectedAcademicYearId.value) {
    schedules.value = [];
    return;
  }

  isLoading.value = true;
  try {
    const response = await attendanceRecapService.getAll({
      academic_year_id: selectedAcademicYearId.value,
    });
    schedules.value = response.data?.data || [];
  } catch (error) {
    toastStore.error("Gagal memuat rekap kehadiran.");
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  try {
    const res = await api.get("/v1/teacher/gradebook/academic-years");
    academicYears.value = res.data?.data || res.data || [];
    const active = academicYears.value.find((ay) => ay.is_active);
    if (active) {
      selectedAcademicYearId.value = active.id;
    }
  } catch (error) {
    toastStore.error("Gagal memuat tahun ajaran.");
  }
});

watch(selectedAcademicYearId, () => {
  fetchRecap();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #d1d5db;
  border-radius: 9999px;
}
</style>
