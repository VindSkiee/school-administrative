<template>
  <div class="space-y-6">
    <section
      class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Laporan &amp; Rapor Semester
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Analitik kehadiran, performa akademik, dan distribusi rapor siswa.
        </p>
      </div>

      <div class="w-full lg:w-[320px]">
        <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">
          Pilih Tahun Ajaran
        </label>
        <BaseSelect
          v-model="selectedAcademicYearId"
          :options="academicYearOptions"
          placeholder="Pilih Tahun Ajaran"
          :disabled="isLoadingInitial || isLoadingYears"
        />
      </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-200 p-2">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-4 py-3 rounded-xl text-sm font-semibold transition-colors"
          :class="
            activeTab === tab.key
              ? 'bg-brand-red text-white shadow-sm'
              : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
          "
        >
          {{ tab.label }}
        </button>
      </div>
    </section>

    <section
      v-if="isLoadingInitial"
      class="bg-white rounded-2xl border border-gray-200 p-10 text-center"
    >
      <div class="inline-flex items-center text-gray-600">
        <svg class="animate-spin h-5 w-5 mr-3 text-brand-red" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
        Memuat data laporan...
      </div>
    </section>

    <template v-else>
      <section v-if="activeTab === 'attendance'" class="space-y-4">
        <div
          v-if="isLoadingReports"
          class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-600"
        >
          Memuat ringkasan kehadiran...
        </div>

        <template v-else>
          <div v-if="attendanceRows.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <article
              v-for="card in attendanceCards"
              :key="card.label"
              class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm"
            >
              <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ card.label }}</p>
              <p class="text-3xl font-bold text-gray-800 mt-2">{{ card.value }}</p>
            </article>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
              <h2 class="font-semibold text-gray-800">Ringkasan Per Kelas</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-brand-red text-white">
                  <tr>
                    <th class="px-4 py-3 text-left">Kelas</th>
                    <th class="px-4 py-3 text-center">Total Rekaman</th>
                    <th class="px-4 py-3 text-center">Hadir</th>
                    <th class="px-4 py-3 text-center">Izin</th>
                    <th class="px-4 py-3 text-center">Sakit</th>
                    <th class="px-4 py-3 text-center">Alpa</th>
                    <th class="px-4 py-3 text-center">Terlambat</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="row in attendanceRows" :key="row.class_id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ row.class_name }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_records) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_present) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_permission) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_sick) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_alpa) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_late) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div
            v-if="!attendanceRows.length"
            class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500"
          >
            Data kehadiran belum tersedia untuk Tahun Ajaran yang dipilih.
          </div>
        </template>
      </section>

      <section v-if="activeTab === 'academic'" class="space-y-4">
        <div
          v-if="isLoadingReports"
          class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-600"
        >
          Memuat ringkasan akademik...
        </div>

        <template v-else>
          <div v-if="academicRows.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <article
              v-for="card in academicCards"
              :key="card.label"
              class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm"
            >
              <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ card.label }}</p>
              <p class="text-3xl font-bold text-gray-800 mt-2">{{ card.value }}</p>
            </article>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
              <h2 class="font-semibold text-gray-800">Statistik Nilai Per Kelas</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-brand-red text-white">
                  <tr>
                    <th class="px-4 py-3 text-left">Kelas</th>
                    <th class="px-4 py-3 text-center">Total Tugas</th>
                    <th class="px-4 py-3 text-center">Submission Dinilai</th>
                    <th class="px-4 py-3 text-center">Rata-rata Nilai</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="row in academicRows" :key="row.class_id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ row.class_name }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_assignments) }}</td>
                    <td class="px-4 py-3 text-center">{{ toNumber(row.total_graded_submissions) }}</td>
                    <td class="px-4 py-3 text-center">{{ formatScore(row.average_class_score) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div
            v-if="!academicRows.length"
            class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500"
          >
            Data akademik belum tersedia untuk Tahun Ajaran yang dipilih.
          </div>
        </template>
      </section>

      <section v-if="activeTab === 'distribution'" class="space-y-6">
        <!-- DANGER ZONE: Only visible when NOT yet published -->
        <div v-if="!selectedAcademicYear?.is_report_published" class="rounded-2xl border-2 border-red-300 bg-red-50 p-5 space-y-4">
          <div>
            <h3 class="text-lg font-bold text-red-700">Danger Zone: Publikasi Semester</h3>
            <p class="text-sm text-red-600 mt-1">
              Setelah dipublikasikan, semester akan dikunci dan rapor dianggap final.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-sm text-red-700">
              Status saat ini:
              <span class="font-bold">Belum Dipublikasikan</span>
            </div>

            <button
              @click="handlePublishReports"
              :disabled="isPublishing || !selectedAcademicYearId || !isAllStudentsReady"
              class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <svg
                v-if="isPublishing"
                class="animate-spin h-4 w-4 mr-2"
                viewBox="0 0 24 24"
                fill="none"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              {{ isPublishing ? 'Memproses...' : 'Publikasikan Rapor & Kunci Semester' }}
            </button>
          </div>

          <p v-if="!isAllStudentsReady" class="text-sm font-medium text-red-600">
            Tombol Publikasi dikunci. Harap lengkapi semua nilai & kehadiran siswa terlebih dahulu.
          </p>
        </div>

        <!-- LOCKED STATE: Visible when already published -->
        <div v-else class="rounded-2xl border-2 border-green-300 bg-green-50 p-5 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 text-green-600 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-green-800">Rapor Sudah Dipublikasikan & Semester Terkunci</h3>
              <p class="text-sm text-green-600 mt-0.5">
                Nilai telah difinalisasi. Siswa sekarang dapat mengunduh rapor PDF mereka. Tidak ada perubahan yang dapat dilakukan.
              </p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Distribusi Rapor Siswa</h2>
            <span class="text-xs text-gray-500">{{ filteredDistributionStudents.length }} siswa</span>
          </div>

          

          <div class="p-4 border-b border-gray-100 bg-gray-50">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
              <div class="lg:col-span-2">
                <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">
                  Pencarian Siswa
                </label>
                <div class="relative">
                  <svg
                    class="w-5 h-5 absolute left-3 top-3 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                  <input
                    v-model="distributionSearchQuery"
                    type="text"
                    placeholder="Cari berdasarkan nama siswa, NIS, atau NISN..."
                    class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">
                  Filter Kelas
                </label>
                <BaseSelect
                  v-model="selectedDistributionClassId"
                  :options="distributionClassOptions"
                  placeholder="Semua Kelas"
                  :disabled="isLoadingStudents || isLoadingDistributionClasses"
                />
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-brand-red text-white">
                <tr>
                  <th class="px-4 py-3 text-left">NIS</th>
                  <th class="px-4 py-3 text-left">Nama Siswa</th>
                  <th class="px-4 py-3 text-left">Kelas</th>
                  <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="isLoadingStudents">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-500">Memuat data siswa...</td>
                </tr>

                <tr v-else-if="!filteredDistributionStudents.length">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                    Data siswa tidak ditemukan untuk kombinasi filter saat ini.
                  </td>
                </tr>

                <tr v-else v-for="student in distributionStudentsPage" :key="student.student_id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-800">{{ student.nis || '-' }}</td>
                  <td class="px-4 py-3 text-gray-700">{{ student.name }}</td>
                  <td class="px-4 py-3 text-gray-600">{{ getStudentClassName(student) }}</td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex flex-col items-center gap-1">
                      <button
                        @click="handleDownloadPdf(student)"
                        :disabled="downloadLoadingMap[student.student_id] || !selectedAcademicYearId || !student.is_ready"
                        class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-brand-orange hover:bg-brand-red text-white font-semibold transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
                      >
                        <svg
                          v-if="downloadLoadingMap[student.student_id]"
                          class="animate-spin h-4 w-4 mr-2"
                          viewBox="0 0 24 24"
                          fill="none"
                        >
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ downloadLoadingMap[student.student_id] ? 'Menyiapkan PDF...' : 'Download PDF' }}
                      </button>

                      <p v-if="!student.is_ready && student.missing_info" class="text-xs text-red-600 leading-snug">
                        {{ student.missing_info }}
                      </p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredDistributionStudents.length"
            class="px-4 py-3 border-t border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
          >
            <p class="text-sm text-gray-600">
              Menampilkan
              <span class="font-semibold">{{ distributionStartItem }}</span>
              -
              <span class="font-semibold">{{ distributionEndItem }}</span>
              dari
              <span class="font-semibold">{{ filteredDistributionStudents.length }}</span>
              siswa
            </p>

            <div class="flex items-center gap-2">
              <button
                @click="changeDistributionPage(distributionPagination.currentPage - 1)"
                :disabled="distributionPagination.currentPage === 1"
                class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Sebelumnya
              </button>

              <span class="text-sm font-semibold text-gray-700">
                Halaman {{ distributionPagination.currentPage }} / {{ distributionTotalPages }}
              </span>

              <button
                @click="changeDistributionPage(distributionPagination.currentPage + 1)"
                :disabled="distributionPagination.currentPage === distributionTotalPages"
                class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        </div>

        
      </section>
    </template>

    <ConfirmModal
      :isOpen="publishConfirmModal.isOpen"
      :isLoading="publishConfirmModal.isLoading"
      title="Publikasikan Rapor & Kunci Semester"
      message="Tindakan ini akan mengunci pengeditan nilai dan mengizinkan siswa mengunduh rapor PDF. Lanjutkan?"
      confirmText="Ya, Publikasikan!"
      @confirm="executePublishReports"
      @cancel="publishConfirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onActivated, reactive, ref, watch } from 'vue';
