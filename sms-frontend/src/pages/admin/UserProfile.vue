<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Detail Profil Pengguna</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan profil, histori akademik/jadwal, dan keamanan akun.</p>
      </div>
      <button
        @click="goBack"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors"
      >
        Kembali
      </button>
    </div>

    <div v-if="isLoading" class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 shadow-sm">
      Memuat data profil pengguna...
    </div>

    <div v-else-if="!user" class="bg-white border border-red-200 rounded-xl p-8 text-center text-brand-red shadow-sm">
      Data pengguna tidak ditemukan.
    </div>

    <template v-else>
      <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-full bg-brand-red/10 text-brand-red flex items-center justify-center text-2xl font-bold">
              {{ avatarInitial }}
            </div>

            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-800">{{ user.name }}</h2>
                <span :class="roleBadgeClass" class="px-3 py-1 rounded-full text-xs font-semibold border uppercase tracking-wide">
                  {{ roleLabel }}
                </span>
              </div>

              <p class="text-sm text-gray-600">{{ user.email }}</p>

              <div class="flex flex-wrap items-center gap-3 text-xs mt-2">
                <span class="px-2.5 py-1 rounded-md border border-gray-200 bg-gray-50 text-gray-700">
                  {{ identityLabel }}: {{ identityValue }}
                </span>
                <span class="px-2.5 py-1 rounded-md border border-gray-200 bg-gray-50 text-gray-700">
                  Status Akun: {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
                <span v-if="user.must_change_password" class="px-2.5 py-1 rounded-md border border-amber-200 bg-amber-50 text-amber-700">
                  Wajib ganti password saat login
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-4 py-3 overflow-x-auto">
          <div class="inline-flex bg-gray-100 rounded-lg p-1 gap-1 min-w-max">
            <button
              v-for="tab in visibleTabs"
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
          <div v-if="activeTab === 'general'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Informasi Umum</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Nama Lengkap</p>
                <p class="font-semibold text-gray-800">{{ user.name || '-' }}</p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Email</p>
                <p class="font-semibold text-gray-800">{{ user.email || '-' }}</p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Peran</p>
                <p class="font-semibold text-gray-800">{{ roleLabel }}</p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">{{ identityLabel }}</p>
                <p class="font-semibold text-gray-800">{{ identityValue }}</p>
              </div>

              <div v-if="user.role === 'student'" class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Jenis Kelamin</p>
                <p class="font-semibold text-gray-800">{{ genderLabel }}</p>
              </div>

              <div v-if="user.role === 'student'" class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Kelas Saat Ini</p>
                <p class="font-semibold text-gray-800">{{ currentClassLabel }}</p>
              </div>
            </div>

            <div v-if="user.role === 'student'" class="border border-gray-200 rounded-xl p-4">
              <p class="text-sm font-semibold text-gray-700 mb-3">Riwayat Kelas</p>
              <div v-if="classHistory.length === 0" class="text-sm text-gray-500">Belum ada riwayat kelas.</div>
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
                    <tr v-for="item in classHistory" :key="item.id">
                      <td class="py-2 pr-3 font-semibold text-gray-800">{{ item.name || '-' }}</td>
                      <td class="py-2 pr-3 text-gray-700">{{ item.academic_year?.name || '-' }}</td>
                      <td class="py-2 pr-3 text-gray-700 capitalize">{{ item.academic_year?.semester || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'academic'" class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <h3 class="text-lg font-bold text-gray-800">Riwayat Akademik</h3>

              <select
                v-model="selectedAcademicYear"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none"
              >
                <option value="all">Semua Tahun Ajaran / Semester</option>
                <option v-for="option in academicYearOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div v-if="filteredGradeHistory.length === 0" class="p-6 text-sm text-gray-500 text-center">
                Belum ada data nilai pada filter yang dipilih.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-left text-gray-600 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3">Kelas</th>
                      <th class="px-4 py-3">Tahun Ajaran</th>
                      <th class="px-4 py-3">Semester</th>
                      <th class="px-4 py-3 text-right">Nilai Akhir</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="grade in filteredGradeHistory" :key="grade.grade_id">
                      <td class="px-4 py-3 font-semibold text-gray-800">{{ grade.subject_name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ grade.class_name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ grade.academic_year_name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700 capitalize">{{ grade.semester || '-' }}</td>
                      <td class="px-4 py-3 text-right">
                        <span class="px-2.5 py-1 rounded-md bg-green-50 text-green-700 border border-green-200 font-bold">
                          {{ formatScore(grade.score) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'teaching'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Mengajar</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div v-if="teacherSchedules.length === 0" class="p-6 text-sm text-gray-500 text-center">
                Belum ada jadwal mengajar untuk pengguna ini.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 text-left text-gray-600 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3">Hari</th>
                      <th class="px-4 py-3">Jam</th>
                      <th class="px-4 py-3">Kelas</th>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3">Tahun Ajaran</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="schedule in teacherSchedules" :key="schedule.id">
                      <td class="px-4 py-3 font-semibold text-gray-800">{{ formatDay(schedule.day_of_week) }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ schedule.school_class?.name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ schedule.subject?.name || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ schedule.academic_year?.name || '-' }}
                        <span v-if="schedule.academic_year?.semester" class="capitalize">({{ schedule.academic_year.semester }})</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'security'" class="space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Keamanan Akun</h3>

            <div class="border border-red-300 bg-red-50 rounded-xl p-5">
              <p class="text-sm font-semibold text-brand-red">Danger Zone</p>
              <p class="text-sm text-red-700 mt-1">
                Reset password akan mengembalikan password user ke default sistem, dan user wajib mengganti password saat login kembali.
              </p>

              <button
                @click="handleResetPassword"
                :disabled="isResetting"
                class="mt-4 px-4 py-2.5 bg-brand-red hover:bg-brand-orange text-white rounded-lg text-sm font-semibold transition-colors disabled:opacity-70"
              >
                {{ isResetting ? 'Memproses reset...' : 'Reset Password ke Default' }}
              </button>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { userService } from '../../services/modules/admin/userService';
import { useToastStore } from '../../stores/toast';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

const user = ref(null);
const isLoading = ref(true);
const isResetting = ref(false);
const activeTab = ref('general');
const selectedAcademicYear = ref('all');

const visibleTabs = computed(() => {
  const tabs = [{ key: 'general', label: 'Informasi Umum' }];

  if (user.value?.role === 'student') {
    tabs.push({ key: 'academic', label: 'Riwayat Akademik' });
  }

  if (user.value?.role === 'teacher') {
    tabs.push({ key: 'teaching', label: 'Jadwal Mengajar' });
  }

  tabs.push({ key: 'security', label: 'Keamanan Akun' });

  return tabs;
});

const avatarInitial = computed(() => {
  if (!user.value?.name) {
    return '?';
  }

  return user.value.name.charAt(0).toUpperCase();
});

const roleLabel = computed(() => {
  const roleMap = {
    student: 'Siswa',
    teacher: 'Guru',
    admin: 'Admin',
    principal: 'Kepala Sekolah',
  };

  return roleMap[user.value?.role] || user.value?.role || '-';
});

const roleBadgeClass = computed(() => {
  const roleColorMap = {
    student: 'bg-green-50 text-green-700 border-green-200',
    teacher: 'bg-blue-50 text-blue-700 border-blue-200',
    admin: 'bg-purple-50 text-purple-700 border-purple-200',
    principal: 'bg-orange-50 text-orange-700 border-orange-200',
  };

  return roleColorMap[user.value?.role] || 'bg-gray-50 text-gray-700 border-gray-200';
});

const identityLabel = computed(() => {
  if (user.value?.role === 'student') {
    return 'NIS / NISN';
  }

  return 'NIP';
});

const identityValue = computed(() => {
  if (!user.value) {
    return '-';
  }

  if (user.value.role === 'student') {
    const nis = user.value.student?.nis || '-';
    const nisn = user.value.student?.nisn || '-';
    return `${nis} / ${nisn}`;
  }

  if (user.value.role === 'teacher') {
    return user.value.teacher?.nip || '-';
  }

  if (user.value.role === 'admin') {
    return user.value.admin?.nip || '-';
  }

  if (user.value.role === 'principal') {
    return user.value.principal?.nip || '-';
  }

  return '-';
});

const genderLabel = computed(() => {
  if (user.value?.student?.gender === 'L') {
    return 'Laki-laki';
  }

  if (user.value?.student?.gender === 'P') {
    return 'Perempuan';
  }

  return '-';
});

const classHistory = computed(() => {
  const classes = user.value?.student?.classes || [];

  return [...classes].sort((a, b) => {
    const aYear = Number(a.pivot?.academic_year_id || 0);
    const bYear = Number(b.pivot?.academic_year_id || 0);
    return bYear - aYear;
  });
});

const currentClassLabel = computed(() => {
  if (classHistory.value.length === 0) {
    return '-';
  }

  const activeByYear = classHistory.value.find((item) => item.academic_year?.is_active);

  if (activeByYear) {
    return activeByYear.name;
  }

  return classHistory.value[0]?.name || '-';
});

const gradeHistory = computed(() => user.value?.grade_history || []);

const academicYearOptions = computed(() => {
  const optionMap = new Map();

  gradeHistory.value.forEach((grade) => {
    if (!grade.academic_year_id) {
      return;
    }

    const semesterLabel = grade.semester ? ` (${grade.semester})` : '';
    optionMap.set(
      String(grade.academic_year_id),
      `${grade.academic_year_name || 'Tahun Ajaran'}${semesterLabel}`,
    );
  });

  classHistory.value.forEach((cls) => {
    const id = cls.pivot?.academic_year_id;
    if (!id || optionMap.has(String(id))) {
      return;
    }

    const semesterLabel = cls.academic_year?.semester ? ` (${cls.academic_year.semester})` : '';
    optionMap.set(String(id), `${cls.academic_year?.name || 'Tahun Ajaran'}${semesterLabel}`);
  });

  return Array.from(optionMap.entries()).map(([value, label]) => ({ value, label }));
});

const filteredGradeHistory = computed(() => {
  if (selectedAcademicYear.value === 'all') {
    return gradeHistory.value;
  }

  return gradeHistory.value.filter(
    (grade) => String(grade.academic_year_id) === String(selectedAcademicYear.value),
  );
});

const teacherSchedules = computed(() => user.value?.teacher?.schedules || []);

const formatTime = (time) => {
  if (!time) {
    return '-';
  }

  return String(time).slice(0, 5);
};

const formatScore = (score) => {
  if (score === null || score === undefined) {
    return '-';
  }

  return Number(score).toFixed(2);
};

const formatDay = (day) => {
  const dayMap = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
  };

  return dayMap[day] || day || '-';
};

const normalizeActiveTab = () => {
  const available = visibleTabs.value.map((tab) => tab.key);

  if (!available.includes(activeTab.value)) {
    activeTab.value = 'general';
  }
};

const fetchUser = async () => {
  isLoading.value = true;

  try {
    const { data } = await userService.getById(route.params.id);
    user.value = data;
    normalizeActiveTab();

    if (selectedAcademicYear.value !== 'all') {
      const stillExists = academicYearOptions.value.some(
        (option) => option.value === selectedAcademicYear.value,
      );

      if (!stillExists) {
        selectedAcademicYear.value = 'all';
      }
    }
  } catch {
    user.value = null;
    toastStore.error('Gagal memuat detail pengguna.');
  } finally {
    isLoading.value = false;
  }
};

const handleResetPassword = async () => {
  const confirmed = window.confirm(
    'Yakin mereset password user ini? Password akan dikembalikan ke default dan user wajib mengganti password saat login kembali.',
  );

  if (!confirmed) {
    return;
  }

  isResetting.value = true;

  try {
    const { data } = await userService.resetPassword(route.params.id);
    toastStore.success(data?.message || 'Password berhasil direset ke default.');
    await fetchUser();
  } catch {
    toastStore.error('Reset password gagal diproses.');
  } finally {
    isResetting.value = false;
  }
};

const goBack = () => {
  router.push('/admin/users');
};

watch(
  () => route.params.id,
  () => {
    fetchUser();
  },
);

onMounted(fetchUser);
</script>
