<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
      <div>
        <button
          @click="goBack"
          class="inline-flex items-center px-4 py-2 mb-4 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>

        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Detail Kelas {{ classData?.name || '-' }}
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          {{ academicYearLabel }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center">
      <div class="inline-flex items-center gap-3 text-gray-600">
        <svg class="animate-spin h-5 w-5 text-brand-red" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Memuat detail kelas...
      </div>
    </div>

    <template v-else-if="classData">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
        <button
          @click="exportStudentsCsv"
          class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors"
        >
          Export Siswa CSV
        </button>
        <button
          @click="exportSchedulesCsv"
          class="inline-flex items-center justify-center px-4 py-2 bg-brand-red hover:bg-brand-orange text-white rounded-lg text-sm font-semibold transition-colors shadow-sm"
        >
          Export Jadwal CSV
        </button>
      </div>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Wali Kelas</p>
          <div v-if="classData.homeroom_teacher?.user?.id" class="space-y-1">
            <router-link
              :to="{ name: 'Detail Pengguna', params: { id: classData.homeroom_teacher.user.id } }"
              class="inline-flex text-lg font-bold text-brand-red hover:text-brand-orange hover:underline transition-colors"
            >
              {{ classData.homeroom_teacher.user.name }}
            </router-link>
            <p class="text-sm text-gray-500">Klik untuk membuka profil user</p>
          </div>
          <span v-else class="inline-flex px-3 py-1 rounded-full bg-red-50 text-brand-red border border-red-200 text-xs font-semibold">
            Belum Set Wali Kelas
          </span>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Total Siswa</p>
          <p class="text-3xl font-bold text-gray-800">{{ studentsRows.length }} <span class="text-base font-semibold text-gray-500">Siswa</span></p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Total Jam Pelajaran</p>
          <p class="text-3xl font-bold text-gray-800">{{ schedulesRows.length }} <span class="text-base font-semibold text-gray-500">Jadwal</span></p>
        </div>
      </section>

      <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Siswa Laki-laki</p>
          <p class="text-3xl font-bold text-gray-800">{{ genderStats.male }} <span class="text-base font-semibold text-gray-500">Siswa</span></p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Siswa Perempuan</p>
          <p class="text-3xl font-bold text-gray-800">{{ genderStats.female }} <span class="text-base font-semibold text-gray-500">Siswa</span></p>
        </div>
      </section>

      <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
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
          <div v-if="activeTab === 'students'" class="space-y-4">
            <div class="flex items-center justify-between gap-3 flex-col sm:flex-row">
              <div>
                <h2 class="text-lg font-bold text-gray-800">Daftar Siswa</h2>
                <p class="text-sm text-gray-500">Siswa yang terdaftar di kelas ini sudah diurutkan A-Z.</p>
              </div>
            </div>

            <BaseTable
              :columns="studentColumns"
              :data="studentsRows"
              :isLoading="false"
              emptyMessage="Belum ada siswa di kelas ini."
            >
              <template #cell(nis)="{ item }">
                <span class="font-medium text-gray-700">{{ item.nis || '-' }}</span>
              </template>

              <template #cell(nisn)="{ item }">
                <span class="font-medium text-gray-700">{{ item.nisn || '-' }}</span>
              </template>

              <template #cell(name)="{ item }">
                <router-link
                  v-if="item.user?.id"
                  :to="{ name: 'Detail Pengguna', params: { id: item.user.id } }"
                  class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
                >
                  {{ item.user?.name || '-' }}
                </router-link>
                <span v-else class="font-medium text-gray-700">{{ item.user?.name || '-' }}</span>
              </template>

              <template #cell(gender)="{ item }">
                <span
                  :class="genderBadgeClass(item.gender)"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border"
                >
                  {{ formatGender(item.gender) }}
                </span>
              </template>
            </BaseTable>
          </div>

          <div v-else-if="activeTab === 'schedules'" class="space-y-4">
            <div>
              <h2 class="text-lg font-bold text-gray-800">Jadwal Pelajaran Kelas</h2>
              <p class="text-sm text-gray-500">Urutan jadwal disusun berdasarkan hari dan jam mulai.</p>
            </div>

            <BaseTable
              :columns="scheduleColumns"
              :data="schedulesRows"
              :isLoading="false"
              emptyMessage="Belum ada jadwal pelajaran untuk kelas ini."
            >
              <template #cell(day)="{ item }">
                <span class="font-semibold text-gray-800">{{ translateDay(item.day_of_week) }}</span>
              </template>

              <template #cell(time)="{ item }">
                <span class="font-medium text-gray-700">{{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</span>
              </template>

              <template #cell(subject)="{ item }">
                <span class="font-semibold text-brand-red">{{ item.subject?.name || '-' }}</span>
              </template>

              <template #cell(teacher)="{ item }">
                <router-link
                  v-if="item.teacher?.user?.id"
                  :to="{ name: 'Detail Pengguna', params: { id: item.teacher.user.id } }"
                  class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
                >
                  {{ item.teacher?.user?.name || '-' }}
                </router-link>
                <span v-else class="font-medium text-gray-700">{{ item.teacher?.user?.name || '-' }}</span>
              </template>
            </BaseTable>
          </div>
        </div>
      </section>
    </template>

    <div v-else class="bg-white rounded-2xl border border-red-200 shadow-sm p-8 text-center text-brand-red">
      Gagal memuat detail kelas.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';
import { classService } from '../../services/modules/admin/classService';
import BaseTable from '../../components/BaseTable.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

const classData = ref(null);
const isLoading = ref(true);
const activeTab = ref('students');

const tabs = [
  { key: 'students', label: 'Daftar Siswa' },
  { key: 'schedules', label: 'Jadwal Pelajaran Kelas' },
];

const studentColumns = [
  { key: 'nis', label: 'NIS' },
  { key: 'nisn', label: 'NISN' },
  { key: 'name', label: 'Nama Siswa' },
  { key: 'gender', label: 'Jenis Kelamin', align: 'center' },
];

const scheduleColumns = [
  { key: 'day', label: 'Hari' },
  { key: 'time', label: 'Waktu' },
  { key: 'subject', label: 'Mata Pelajaran' },
  { key: 'teacher', label: 'Guru Pengajar' },
];

const studentsRows = computed(() => {
  const rows = classData.value?.students || [];

  return [...rows].sort((a, b) => {
    const aName = a.user?.name || '';
    const bName = b.user?.name || '';
    return aName.localeCompare(bName, 'id', { sensitivity: 'base' });
  });
});

const schedulesRows = computed(() => {
  const rows = classData.value?.schedules || [];
  const dayRank = {
    monday: 1,
    tuesday: 2,
    wednesday: 3,
    thursday: 4,
    friday: 5,
    saturday: 6,
    sunday: 7,
  };

  return [...rows].sort((a, b) => {
    const dayDiff = (dayRank[a.day_of_week] || 99) - (dayRank[b.day_of_week] || 99);

    if (dayDiff !== 0) {
      return dayDiff;
    }

    return String(a.start_time || '').localeCompare(String(b.start_time || ''));
  });
});

const genderStats = computed(() => {
  return studentsRows.value.reduce(
    (summary, student) => {
      if (student.gender === 'L') {
        summary.male += 1;
      }

      if (student.gender === 'P') {
        summary.female += 1;
      }

      return summary;
    },
    { male: 0, female: 0 },
  );
});

const academicYearLabel = computed(() => {
  const academicYear = classData.value?.academic_year;
  if (!academicYear) {
    return '-';
  }

  const semesterLabel = academicYear.semester === 'odd'
    ? 'Ganjil'
    : academicYear.semester === 'even'
      ? 'Genap'
      : academicYear.semester || '-';

  return `${academicYear.name || '-'} • Semester ${semesterLabel}`;
});

const translateDay = (day) => {
  const mapping = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
  };

  return mapping[day] || day || '-';
};

const formatTime = (time) => (time ? String(time).slice(0, 5) : '-');

const formatGender = (gender) => {
  if (gender === 'L') {
    return 'Laki-laki';
  }

  if (gender === 'P') {
    return 'Perempuan';
  }

  return '-';
};

const genderBadgeClass = (gender) => {
  if (gender === 'L') {
    return 'bg-blue-50 text-blue-700 border-blue-200';
  }

  if (gender === 'P') {
    return 'bg-pink-50 text-pink-700 border-pink-200';
  }

  return 'bg-gray-50 text-gray-700 border-gray-200';
};

const escapeCsvValue = (value) => {
  const normalized = value === null || value === undefined ? '' : String(value);
  return `"${normalized.replaceAll('"', '""')}"`;
};

const sanitizeFilename = (value) => {
  return String(value || 'detail-kelas')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'detail-kelas';
};

const downloadCsv = (filename, headers, rows) => {
  const headerLine = headers.map(escapeCsvValue).join(',');
  const bodyLines = rows.map((row) => row.map(escapeCsvValue).join(','));
  const csvContent = `\uFEFF${[headerLine, ...bodyLines].join('\r\n')}`;

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');

  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};

const exportStudentsCsv = () => {
  const rows = studentsRows.value.map((student) => [
    classData.value?.name || '-',
    academicYearLabel.value,
    student.nis || '-',
    student.nisn || '-',
    student.user?.name || '-',
    formatGender(student.gender),
  ]);

  downloadCsv(
    `detail-kelas-${sanitizeFilename(classData.value?.name)}-siswa.csv`,
    ['Kelas', 'Tahun Ajaran', 'NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin'],
    rows,
  );
};

const exportSchedulesCsv = () => {
  const rows = schedulesRows.value.map((schedule) => [
    classData.value?.name || '-',
    academicYearLabel.value,
    translateDay(schedule.day_of_week),
    `${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}`,
    schedule.subject?.name || '-',
    schedule.teacher?.user?.name || '-',
  ]);

  downloadCsv(
    `detail-kelas-${sanitizeFilename(classData.value?.name)}-jadwal.csv`,
    ['Kelas', 'Tahun Ajaran', 'Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengajar'],
    rows,
  );
};

const fetchClassDetail = async () => {
  isLoading.value = true;

  try {
    const { data } = await classService.getById(route.params.id);
    classData.value = data;
    activeTab.value = 'students';
  } catch {
    classData.value = null;
    toastStore.error('Gagal memuat detail kelas.');
  } finally {
    isLoading.value = false;
  }
};

const goBack = () => {
  router.push('/admin/classes');
};

watch(
  () => route.params.id,
  () => {
    fetchClassDetail();
  },
);

onMounted(fetchClassDetail);
</script>
