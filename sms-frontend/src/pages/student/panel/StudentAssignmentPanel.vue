<template>
  <div class="space-y-6">
    <div
      class="bg-orange-50 border border-orange-200 rounded-2xl p-4 shadow-sm flex items-center gap-4"
    >
      <div
        class="w-6 h-6 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-brand-orange shrink-0 shadow-sm"
      >
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
          ></path>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-2xs sm:text-lg font-bold text-orange-900">
            Daftar Tugas
          </h3>
          <BasePopoverInfo>
            <strong class="text-gray-800 block mb-1"
              >Informasi Penugasan:</strong
            >
            Menampilkan seluruh tugas untuk mata pelajaran ini. Anda dapat
            mengubah (Edit) file jawaban Anda selama
            <strong>Deadline belum berakhir</strong> dan tugas
            <strong>belum dinilai</strong> oleh guru.
          </BasePopoverInfo>
        </div>
      </div>
    </div>

    <div
      class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4"
    >
      <div class="relative flex-1">
        <div
          class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
        >
          <svg
            class="w-5 h-5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            ></path>
          </svg>
        </div>
        <input
          type="text"
          v-model="searchQuery"
          @input="onSearchInput"
          placeholder="Cari judul tugas..."
          class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-orange/20 focus:border-brand-orange focus:bg-white outline-none transition-all"
        />
      </div>

      <div class="relative w-full sm:w-48 shrink-0">
        <input
          type="date"
          v-model="filterDate"
          @change="fetchAssignments"
          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 focus:ring-2 focus:ring-brand-orange/20 focus:border-brand-orange focus:bg-white outline-none transition-all"
        />
        <button
          v-if="filterDate"
          @click="clearDate"
          class="absolute inset-y-0 right-10 flex items-center text-gray-400 hover:text-brand-red"
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
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center py-16"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-orange mb-3"
      ></div>
      <p class="text-gray-500 font-medium text-sm">Menarik daftar tugas...</p>
    </div>

    <div
      v-else-if="assignments.length === 0"
      class="bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200"
    >
      <div
        class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4"
      >
        <svg
          class="w-8 h-8 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M5 13l4 4L19 7"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">Tugas Tidak Ditemukan</h3>
      <p class="text-gray-500 mt-1 max-w-md mx-auto text-sm">
        {{
          searchQuery || filterDate
            ? "Coba ubah kata kunci pencarian atau filter tanggal Anda."
            : "Guru belum memberikan tugas apapun untuk mata pelajaran ini."
        }}
      </p>
      <button
        v-if="searchQuery || filterDate"
        @click="resetFilters"
        class="mt-5 px-6 py-2 bg-brand-orange hover:bg-orange-600 text-white text-sm font-bold rounded-xl shadow-sm transition-colors"
      >
        Reset Filter
      </button>
    </div>

    <div v-else class="space-y-5">
      <div
        v-for="task in assignments"
        :key="task.id"
        class="bg-white border border-gray-200 rounded-2xl p-5 md:p-6 shadow-sm flex flex-col md:flex-row gap-6 relative overflow-hidden transition-all hover:shadow-md"
      >
        <div
          class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors"
          :class="getStatus(task).barColor"
        ></div>

        <div class="flex-1 pl-2 w-full">
          <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2.5 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold rounded-lg uppercase tracking-wider"
                >
                  Diunggah
                </span>
                <span class="text-xs font-semibold text-gray-500">{{
                  formatDate(task.created_at)
                }}</span>
              </div>
              <h3 class="text-xl font-bold text-gray-800">{{ task.title }}</h3>
              <span class="inline-flex items-center mt-1 px-2.5 py-0.5 text-[10px] font-bold rounded-lg" :class="getTypeBadge(task.type).classes">
                {{ getTypeBadge(task.type).label }}
              </span>
            </div>

            <div
              class="px-3 py-1.5 rounded-lg border flex items-center gap-1.5 text-xs font-bold shrink-0"
              :class="getDeadlineClass(task.due_date)"
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
              {{ isReportPublished ? 'Dikunci saat rapor' : 'Tenggat' }}: {{ formatDateTime(effectiveDeadline(task.due_date)) }}
            </div>
          </div>

          <div v-if="isExamType(task.type)" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2">
            <svg class="w-5 h-5 text-brand-red shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
            <p class="text-xs text-brand-red font-medium">
              <strong>Perhatian:</strong> Ini adalah <strong>{{ examTypeLabel(task.type) }}</strong>. Pastikan Anda memeriksa kembali jawaban Anda sebelum mengumpulkan. Nilai ini memiliki bobot besar terhadap rapor Anda.
            </p>
          </div>

          <p
            class="text-sm text-gray-600 leading-relaxed mb-4 whitespace-pre-line"
          >
            {{ task.description }}
          </p>

          <div
            v-if="task.attachments && task.attachments.length > 0"
            class="mb-5 bg-gray-50 p-3 rounded-xl border border-gray-100 w-full overflow-x-auto"
          >
            <p
              class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"
            >
              Lampiran:
            </p>
            <div class="flex gap-2">
              <button
                v-for="(path, idx) in task.attachments"
                :key="idx"
                @click="previewAttachment(path)"
                class="px-3 py-1.5 bg-white border border-gray-200 hover:border-brand-orange hover:text-brand-orange text-gray-600 text-xs font-semibold rounded-lg shadow-sm transition-colors flex items-center gap-1.5 shrink-0"
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
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  ></path>
                </svg>
                File {{ idx + 1 }}
              </button>
            </div>
          </div>
        </div>

        <div
          class="w-full md:w-64 shrink-0 flex flex-col justify-between bg-gray-50 rounded-xl p-4 border border-gray-100"
        >
          <div class="mb-4">
            <p
              class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1"
            >
              Status Pengerjaan
            </p>
            <div
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold"
              :class="getStatus(task).badgeClass"
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
                  :d="getStatus(task).icon"
                ></path>
              </svg>
              {{ getStatus(task).text }}
            </div>
          </div>

          <div
            v-if="task.submission && task.submission.grade"
            class="mt-auto pt-3 border-t border-gray-200"
          >
            <p
              class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1"
            >
              Nilai Anda
            </p>
            <div class="flex items-end gap-2">
              <span
                class="text-3xl font-black"
                :class="
                  task.submission.grade.score >= 75
                    ? 'text-green-600'
                    : 'text-brand-red'
                "
              >
                {{ task.submission.grade.score }}
              </span>
              <span class="text-xs font-bold text-gray-400 mb-1">/ 100</span>
            </div>
            <p
              v-if="task.submission.grade.feedback"
              class="text-xs text-gray-500 mt-1 italic"
            >
              "{{ task.submission.grade.feedback }}"
            </p>
          </div>

          <div v-else class="mt-auto">
            <div v-if="task.submission" class="mb-2">
              <p class="text-xs text-gray-500 font-medium">
                File Jawaban:
                <button
                  @click="previewAttachment(task.submission.file_path)"
                  class="font-semibold text-blue-600 hover:underline"
                >
                  Lihat File ✓
                </button>
              </p>
            </div>

            <button
              v-if="canSubmit(task)"
              @click="openModal(task)"
              class="w-full py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center justify-center gap-2"
              :class="
                task.submission
                  ? 'bg-white border-2 border-brand-orange text-brand-orange hover:bg-orange-50'
                  : 'bg-brand-orange hover:bg-orange-600 text-white'
              "
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
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                ></path>
              </svg>
              {{ task.submission ? "Edit Jawaban" : "Kerjakan Tugas" }}
            </button>

            <button
              v-else
              disabled
              class="w-full py-2.5 bg-gray-200 text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed"
            >
              {{ isReportPublished ? 'Rapor Diterbitkan' : 'Akses Ditutup' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in-up"
      >
        <div class="p-6 md:p-8">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kumpul Jawaban</h3>
            <button
              @click="closeModal"
              class="text-gray-400 hover:text-brand-red transition-colors p-1 bg-gray-50 hover:bg-red-50 rounded-full"
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
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>

          <div
            class="mb-5 p-4 bg-orange-50 rounded-xl border border-orange-100"
          >
            <h4 class="font-bold text-orange-900 mb-1 line-clamp-1">
              {{ activeTask?.title }}
            </h4>
            <p class="text-xs text-orange-700">
              Pastikan file sudah benar sebelum dikirim. Upload ulang akan
              menimpa (menghapus) file Anda sebelumnya.
            </p>
          </div>

          <div
            class="relative border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:bg-gray-50 hover:border-brand-orange transition-colors"
            :class="{ 'border-brand-orange bg-orange-50': selectedFile }"
          >
            <input
              type="file"
              @change="handleFileUpload"
              accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              required
            />

            <div v-if="!selectedFile" class="pointer-events-none">
              <div
                class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm text-gray-400"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  ></path>
                </svg>
              </div>
              <p class="text-sm text-gray-600 font-medium mb-1">
                <span class="text-brand-orange font-bold">Pilih File</span> atau
                seret ke sini
              </p>
              <p class="text-xs text-gray-400">
                PDF, DOC/X, ZIP, PNG, JPG (Maks 10MB)
              </p>
            </div>

            <div v-else class="pointer-events-none flex flex-col items-center">
              <div
                class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3 text-brand-orange"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  ></path>
                </svg>
              </div>
              <p class="text-sm font-bold text-gray-800 break-all">
                {{ selectedFile.name }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                {{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB
              </p>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              @click="closeModal"
              class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
            >
              Batal
            </button>
            <button
              @click="submitTask"
              :disabled="!selectedFile || isSubmitting"
              class="px-6 py-2.5 bg-brand-orange hover:bg-orange-600 text-white text-sm font-bold rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <svg
                v-if="isSubmitting"
                class="animate-spin w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                ></path>
              </svg>
              {{ isSubmitting ? "Mengirim..." : "Kirim Jawaban" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { studentAssignmentService } from "../../../services/modules/student/assignmentService";
import { useToastStore } from "../../../stores/toast";
import BasePopoverInfo from "../../../components/BasePopoverInfo.vue";
import { useReportStatus } from '../../../composables/useReportStatus';

// PROPS INI WAJIB SAMA SEPERTI MATERIAL
const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true },
});

const toastStore = useToastStore();
const { isReportPublished, publishedAt } = useReportStatus('student');
const assignments = ref([]);
const isLoading = ref(true);

// Filter Search & Date
const searchQuery = ref("");
const filterDate = ref("");
let searchTimeout = null;

// Modal States
const showModal = ref(false);
const isSubmitting = ref(false);
const activeTask = ref(null);
const selectedFile = ref(null);

const formatDate = (dateString) => {
  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  }).format(new Date(dateString));
};

const formatDateTime = (dateString) => {
  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(dateString));
};

const isPastDeadline = (dueDate) => {
  if (isReportPublished.value) return true;
  return new Date() > new Date(dueDate);
};

// Effective deadline: show published date as cutoff when report is published
const effectiveDeadline = (dueDate) => {
  if (isReportPublished.value && publishedAt.value) return publishedAt.value;
  return dueDate;
};

// Logika dinamis untuk warna indikator status Tugas
const getStatus = (task) => {
  if (task.submission && task.submission.grade) {
    return {
      text: "Sudah Dinilai",
      badgeClass: "bg-green-100 text-green-700",
      barColor: "bg-green-500",
      icon: "M5 13l4 4L19 7",
    };
  }
  if (task.submission) {
    return {
      text: "Sudah Dikumpul",
      badgeClass: "bg-blue-100 text-blue-700",
      barColor: "bg-blue-500",
      icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",
    };
  }
  if (isPastDeadline(task.due_date)) {
    return {
      text: "Terlewat",
      badgeClass: "bg-red-100 text-brand-red",
      barColor: "bg-brand-red",
      icon: "M6 18L18 6M6 6l12 12",
    };
  }
  return {
    text: "Belum Dikerjakan",
    badgeClass: "bg-orange-100 text-brand-orange",
    barColor: "bg-brand-orange",
    icon: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
  };
};

const getDeadlineClass = (dueDate) => {
  return isPastDeadline(dueDate)
    ? "border-red-200 text-brand-red bg-red-50"
    : "border-gray-200 text-gray-600 bg-gray-50";
};

const canSubmit = (task) => {
  if (isReportPublished.value) return false;
  const hasGrade = task.submission && task.submission.grade;
  return !hasGrade && !isPastDeadline(task.due_date);
};

const getTypeBadge = (type) => {
  switch (type) {
    case 'uts': return { label: 'UTS', classes: 'bg-brand-orange/10 text-brand-orange' };
    case 'uas': return { label: 'UAS', classes: 'bg-brand-red/10 text-brand-red' };
    default: return { label: 'Tugas Harian', classes: 'bg-blue-50 text-blue-700' };
  }
};

const isExamType = (type) => type === 'uts' || type === 'uas';
const examTypeLabel = (type) => type === 'uts' ? 'UTS' : 'UAS';

// FITUR FILTER SEARCH DEBOUNCE & DATE
const fetchAssignments = async () => {
  isLoading.value = true;
  try {
    const params = {
      schedule_id: props.scheduleId,
      search: searchQuery.value || undefined,
      date: filterDate.value || undefined,
    };
    const res = await studentAssignmentService.getAssignments(params);
    assignments.value = res.data.data || res.data;
  } catch (error) {
    toastStore.error("Gagal memuat daftar tugas.");
  } finally {
    isLoading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchAssignments();
  }, 500);
};