import BaseSelect from '../../components/BaseSelect.vue';
import ConfirmModal from '../../components/ConfirmModal.vue';
import { useToastStore } from '../../stores/toast';
import { academicYearService } from '../../services/modules/admin/academicYearService';
import { classService } from '../../services/modules/admin/classService';
import { reportService } from '../../services/modules/admin/reportService';

const toastStore = useToastStore();

const tabs = [
  { key: 'attendance', label: 'Ringkasan Kehadiran' },
  { key: 'academic', label: 'Ringkasan Akademik' },
  { key: 'distribution', label: 'Distribusi Rapor' }
];

const activeTab = ref('attendance');
const selectedAcademicYearId = ref('');

const academicYears = ref([]);
const attendanceRows = ref([]);
const academicRows = ref([]);
const students = ref([]);
const distributionClassOptions = ref([]);
const isAllStudentsReady = ref(false);

const selectedDistributionClassId = ref('');
const distributionSearchQuery = ref('');
const distributionPagination = reactive({
  currentPage: 1,
  perPage: 100
});

const isLoadingInitial = ref(true);
const isLoadingYears = ref(false);
const isLoadingReports = ref(false);
const isLoadingStudents = ref(false);
const isLoadingDistributionClasses = ref(false);
const isPublishing = ref(false);

