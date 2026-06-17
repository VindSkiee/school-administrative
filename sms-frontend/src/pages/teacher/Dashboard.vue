<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-[23px] lg:text-3xl font-bold text-gray-800 font-serif">
          Selamat datang, {{ authStore.user?.name || "Guru" }}!
        </h1>
        <p class="text-xs lg:text-sm text-gray-500 mt-1">
          Pantau jadwal mengajar harian dan aktivitas akademik Anda di sini.
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

    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
      <div class="h-32 bg-gray-200 rounded-2xl md:col-span-3"></div>
      <div class="h-24 bg-gray-200 rounded-2xl"></div>
      <div class="h-24 bg-gray-200 rounded-2xl"></div>
      <div class="h-24 bg-gray-200 rounded-2xl"></div>
    </div>

    <template v-else>
      <section
        v-if="dashboardData.homeroomClass"
        class="bg-gradient-to-r from-brand-red to-brand-orange rounded-2xl md:rounded-3xl p-4 sm:p-6 md:p-8 text-white shadow-md relative overflow-hidden"
      >
        <svg class="absolute top-0 right-0 translate-x-1/3 -translate-y-1/4 opacity-10 w-40 h-40 md:w-64 md:h-64" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 md:gap-6">
          <div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-5 text-center sm:text-left">
            <div class="w-14 h-14 sm:w-16 sm:h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30 shrink-0">
              <svg class="w-7 h-7 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <div>
              <span class="inline-block px-2 py-1 bg-white/20 rounded-lg text-[10px] sm:text-xs font-bold tracking-wide uppercase mb-1 backdrop-blur-sm">Wali Kelas</span>
              <h2 class="text-xl sm:text-2xl md:text-3xl font-bold font-serif break-words">Kelas {{ dashboardData.homeroomClass.name }}</h2>
              <p class="text-red-100 text-xs sm:text-sm md:text-[15px] mt-1">
                Tahun Ajaran Aktif • {{ dashboardData.homeroomClass.total_students }} Siswa Terdaftar
              </p>
            </div>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <button @click="goToClassDetail(dashboardData.homeroomClass.id)" class="w-full sm:w-auto px-5 py-2.5 bg-white text-brand-red font-semibold rounded-xl text-sm shadow-sm hover:bg-red-50 transition-colors">
              Lihat Detail Kelas
            </button>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4 h-full">
          <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium leading-tight mb-1">Sisa Jadwal Hari Ini</p>
            <p class="text-2xl font-bold text-gray-800">
              {{ remainingSchedulesCount }}
              <span class="text-sm font-normal text-gray-500">Sesi</span>
            </p>
            <p v-if="remainingSchedulesCount === 0" class="text-[11px] text-green-600 font-bold mt-1">Selesai untuk hari ini! 🎉</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4 h-full">
          <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500 font-medium leading-tight mb-1">Tugas Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800">
              {{ dashboardData.stats.pending_grading }}
              <span class="text-sm font-normal text-gray-500">Tugas</span>
            </p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start gap-4 h-full">
          <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <div class="w-full">
            <p class="text-sm text-gray-500 font-medium leading-tight mb-2">Kelas Diajar Hari Ini</p>
            <div v-if="classesToday.length === 0" class="text-xs text-gray-400">Tidak ada jadwal</div>
            <div v-else class="flex flex-wrap gap-1.5 mt-1">
              <span 
                v-for="(cls, idx) in classesToday" 
                :key="idx" 
                class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg"
              >
                {{ cls }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm flex flex-col h-[calc(68vh-120px)]">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Terdekat Hari Ini</h3>
            <button @click="goToSchedule" class="text-sm text-brand-red font-semibold hover:underline cursor-pointer">
              Lihat Jadwal Hari Ini
            </button>
          </div>

          <div v-if="!nextUpcomingSchedule" class="flex-1 flex flex-col items-center justify-center text-center py-8 text-gray-500 text-sm bg-gray-50 rounded-2xl border border-gray-100">
            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-gray-100">
              <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            <p class="font-semibold text-gray-700 text-base">Semua Selesai!</p>
            <p class="mt-1">Tidak ada jadwal tersisa hari ini. Selamat beristirahat.</p>
          </div>

          <div v-else class="flex-1">
            <div class="relative p-5 rounded-2xl border-2 transition-colors flex flex-col h-full"
                 :class="scheduleStatus.isOngoing ? 'border-brand-red bg-red-50/30' : 'border-gray-200 hover:border-brand-red/50'">
              
              <div class="absolute -top-3 left-4 px-3 py-1 text-xs font-bold uppercase rounded-full border shadow-sm"
                   :class="scheduleStatus.isOngoing ? 'bg-brand-red text-white border-brand-red animate-pulse' : 'bg-blue-100 text-blue-700 border-blue-200'">
                {{ scheduleStatus.text }}
              </div>

              <div class="mt-2 flex items-start gap-4">
                <div class="flex flex-col items-center justify-center px-4 py-2 bg-white rounded-xl shadow-sm border border-gray-100 shrink-0">
                  <span class="text-lg font-black text-gray-800">{{ formatTime(nextUpcomingSchedule.start_time) }}</span>
                  <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest my-0.5">s/d</span>
                  <span class="text-sm font-bold text-gray-500">{{ formatTime(nextUpcomingSchedule.end_time) }}</span>
                </div>
                
                <div class="flex-1">
                  <h4 class="text-xl font-bold text-gray-800 line-clamp-1">{{ nextUpcomingSchedule.subject_name }}</h4>
                  <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-600 font-medium">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                      Kelas {{ nextUpcomingSchedule.class_name }}
                    </span>
                    <span class="flex items-center gap-1.5">
                      <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                      {{ nextUpcomingSchedule.students_count }} Siswa
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="mt-auto pt-5">
                 <button @click="goToScheduleDetail(nextUpcomingSchedule.id)" class="w-full py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-xl text-sm transition-colors shadow-sm">
                   Buka Ruang Kelas
                 </button>
              </div>
            </div>
          </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm h-[calc(68vh-120px)] flex flex-col">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Menunggu Penilaian</h3>
          </div>

          <div v-if="dashboardData.pendingTasks.length === 0" class="text-center py-8 text-gray-500 text-sm">
            Wah, semua tugas sudah dinilai! Pekerjaan yang hebat.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="task in dashboardData.pendingTasks"
              :key="task.id"
              class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 border border-transparent hover:border-gray-200 transition-colors cursor-pointer"
              @click="$router.push({ name: 'TeacherAssignments' })"
            >
              <div>
                <h4 class="font-semibold text-gray-800 text-sm">{{ task.title }}</h4>
                <p class="text-xs text-gray-500 mt-1">{{ task.subject_name }} • Kelas {{ task.class_name }}</p>
              </div>
              <div class="text-right">
                <span class="inline-block px-2.5 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-lg mb-1">
                  {{ task.ungraded_count }} Menunggu
                </span>
              </div>
            </div>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useAuthStore } from "../../stores/auth";