const clearDate = () => {
  filterDate.value = "";
  fetchAssignments();
};

const resetFilters = () => {
  searchQuery.value = "";
  filterDate.value = "";
  fetchAssignments();
};

// Preview Lampiran dari Guru ATAU Jawaban dari Siswa
const previewAttachment = (filePath) => {
  if (!filePath) return;
  const baseUrl = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";
  window.open(`${baseUrl}/storage/${filePath}`, "_blank");
};

// Modal Actions
const openModal = (task) => {
  activeTask.value = task;
  selectedFile.value = null;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  activeTask.value = null;
  selectedFile.value = null;
};

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    toastStore.error("Ukuran file maksimal adalah 10MB.");
    e.target.value = "";
    selectedFile.value = null;
    return;
  }
  selectedFile.value = file;
};

const submitTask = async () => {
  if (!selectedFile.value || !activeTask.value) return;

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append("file", selectedFile.value);

    await studentAssignmentService.submitAssignment(
      activeTask.value.id,
      formData,
    );

    toastStore.success("Jawaban tugas berhasil dikirim!");
    closeModal();
    await fetchAssignments();
  } catch (error) {
    const msg =
      error.response?.data?.error ||
      error.response?.data?.message ||
      "Gagal mengirim jawaban.";
    toastStore.error(msg);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  fetchAssignments();
});
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
