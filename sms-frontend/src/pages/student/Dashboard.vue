<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-[23px] lg:text-3xl font-bold text-gray-800 font-serif">
          Halo, {{ authStore.user?.name || "Siswa" }}! 👋
        </h1>
        <p class="text-xs lg:text-sm text-gray-500 mt-1">
          Siap untuk belajar hal baru hari ini? Cek jadwal dan tugasmu.
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
      <div class="h-40 bg-gray-200 rounded-3xl md:col-span-3"></div>
      <div class="h-64 bg-gray-200 rounded-3xl md:col-span-2"></div>
      <div class="h-64 bg-gray-200 rounded-3xl"></div>
    </div>

    <template v-else>
      <div v-if="!hasActiveRoute" class="bg-red-50 border-2 border-red-200 rounded-3xl p-8 md:p-12 text-center shadow-sm relative overflow-hidden">
        <div class="relative z-10 flex flex-col items-center">
          <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center mb-5 border border-red-200 shadow-sm">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          </div>
          <h2 class="text-2xl md:text-3xl font-bold text-red-800 mb-3 font-serif">Oops! Kamu Belum Mendapat Kelas</h2>
          <p class="text-red-600 md:text-lg max-w-2xl">
            Sistem mendeteksi akunmu masih aktif, tetapi <strong>belum ditempatkan di kelas manapun</strong>.
          </p>
        </div>
      </div>

      <div v-else class="space-y-6">
        
        <section class="bg-gradient-to-r from-brand-orange to-brand-red rounded-3xl p-6 md:p-8 text-white shadow-md relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-6">
          <svg class="absolute top-0 right-0 translate-x-1/4 -translate-y-1/4 opacity-10 w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
          
          <div class="relative z-10 flex items-center gap-5 w-full">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30 shrink-0">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
              <span class="inline-block px-2.5 py-1 bg-white/20 rounded-lg text-xs font-bold tracking-wide uppercase mb-1">Ruang Kelas Aktif</span>
              <h2 class="text-2xl md:text-3xl font-bold font-serif">{{ className }}</h2>
              
              <div class="mt-2 text-orange-100 text-sm flex flex-col sm:flex-row sm:gap-4 font-medium">
                <p>Wali Kelas: <span class="font-bold text-white">{{ dashboardData.homeroomClass?.homeroom_teacher_name }}</span></p>
                <p class="hidden sm:block">•</p>
                <p>{{ dashboardData.homeroomClass?.total_students }} Siswa Terdaftar</p>
              </div>
            </div>
          </div>
          
          <div class="relative z-10 w-full md:w-auto shrink-0">
            <button @click="$router.push('/student/class-detail')" class="w-full md:w-auto px-6 py-3 bg-white text-brand-red hover:bg-red-50 font-bold rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              Lihat Kelas
            </button>
          </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <div class="space-y-6">
            <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm flex flex-col h-90">
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-800">Jadwal Terdekat Hari Ini</h3>
                <button @click="$router.push('/student/schedules')" class="text-sm text-brand-red font-semibold hover:underline">Lihat Semua</button>
              </div>

              <div v-if="upcomingSchedules.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-6 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mb-3 shadow-sm text-green-500">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <p class="font-semibold text-gray-700">Tidak Ada Jadwal Tersisa</p>
                <p class="text-xs text-gray-500 mt-1">Sesi belajarmu hari ini sudah selesai.</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="schedule in upcomingSchedules" :key="schedule.id" 
                     class="flex items-center gap-4 p-4 border border-gray-100 rounded-2xl hover:border-brand-red/30 transition-colors group cursor-pointer">
                  <div class="flex flex-col items-center justify-center px-3 py-1.5 bg-gray-50 group-hover:bg-red-50 rounded-xl shrink-0 transition-colors">
                    <span class="text-sm font-bold text-gray-800 group-hover:text-brand-red">{{ formatTime(schedule.start_time) }}</span>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-bold text-gray-800 line-clamp-1 group-hover:text-brand-red transition-colors">{{ schedule.subject_name }}</h4>
                    <p class="text-xs text-gray-500 mt-0.5">
                      Guru: <span class="font-semibold text-gray-700">{{ schedule.teacher_name }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-800">Baru Saja Dinilai</h3>
                <button @click="$router.push({ name: 'StudentReport' })" class="text-sm text-brand-orange font-semibold hover:underline">Rekap Nilai</button>
              </div>

              <div v-if="recentGrades.length === 0" class="text-center py-6 text-sm text-gray-500 bg-gray-50 rounded-2xl border border-gray-100">
                Belum ada tugas yang dinilai baru-baru ini.
              </div>

              <div v-else class="space-y-3">
                <div v-for="sub in recentGrades" :key="sub.id" 
                     @click="goToAssignment(sub.assignment_id)"
                     class="flex items-center justify-between p-4 bg-gray-50 hover:bg-white border border-transparent hover:border-brand-orange/30 rounded-2xl transition-all cursor-pointer group">
                  <div>
                    <h4 class="text-sm font-bold text-gray-800 line-clamp-1 group-hover:text-brand-orange transition-colors">{{ sub.assignment_title }}</h4>
                    <p class="text-xs text-gray-500 mt-1">{{ sub.subject_name }}</p>
                  </div>
                  <div class="shrink-0 flex items-center justify-center w-12 h-12 bg-transparent font-black text-lg"
                       :class="getScoreColor(sub.grade?.score)">
                    {{ sub.grade?.score }}
                  </div>
                </div>
              </div>
            </section>
          </div>

          <div class="space-y-6">
            <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm h-90 flex flex-col">
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-800">Tenggat Waktu Terdekat</h3>
                <span class="px-2.5 py-1 bg-brand-orange/10 text-brand-orange text-xs font-bold rounded-lg">{{ deadlineAssignments.length }} Tugas</span>
              </div>

              <div v-if="deadlineAssignments.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-10 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm border border-gray-100">
                  <svg class="w-8 h-8 text-brand-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="font-bold text-gray-700">Aman Terkendali!</p>
                <p class="text-sm text-gray-500 mt-1">Tidak ada tugas yang menunggu untuk dikerjakan.</p>
              </div>

              <div v-else class="space-y-4">
                <div v-for="task in deadlineAssignments" :key="task.id" 
                     class="p-4 border-2 rounded-2xl hover:shadow-md transition-all relative overflow-hidden"
                     :class="getUrgencyClass(task.due_date).border">
                  
                  <div class="absolute top-0 left-0 h-1 w-full" :class="getUrgencyClass(task.due_date).bg"></div>

                  <div class="flex justify-between items-start gap-3 mt-1">
                    <div>
                      <h4 class="font-bold text-gray-800 line-clamp-2">{{ task.title }}</h4>
                      <p class="text-xs text-gray-500 font-medium mt-1">{{ task.subject_name }}</p>
                    </div>
                  </div>

                  <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs font-bold" :class="getUrgencyClass(task.due_date).text">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                      {{ formatTimeDistance(task.due_date) }}
                    </div>
                    
                    <button @click="goToAssignment(task.id)" 
                            class="px-4 py-1.5 bg-brand-red hover:bg-brand-orange text-white text-xs font-bold rounded-lg shadow-sm transition-colors">
                      Kerjakan
                    </button>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { studentDashboardService } from '../../services/modules/student/dashboardService';
import { useToastStore } from '../../stores/toast';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const isLoading = ref(true);
const hasActiveRoute = ref(true);
const className = ref('Kelas Kamu');

// Waktu Real-time
const timer = ref(null);
const currentDateTime = ref(new Date());

const dashboardData = ref({ academicYear: null, homeroomClass: null });
const schedulesToday = ref([]);
const deadlineAssignments = ref([]);
const recentGrades = ref([]);

const currentDate = computed(() => {
  return new Intl.DateTimeFormat("id-ID", { weekday: "long", year: "numeric", month: "long", day: "numeric" }).format(currentDateTime.value);
});

const currentTimeStr = computed(() => {
  const h = String(currentDateTime.value.getHours()).padStart(2, '0');
  const m = String(currentDateTime.value.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
});

const upcomingSchedules = computed(() => {
  const now = currentTimeStr.value;
  return schedulesToday.value
    .filter(s => formatTime(s.end_time) >= now)
    .sort((a, b) => formatTime(a.start_time).localeCompare(formatTime(b.start_time)))
    .slice(0, 3);
});

const formatTime = (timeStr) => timeStr ? timeStr.slice(0, 5) : '';

const getScoreColor = (score) => {
  if (!score) return 'text-gray-400';
  return score >= 70 ? 'text-green-600' : 'text-red-500';
};

const formatTimeDistance = (dueDate) => {
  if (!dueDate) return '';
  const diff = new Date(dueDate) - currentDateTime.value;
  if (diff < 0) return 'Waktu Habis';
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24));
  const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
  
  if (days > 0) return `${days} Hari lagi`;
  return `${hours} Jam lagi`;
};