import { teacherDashboardService } from "../../services/modules/teacher/dashboardService";
import { useToastStore } from "../../stores/toast";
import { useRouter } from "vue-router";

const authStore = useAuthStore();
const toastStore = useToastStore();
const isLoading = ref(true);
const router = useRouter();

// ================= WAKTU REAL-TIME =================
const timer = ref(null);
const currentDateTime = ref(new Date());

const currentDate = computed(() => {
  return new Intl.DateTimeFormat("id-ID", {
    weekday: "long", year: "numeric", month: "long", day: "numeric",
  }).format(currentDateTime.value);
});

// String Jam "HH:MM" (Misal: "08:30")
const currentTimeStr = computed(() => {
  const h = String(currentDateTime.value.getHours()).padStart(2, '0');
  const m = String(currentDateTime.value.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
});

// ================= DATA DASHBOARD =================
const dashboardData = ref({
  academicYear: null,
  homeroomClass: null,
  stats: { schedules_today: 0, pending_grading: 0, total_students_taught: 0 },
  todaySchedules: [],
  pendingTasks: [],
});

// ================= LOGIKA JADWAL PINTAR =================

// Helper Pemotong Jam
const formatTime = (timeStr) => {
  if (!timeStr) return "";
  return timeStr.slice(0, 5); // "08:00:00" -> "08:00"
};

// 1. Sisa Jadwal Hari Ini
const remainingSchedulesCount = computed(() => {
  const now = currentTimeStr.value;
  // Jadwal masih tersisa jika 'end_time'-nya belum terlewati
  return dashboardData.value.todaySchedules.filter(s => formatTime(s.end_time) >= now).length;
});

// 2. Daftar Unik Kelas yang Diajar Hari Ini
const classesToday = computed(() => {
  const classNames = dashboardData.value.todaySchedules.map(s => s.class_name);
  return [...new Set(classNames)]; // Menghapus duplikat
});

// 3. Jadwal Terdekat (Satu Saja)
const nextUpcomingSchedule = computed(() => {
  const now = currentTimeStr.value;
  // Cari semua jadwal yang belum berakhir
  let upcoming = dashboardData.value.todaySchedules.filter(s => formatTime(s.end_time) >= now);
  
  if (upcoming.length === 0) return null;
  
  // Urutkan berdasarkan waktu mulai dari yang paling pagi ke sore
  upcoming.sort((a, b) => formatTime(a.start_time).localeCompare(formatTime(b.start_time)));
  
  return upcoming[0]; // Ambil yang paling atas (terdekat)
});

// 4. Status Teks untuk Jadwal Terdekat (Ongoing vs Upcoming)
const scheduleStatus = computed(() => {
  if (!nextUpcomingSchedule.value) return { text: '', isOngoing: false };
  
  const now = currentTimeStr.value;
  const start = formatTime(nextUpcomingSchedule.value.start_time);
  const end = formatTime(nextUpcomingSchedule.value.end_time);

  if (now >= start && now <= end) {
    return { text: 'Sedang Berlangsung', isOngoing: true };
  }
  return { text: 'Segera Dimulai', isOngoing: false };
});


// ================= NAVIGASI =================
const goToClassDetail = (classId) => {
  if (!classId) return;
  router.push({ name: "TeacherClassDetail", params: { id: classId } });
};

const goToSchedule = () => {
  router.push({ name: "TeacherAttendance" });
};

// Navigasi cepat langsung ke ruang kelas dari Dashboard!
const goToScheduleDetail = (scheduleId) => {
  const todayYYYYMMDD = new Date().toISOString().split('T')[0];
  router.push({ 
    path: `/teacher/classes/${scheduleId}/detail`, 
    query: { date: todayYYYYMMDD } 
  });
};


// ================= FETCHING API =================
const fetchTeacherDashboard = async () => {
  isLoading.value = true;
  try {
    const response = await teacherDashboardService.getStats();
    const payload = response.data;

    dashboardData.value = {
      academicYear: payload.academic_year || null,
      homeroomClass: payload.homeroom_class || null,
      stats: {
        schedules_today: payload.stats?.schedules_today || 0,
        pending_grading: payload.stats?.pending_grading || 0,
        total_students_taught: payload.stats?.total_students_taught || 0,
      },
      todaySchedules: payload.today_schedules || [],
      pendingTasks: payload.pending_tasks || [],
    };
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal mengambil data dashboard guru");
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchTeacherDashboard();
  
  // Update Jam setiap 1 menit agar UI berubah otomatis (Live)
  timer.value = setInterval(() => {
    currentDateTime.value = new Date();
  }, 60000);
});

onUnmounted(() => {
  if (timer.value) clearInterval(timer.value);
});
</script>