const downloadLoadingMap = reactive({});

const publishConfirmModal = reactive({ isOpen: false, isLoading: false });

const selectedAcademicYear = computed(() =>
  academicYears.value.find((item) => String(item.id) === String(selectedAcademicYearId.value)) || null
);

const academicYearOptions = computed(() =>
  academicYears.value.map((item) => ({
    value: String(item.id),
    label: `${item.name} - ${item.semester === 'odd' ? 'Ganjil' : 'Genap'}${item.is_active ? ' (Aktif)' : ''}`
  }))
);

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isNaN(parsed) ? 0 : parsed;
};

const formatScore = (value) => {
  const score = Number(value);
  if (Number.isNaN(score)) {
    return '0.00';
  }
  return score.toFixed(2);
};

const unwrapArrayPayload = (response) => {
  const payload = response?.data;
  if (Array.isArray(payload)) {
    return payload;
  }
  if (Array.isArray(payload?.data)) {
    return payload.data;
  }
  return [];
};

const unwrapDistributionPayload = (response) => {
  const payload = response?.data || {};

  return {
    isAllReady: Boolean(payload.is_all_ready),
    students: Array.isArray(payload.data) ? payload.data : []
  };
};

const getStudentClassName = (student) => student?.class_name || '-';

const filteredDistributionStudents = computed(() => {
  const query = distributionSearchQuery.value.trim().toLowerCase();
  const classFilterId = String(selectedDistributionClassId.value || '');

  return students.value.filter((student) => {
    const matchClass = !classFilterId || String(student?.class_id) === classFilterId;

    if (!matchClass) {
      return false;
    }

    if (!query) {
      return true;
    }

    const name = String(student.name || '').toLowerCase();
    const nis = String(student.nis || '').toLowerCase();
    const nisn = String(student.nisn || '').toLowerCase();

    return name.includes(query) || nis.includes(query) || nisn.includes(query);
  });
});

const distributionTotalPages = computed(() => {
  const total = filteredDistributionStudents.value.length;
  return total > 0 ? Math.ceil(total / distributionPagination.perPage) : 1;
});

const distributionStudentsPage = computed(() => {
  const start = (distributionPagination.currentPage - 1) * distributionPagination.perPage;
  const end = start + distributionPagination.perPage;
  return filteredDistributionStudents.value.slice(start, end);
});

const distributionStartItem = computed(() => {
  if (!filteredDistributionStudents.value.length) {
    return 0;
  }
  return (distributionPagination.currentPage - 1) * distributionPagination.perPage + 1;
});

