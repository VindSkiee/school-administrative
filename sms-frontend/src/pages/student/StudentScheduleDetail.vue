<template>
  <div class="space-y-6 pb-12">
    <div v-if="isPageLoading" class="flex flex-col justify-center items-center py-24 bg-white rounded-3xl shadow-sm border border-gray-200">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
      <p class="text-gray-500 font-bold">Memuat rincian kelas...</p>
    </div>

    <template v-else>
      <div class="bg-white p-5 md:p-8 rounded-3xl shadow-sm border border-gray-200 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        
        <div class="flex items-start gap-4 w-full lg:w-auto">
          <button @click="goBack" class="mt-1 text-gray-400 hover:text-brand-red transition-colors flex-shrink-0 bg-gray-50 hover:bg-red-50 p-2 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </button>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span class="px-2.5 py-1 bg-red-50 text-brand-red text-xs font-bold rounded-lg uppercase tracking-wider">
                Kelas {{ scheduleInfo.school_class?.name || "..." }}
              </span>
              <span class="text-sm font-semibold text-gray-500">
                {{ formatTime(scheduleInfo.start_time) }} - {{ formatTime(scheduleInfo.end_time) }}
              </span>
              <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg uppercase tracking-wider">
                {{ scheduleDayNameId }}
              </span>
            </div>
            
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 font-serif break-words mb-2">
              {{ scheduleInfo.subject?.name || "Memuat Mapel..." }}
            </h1>

            <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              Pengajar: <span class="font-bold text-gray-700">{{ scheduleInfo.teacher?.user?.name || 'Menunggu Info' }}</span>
            </div>
          </div>
        </div>

        <div class="w-full lg:w-auto bg-gray-50 p-4 rounded-2xl border border-gray-100 text-left sm:text-center flex flex-row lg:flex-col items-center justify-between lg:justify-center gap-2">
          <div class="flex items-center gap-2 lg:justify-center text-gray-500">
            <svg class="w-5 h-5 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-xs font-bold uppercase tracking-wider">Tanggal Sesi</span>
          </div>
          <p class="text-sm md:text-base font-bold text-gray-800">{{ displayDate }}</p>
        </div>
      </div>

      <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="w-full px-4 py-3 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2"
            :class="activeTab === tab.id ? 'bg-brand-red text-white shadow-sm' : 'bg-gray-50 text-gray-500 hover:text-gray-800 hover:bg-gray-100'"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <div class="transition-all duration-300">
        
        <div v-if="activeTab === 'attendance'">
          <StudentAttendancePanel 
            :scheduleId="route.params.id || route.params.schedule_id" 
            :selectedDate="globalDate" 
          />
        </div>
        
        <div v-if="activeTab === 'materials'">
          <StudentMaterialPanel 
            :scheduleId="route.params.id || route.params.schedule_id" 
            :selectedDate="globalDate" 
          />
        </div>
        <div v-if="activeTab === 'assignments'">
          <StudentAssignmentPanel 
            :scheduleId="route.params.id || route.params.schedule_id" 
            :selectedDate="globalDate" 
          />
        </div>

      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStudentScheduleDetailStore } from '../../stores/studentScheduleDetail';
import { studentScheduleService } from '../../services/modules/student/scheduleService';
import StudentAttendancePanel from './panel/StudentAttendancePanel.vue';
import StudentMaterialPanel from './panel/StudentMaterialPanel.vue';
import StudentAssignmentPanel from './panel/StudentAssignmentPanel.vue';

const studentDetailStore = useStudentScheduleDetailStore();
const route = useRoute();
const router = useRouter();

// Jika di URL ada ?tab=materials, langsung aktifkan tab tersebut!
const activeTab = ref(route.query.tab || 'attendance');

// Tetap simpan globalDate dari URL untuk di-passing ke Panel API
// Tapi kita TIDAK menampilkannya sebagai form input yang bisa diedit siswa
const globalDate = ref(route.query.date || new Date().toISOString().split('T')[0]);
const isPageLoading = ref(false);

const scheduleInfo = computed(() => studentDetailStore.scheduleInfo || {});

const tabs = [
  { id: 'attendance', label: 'Kehadiran Saya' },
  { id: 'materials', label: 'Materi Belajar' },
  { id: 'assignments', label: 'Tugas & Aktivitas' },
];

const dayMap = {
  sunday: "Minggu", monday: "Senin", tuesday: "Selasa",
  wednesday: "Rabu", thursday: "Kamis", friday: "Jumat", saturday: "Sabtu"
};

const scheduleDayNameId = computed(() => {
  return dayMap[scheduleInfo.value?.day_of_week?.toLowerCase()] || "...";
});

// Format Tanggal untuk Ditampilkan ke Siswa (Read-only)
const displayDate = computed(() => {
  if (!globalDate.value) return '-';
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric'
  }).format(new Date(globalDate.value));
});

const formatTime = (timeString) => {
  if (!timeString) return '';
  return timeString.substring(0, 5);
};

// Navigate back to schedule list with the same day pre-selected
const goBack = () => {
  const day = scheduleInfo.value?.day_of_week?.toLowerCase() || '';
  router.push({ path: '/student/schedules', query: day ? { day } : {} });
};

onMounted(async () => {
  // Gunakan route.params.id, dengan fallback ke schedule_id jika router Anda berbeda
  const scheduleId = route.params.id || route.params.schedule_id; 
  
  if (!scheduleInfo.value || Object.keys(scheduleInfo.value).length === 0) {
    isPageLoading.value = true;
    try {
      const res = await studentScheduleService.getScheduleDetail(scheduleId);
      studentDetailStore.scheduleInfo = res.data.data;
    } catch (error) {
      router.push('/student/dashboard');
    } finally {
      isPageLoading.value = false;
    }
  }
});

onUnmounted(() => {
  studentDetailStore.clearData();
});
</script>