const getUrgencyClass = (dueDate) => {
  const diff = new Date(dueDate) - currentDateTime.value;
  const hours = diff / (1000 * 60 * 60);
  
  if (hours <= 24) return { border: 'border-red-200 bg-red-50/30', bg: 'bg-red-500', text: 'text-red-600' };
  if (hours <= 72) return { border: 'border-amber-200 bg-amber-50/30', bg: 'bg-amber-500', text: 'text-amber-600' };
  return { border: 'border-gray-200', bg: 'bg-blue-400', text: 'text-gray-500' };
};

const goToAssignment = (id) => {
  router.push({ name: 'StudentAssignmentsList', query: { tab: 'graded' } });
};

const fetchDashboardData = async () => {
  isLoading.value = true;
  hasActiveRoute.value = true;

  try {
    const response = await studentDashboardService.getDashboardStats();
    const payload = response.data.data;

    // Masukkan data dari backend ke state Vue
    dashboardData.value.academicYear = payload.academic_year || null;
    dashboardData.value.homeroomClass = payload.homeroom_class;
    className.value = payload.homeroom_class?.name || 'Kelas Kamu';
    schedulesToday.value = payload.today_schedules || [];
    deadlineAssignments.value = payload.deadline_assignments || [];
    recentGrades.value = payload.recent_grades || [];

  } catch (error) {
    if (error.response && error.response.status === 403) {
      hasActiveRoute.value = false;
    } else {
      toastStore.error("Gagal memuat data dashboard.");
    }
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
  
  timer.value = setInterval(() => {
    currentDateTime.value = new Date();
  }, 60000);
});

onUnmounted(() => {
  if (timer.value) clearInterval(timer.value);
});
</script>