const distributionEndItem = computed(() => {
  if (!filteredDistributionStudents.value.length) {
    return 0;
  }
  return Math.min(
    distributionPagination.currentPage * distributionPagination.perPage,
    filteredDistributionStudents.value.length
  );
});

const changeDistributionPage = (page) => {
  if (page < 1 || page > distributionTotalPages.value) {
    return;
  }
  distributionPagination.currentPage = page;
};

const attendanceCards = computed(() => {
  const totals = attendanceRows.value.reduce(
    (acc, item) => {
      acc.totalRecords += toNumber(item.total_records);
      acc.totalPresent += toNumber(item.total_present);
      acc.totalPermission += toNumber(item.total_permission);
      acc.totalSick += toNumber(item.total_sick);
      acc.totalAlpa += toNumber(item.total_alpa);
      acc.totalLate += toNumber(item.total_late);
      return acc;
    },
    {
      totalRecords: 0,
      totalPresent: 0,
      totalPermission: 0,
      totalSick: 0,
      totalAlpa: 0,
      totalLate: 0
    }
  );

  const attendanceRate =
    totals.totalRecords > 0 ? `${((totals.totalPresent / totals.totalRecords) * 100).toFixed(1)}%` : '0.0%';

  return [
    { label: 'Total Rekaman', value: totals.totalRecords },
    { label: 'Total Hadir', value: totals.totalPresent },
    { label: 'Total Alpha', value: totals.totalAlpa },
    { label: 'Kehadiran', value: attendanceRate }
  ];
});

const academicCards = computed(() => {
  const totals = academicRows.value.reduce(
    (acc, item) => {
      const gradedCount = toNumber(item.total_graded_submissions);
      const average = Number(item.average_class_score) || 0;

      acc.totalAssignments += toNumber(item.total_assignments);
      acc.totalGraded += gradedCount;
      acc.weightedScore += average * gradedCount;
      if (gradedCount > 0) {
        acc.activeClasses += 1;
      }
      return acc;
    },
    {
      totalAssignments: 0,
      totalGraded: 0,
      weightedScore: 0,
      activeClasses: 0
    }
  );

  const overallAverage = totals.totalGraded > 0 ? (totals.weightedScore / totals.totalGraded).toFixed(2) : '0.00';

  return [
    { label: 'Total Tugas', value: totals.totalAssignments },
    { label: 'Submission Dinilai', value: totals.totalGraded },
    { label: 'Rata-rata Umum', value: overallAverage },
    { label: 'Kelas Aktif Penilaian', value: totals.activeClasses }
  ];
});

// Ambil daftar Tahun Ajaran dan set default ke yang aktif.
const fetchAcademicYears = async () => {
  isLoadingYears.value = true;
  try {
    const response = await academicYearService.getAll({ per_page: 'all' });
    const list = unwrapArrayPayload(response);
    academicYears.value = list;

    if (!selectedAcademicYearId.value && list.length) {
      const activeYear = list.find((item) => item.is_active);
      selectedAcademicYearId.value = String((activeYear || list[0]).id);
    }
  } catch (error) {
    toastStore.error('Gagal memuat Tahun Ajaran.');
  } finally {
    isLoadingYears.value = false;
  }
};

// Ambil data ringkasan tab Kehadiran dan Akademik secara paralel.
const fetchReportSummaries = async () => {
  if (!selectedAcademicYearId.value) {
    attendanceRows.value = [];
    academicRows.value = [];
    return;
  }

  isLoadingReports.value = true;
  try {
    const [attendanceResponse, academicResponse] = await Promise.all([
      reportService.getAttendanceSummary(selectedAcademicYearId.value),
      reportService.getAcademicSummary(selectedAcademicYearId.value)
    ]);

    attendanceRows.value = unwrapArrayPayload(attendanceResponse);
    academicRows.value = unwrapArrayPayload(academicResponse);
  } catch (error) {
    toastStore.error(error.response?.data?.error || 'Gagal memuat ringkasan laporan.');
  } finally {
    isLoadingReports.value = false;
  }
};

// Ambil daftar distribusi kesiapan siswa untuk kebutuhan PDF rapor.
const fetchDistributionStudents = async () => {
  if (!selectedAcademicYearId.value) {
    students.value = [];
    isAllStudentsReady.value = false;
    return;
  }

  isLoadingStudents.value = true;
  try {
    const response = await reportService.getDistributionList(selectedAcademicYearId.value);
    const payload = unwrapDistributionPayload(response);

    students.value = payload.students;
    isAllStudentsReady.value = payload.isAllReady;
  } catch (error) {
    students.value = [];
    isAllStudentsReady.value = false;
    toastStore.error(error.response?.data?.error || 'Gagal memuat daftar distribusi rapor.');
  } finally {
    isLoadingStudents.value = false;
  }
};

