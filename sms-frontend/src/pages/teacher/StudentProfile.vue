<template>
  <div class="space-y-6">
    <!-- Header -->
    <!-- Header -->
    <div class="flex items-center gap-3 md:gap-4 mb-2">
      <!-- Tombol Kembali Tunggal -->
      <button
        @click="goBack"
        class="p-2 text-gray-500 hover:text-brand-red hover:bg-red-50 rounded-full transition-all flex-shrink-0 group"
        title="Kembali ke halaman sebelumnya"
      >
        <svg class="w-6 h-6 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </button>
      
      <div class="flex-1 min-w-0">
        <h1 class="text-[23px] lg:text-3xl font-bold text-gray-800 font-serif">
          Profil Siswa
        </h1>
        <p class="text-xs lg:text-sm text-gray-500 mt-1">
          {{ isHomeroom ? 'Informasi lengkap siswa sebagai wali kelas.' : 'Data nilai dan kehadiran siswa pada mata pelajaran Anda.' }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white border border-gray-200 rounded-2xl p-8 text-center shadow-sm">
      <div class="inline-flex items-center gap-3 text-gray-500">
        <svg class="animate-spin h-5 w-5 text-brand-red" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        Memuat data profil siswa...
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="!student" class="bg-white border border-red-200 rounded-2xl p-8 text-center text-brand-red shadow-sm">
      Data siswa tidak ditemukan.
    </div>

    <template v-else>
      <!-- Student Info Card -->
      <section class="bg-white border border-gray-200 rounded-2xl p-4 sm:p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
          <div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 text-center sm:text-left">
            <!-- Avatar -->
            <div class="w-20 h-20 sm:w-28 sm:h-28 lg:w-36 lg:h-36 rounded-full flex items-center justify-center text-xl sm:text-2xl lg:text-5xl font-bold border-4 border-white shadow-sm shrink-0"
              :class="student.avatar_url ? '' : 'bg-brand-orange text-brand-white'"
            >
              <img v-if="student.avatar_url" :src="student.avatar_url" alt="Avatar"
                class="w-full h-full rounded-full object-cover" />
              <span v-else>{{ avatarInitial }}</span>
            </div>

            <!-- Info -->
            <div class="space-y-2">
              <div class="flex flex-col sm:flex-row sm:flex-wrap items-center gap-2">
                <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800">{{ student.name }}</h2>
                <span v-if="isHomeroom" class="px-2 py-1 sm:px-3 rounded-full text-[10px] sm:text-xs font-semibold border uppercase tracking-wide bg-orange-50 text-orange-700 border-orange-200">
                  Wali Kelas Anda
                </span>
              </div>
              <p class="text-xs sm:text-sm text-gray-600">
                NIS: {{ student.nis || '-' }} &middot; NISN: {{ student.nisn || '-' }}
              </p>
              <div class="flex flex-wrap justify-center sm:justify-start gap-2 text-[10px] sm:text-xs mt-2">
                <span v-if="student.class_name" class="px-2 py-1 sm:px-2.5 rounded-md border border-gray-200 bg-gray-50 text-gray-700">
                  Kelas: {{ student.class_name }}
                </span>
                <span class="px-2 py-1 sm:px-2.5 rounded-md border border-gray-200 bg-gray-50 text-gray-700">
                  {{ genderLabel }}
                </span>
                <span class="px-2 py-1 sm:px-2.5 rounded-md border"
                  :class="student.is_active ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-brand-red'">
                  {{ student.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Overall attendance badge (right side) -->
          <div v-if="student.overall_attendance?.total" class="text-center shrink-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Kehadiran Keseluruhan</p>
            <p class="text-3xl font-bold" :class="attendanceColor(student.overall_attendance.rate)">
              {{ student.overall_attendance.rate ?? '-' }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">
              {{ student.overall_attendance.present }}/{{ student.overall_attendance.total }} pertemuan
            </p>
          </div>
        </div>
      </section>

      <!-- Academic Year Filter (homeroom only) -->
      <div v-if="isHomeroom" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-72">
          <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun Ajaran</label>
          <BaseSelect
            v-model="selectedAcademicYearId"
            :options="academicYearOptions"
            placeholder="Pilih Tahun Ajaran"
            @update:modelValue="onAcademicYearChange"
          />
        </div>
      </div>

      <!-- Tabs -->
      <section class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-4 py-3 overflow-x-auto">
          <div class="inline-flex bg-gray-100 rounded-lg p-1 gap-1 min-w-max">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              class="px-4 py-2 text-sm font-semibold rounded-md transition-colors"
              :class="activeTab === tab.key ? 'bg-white text-brand-red shadow-sm' : 'text-gray-600 hover:text-gray-800'"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <div class="p-5 sm:p-6">
          <!-- TAB: Informasi Umum (homeroom only) -->
          <div v-if="activeTab === 'general'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Informasi Umum</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Nama Lengkap</p>
                <p class="font-semibold text-gray-800">{{ student.name || '-' }}</p>
              </div>
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Email</p>
                <p class="font-semibold text-gray-800">{{ student.email || '-' }}</p>
              </div>
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">NIS / NISN</p>
                <p class="font-semibold text-gray-800">{{ student.nis || '-' }} / {{ student.nisn || '-' }}</p>
              </div>
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Jenis Kelamin</p>
                <p class="font-semibold text-gray-800">{{ genderLabel }}</p>
              </div>
            </div>

            <!-- Class History -->
            <div class="border border-gray-200 rounded-xl p-4">
              <p class="text-sm font-semibold text-gray-700 mb-3">Riwayat Kelas</p>
              <div v-if="student.class_history?.length === 0" class="text-sm text-gray-500">Belum ada riwayat kelas.</div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="text-left text-gray-600 border-b border-gray-200">
                    <tr>
                      <th class="py-2 pr-3">Kelas</th>
                      <th class="py-2 pr-3">Tahun Ajaran</th>
                      <th class="py-2 pr-3">Semester</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in student.class_history" :key="item.id">
                      <td class="py-2 pr-3 font-semibold text-gray-800">{{ item.name || '-' }}</td>
                      <td class="py-2 pr-3 text-gray-700">{{ item.academic_year?.name || '-' }}</td>
                      <td class="py-2 pr-3 text-gray-700">{{ item.academic_year?.semester_label || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- TAB: Jadwal Pelajaran (homeroom only) -->
          <div v-else-if="activeTab === 'schedules'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Pelajaran</h3>
            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div v-if="student.schedules?.length === 0" class="p-6 text-sm text-gray-500 text-center">
                Belum ada jadwal untuk tahun ajaran ini.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-left text-gray-600 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3">Hari</th>
                      <th class="px-4 py-3">Jam</th>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3">Guru Pengajar</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="s in student.schedules" :key="s.id">
                      <td class="px-4 py-3 font-semibold text-gray-800">{{ formatDay(s.day_of_week) }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ formatTime(s.start_time) }} - {{ formatTime(s.end_time) }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ s.subject_name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">
                        <span :class="s.is_my_subject ? 'text-brand-red font-semibold' : ''">{{ s.teacher_name || '-' }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- TAB: Nilai & Tugas -->
          <div v-else-if="activeTab === 'grades'" class="space-y-5">
            <div class="flex items-center justify-between gap-3">
              <h3 class="text-lg font-bold text-gray-800">Nilai & Tugas</h3>
              <span v-if="!isHomeroom" class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">
                Hanya mata pelajaran yang Anda ajar
              </span>
            </div>

            <div v-if="student.subjects_grades?.length === 0" class="border border-gray-200 rounded-xl p-6 text-sm text-gray-500 text-center">
              Belum ada data nilai.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="subject in student.subjects_grades"
                :key="subject.schedule_id"
                class="border border-gray-200 rounded-xl overflow-hidden"
              >
                <!-- Subject Header -->
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div>
                    <p class="font-bold text-gray-800">{{ subject.subject_name }}</p>
                    <p class="text-xs text-gray-500">Guru: {{ subject.teacher_name || '-' }}</p>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <span class="text-xs text-gray-500">
                      {{ subject.graded_assignments }}/{{ subject.total_assignments }} dinilai
                    </span>
                    <span class="px-2.5 py-1 rounded-md font-bold text-sm"
                      :class="subject.average_score != null
                        ? (subject.average_score >= 75 ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-brand-red border border-red-200')
                        : 'bg-gray-100 text-gray-500 border border-gray-200'">
                      {{ subject.average_score != null ? subject.average_score.toFixed(1) : '-' }}
                    </span>
                  </div>
                </div>

                <!-- Assignment details (only for teacher's own subjects or homeroom) -->
                <div v-if="subject.assignments?.length > 0" class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="text-left text-gray-500 border-b border-gray-100">
                      <tr>
                        <th class="px-4 py-2 w-[40%]">Tugas</th>
                        <th class="px-4 py-2 w-[15%] text-center">Jenis</th>
                        <th class="px-4 py-2 w-[25%]">Tenggat</th>
                        <th class="px-4 py-2 w-[20%] text-center">Nilai</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="a in subject.assignments" :key="a.id">
                        <td class="px-4 py-2 font-medium text-gray-800">{{ a.title }}</td>
                        <td class="px-4 py-2 text-center">
                          <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                            :class="typeBadgeClass(a.type)">
                            {{ typeLabel(a.type) }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-gray-600">{{ formatDate(a.due_date) }}</td>
                        <td class="px-4 py-2 text-center">
                          <span v-if="a.score != null" class="font-bold" :class="a.score >= 75 ? 'text-green-700' : 'text-brand-red'">
                            {{ a.score }}
                          </span>
                          <span v-else class="text-gray-400">-</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: Kehadiran -->
          <div v-else-if="activeTab === 'attendance'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Ringkasan Kehadiran</h3>

            <!-- Overall -->
            <div v-if="student.overall_attendance?.total" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="border border-gray-200 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Total Pertemuan</p>
                <p class="text-2xl font-bold text-gray-800">{{ student.overall_attendance.total }}</p>
              </div>
              <div class="border border-gray-200 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Hadir</p>
                <p class="text-2xl font-bold text-green-700">{{ student.overall_attendance.present }}</p>
              </div>
              <div class="border border-gray-200 rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">Persentase</p>
                <p class="text-2xl font-bold" :class="attendanceColor(student.overall_attendance.rate)">
                  {{ student.overall_attendance.rate ?? '-' }}%
                </p>
              </div>
            </div>

            <!-- Per subject -->
            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-left text-gray-600 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3 text-center">Pertemuan</th>
                      <th class="px-4 py-3 text-center">Hadir</th>
                      <th class="px-4 py-3 text-right">Persentase</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="subject in attendanceSubjects" :key="subject.schedule_id">
                      <td class="px-4 py-3 font-semibold text-gray-800">{{ subject.subject_name }}</td>
                      <td class="px-4 py-3 text-center text-gray-700">{{ subject.attendance.total }}</td>
                      <td class="px-4 py-3 text-center text-gray-700">{{ subject.attendance.present }}</td>
                      <td class="px-4 py-3 text-right">
                        <span class="font-bold" :class="attendanceColor(subject.attendance.rate)">
                          {{ subject.attendance.rate ?? '-' }}%
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';
import { teacherStudentService } from '../../services/modules/teacher/studentService';
import BaseSelect from '../../components/BaseSelect.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

const student = ref(null);
const isLoading = ref(true);
const selectedAcademicYearId = ref('');
const activeTab = ref('general');

// ─── Computed ───

const isHomeroom = computed(() => student.value?.is_homeroom === true);

const avatarInitial = computed(() => {
  return student.value?.name ? student.value.name.charAt(0).toUpperCase() : 'S';
});

const genderLabel = computed(() => {
  if (student.value?.gender === 'L') return 'Laki-laki';
  if (student.value?.gender === 'P') return 'Perempuan';
  return '-';
});

const academicYearOptions = computed(() => {
  return (student.value?.academic_years || []).map(y => ({
    value: y.id,
    label: `${y.name} (${y.semester_label})${y.is_active ? ' - Aktif' : ''}`,
  }));
});

const tabs = computed(() => {
  if (isHomeroom.value) {
    return [
      { key: 'general', label: 'Informasi Umum' },
      { key: 'schedules', label: 'Jadwal Pelajaran' },
      { key: 'grades', label: 'Nilai & Tugas' },
      { key: 'attendance', label: 'Kehadiran' },
    ];
  }
  return [
    { key: 'grades', label: 'Nilai & Tugas' },
    { key: 'attendance', label: 'Kehadiran' },
  ];
});

// For attendance tab, show all subjects (homeroom) or only teacher's subjects (non-homeroom)
const attendanceSubjects = computed(() => {
  return student.value?.subjects_grades || [];
});

// ─── Helpers ───

const formatDay = (day) => {
  const map = { monday: 'Senin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Kamis', friday: 'Jumat', saturday: 'Sabtu' };
  return map[day] || day || '-';
};

const formatTime = (t) => t ? String(t).slice(0, 5) : '-';

const formatDate = (d) => {
  if (!d) return '-';
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(d));
};

const typeLabel = (type) => {
  const map = { task: 'Tugas', uts: 'UTS', uas: 'UAS' };
  return map[type] || type || '-';
};

const typeBadgeClass = (type) => {
  const map = {
    task: 'bg-blue-50 text-blue-700 border border-blue-200',
    uts: 'bg-orange-50 text-orange-700 border border-orange-200',
    uas: 'bg-red-50 text-red-700 border border-red-200',
  };
  return map[type] || 'bg-gray-100 text-gray-700 border border-gray-200';
};

const attendanceColor = (rate) => {
  if (rate == null) return 'text-gray-500';
  if (rate >= 90) return 'text-green-700';
  if (rate >= 75) return 'text-orange-600';
  return 'text-brand-red';
};

// ─── Data Fetching ───

const fetchStudent = async (yearId = null) => {
  isLoading.value = true;
  try {
    const { data } = await teacherStudentService.getStudentProfile(route.params.id, yearId);
    student.value = data;

    // Set initial academic year selection
    if (!selectedAcademicYearId.value && data.selected_academic_year_id) {
      selectedAcademicYearId.value = data.selected_academic_year_id;
    }

    // Normalize active tab
    const available = tabs.value.map(t => t.key);
    if (!available.includes(activeTab.value)) {
      activeTab.value = available[0];
    }
  } catch (error) {
    console.error('Gagal memuat profil siswa:', error);
    toastStore.error('Gagal memuat data profil siswa.');
    student.value = null;
  } finally {
    isLoading.value = false;
  }
};

const onAcademicYearChange = (yearId) => {
  fetchStudent(yearId);
};

// Back to attendance schedule list
const goToAttendance = () => {
  router.push('/teacher/schedules/today');
};

// Navigate to the student's class detail page via their first schedule
const goToClassDetail = () => {
  const firstSchedule = student.value?.schedules?.[0];
  if (firstSchedule?.id) {
    router.push(`/teacher/classes/${firstSchedule.id}/detail`);
  } else {
    router.push('/teacher/schedules/today');
  }
};

const goBack = () => {
  // Cek apakah ada riwayat halaman sebelumnya di browser
  if (window.history.state && window.history.state.back) {
    router.back();
  } else {
    // Fallback: Jika halaman dibuka di tab baru, kembalikan ke dashboard guru
    router.push('/teacher/dashboard'); 
  }
};

// ─── Lifecycle ───
onMounted(() => fetchStudent());

// Proteksi Watcher: Hindari error 404 saat menekan tombol back/pindah halaman
watch(
  () => route.params.id, 
  (newId) => {
    // Sesuaikan 'Profil Siswa' dengan name yang ada di Vue Router kamu untuk halaman ini
    if (route.name === 'Profil Siswa' && newId) {
      fetchStudent();
    }
  }
);
</script>