// Ambil opsi kelas untuk filter distribusi rapor berdasarkan Tahun Ajaran terpilih.
const fetchDistributionClasses = async () => {
  if (!selectedAcademicYearId.value) {
    distributionClassOptions.value = [];
    selectedDistributionClassId.value = '';
    return;
  }

  isLoadingDistributionClasses.value = true;
  try {
    const response = await classService.getAll({
      per_page: 'all',
      academic_year_id: selectedAcademicYearId.value
    });

    const classes = unwrapArrayPayload(response);
    distributionClassOptions.value = classes.map((item) => ({
      value: String(item.id),
      label: item.name
    }));

    const selectedStillExists = distributionClassOptions.value.some(
      (option) => option.value === selectedDistributionClassId.value
    );
    if (!selectedStillExists) {
      selectedDistributionClassId.value = '';
    }
  } catch (error) {
    toastStore.error('Gagal memuat daftar kelas untuk filter rapor.');
  } finally {
    isLoadingDistributionClasses.value = false;
  }
};

const handleDownloadPdf = async (student) => {
  if (!selectedAcademicYearId.value) {
    toastStore.error('Pilih Tahun Ajaran terlebih dahulu.');
    return;
  }

  if (!student.is_ready) {
    toastStore.error(student.missing_info || 'Data akademik siswa belum lengkap.');
    return;
  }

  downloadLoadingMap[student.student_id] = true;
  try {
    await reportService.downloadStudentSemesterPdf(
      selectedAcademicYearId.value,
      student.student_id,
      student.name
    );
    toastStore.success(`Rapor ${student.name} berhasil diunduh.`);
  } catch (error) {
    toastStore.error(error.message || error.response?.data?.error || 'Gagal mengunduh PDF rapor.');
  } finally {
    downloadLoadingMap[student.student_id] = false;
  }
};

const handlePublishReports = () => {
  if (!selectedAcademicYearId.value) {
    toastStore.error('Pilih Tahun Ajaran terlebih dahulu.');
    return;
  }

  if (!isAllStudentsReady.value) {
    toastStore.error('Terdapat siswa dengan data akademik belum lengkap.');
    return;
  }

  publishConfirmModal.isOpen = true;
};

const executePublishReports = async () => {
  publishConfirmModal.isLoading = true;
  isPublishing.value = true;
  try {
    await reportService.publishReports(selectedAcademicYearId.value);
    toastStore.success('Rapor berhasil dipublikasikan dan semester terkunci.');
    publishConfirmModal.isOpen = false;
    await fetchAcademicYears();
  } catch (error) {
    toastStore.error(error.response?.data?.error || 'Gagal mempublikasikan rapor.');
  } finally {
    publishConfirmModal.isLoading = false;
    isPublishing.value = false;
  }
};

// PERF FIX: skip watcher during initial load to prevent duplicate requests
watch(selectedAcademicYearId, async (newValue, oldValue) => {
  if (isInitialLoad.value || !newValue || newValue === oldValue) {
    return;
  }

  selectedDistributionClassId.value = '';
  distributionPagination.currentPage = 1;

  await Promise.all([fetchReportSummaries(), fetchDistributionStudents(), fetchDistributionClasses()]);
});

watch([distributionSearchQuery, selectedDistributionClassId], () => {
  distributionPagination.currentPage = 1;
});

watch(filteredDistributionStudents, () => {
  if (distributionPagination.currentPage > distributionTotalPages.value) {
    distributionPagination.currentPage = distributionTotalPages.value;
  }
});

// PERF FIX: single-pass initial load — watcher is suppressed via isInitialLoad flag
const isInitialLoad = ref(true);

onMounted(async () => {
  isLoadingInitial.value = true;
  isInitialLoad.value = true;
  try {
    await fetchAcademicYears();
    // PERF FIX: single manual fetch after year is set — watcher was suppressed
    await Promise.all([fetchReportSummaries(), fetchDistributionStudents(), fetchDistributionClasses()]);
  } finally {
    isInitialLoad.value = false;
    isLoadingInitial.value = false;
  }
});

// Refresh report data when re-activated from keep-alive cache
onActivated(() => {
  if (selectedAcademicYearId.value) {
    Promise.all([fetchReportSummaries(), fetchDistributionStudents(), fetchDistributionClasses()]);
  }
});
</script>
