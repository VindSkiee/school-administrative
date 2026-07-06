<template>
  <div class="space-y-6">
    <div
      class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md"
    >
      <h1 class="text-2xl md:text-3xl font-bold font-serif">
        Manajemen Rapor Kelas Wali
      </h1>
      <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
        Isi catatan wali kelas untuk setiap siswa sebelum rapor dipublikasikan.
      </p>
    </div>

    <div
      class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col lg:flex-row gap-4 items-stretch lg:items-end w-full"
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
          @update:modelValue="onAcademicYearChange"
        />
        <p
          v-if="selectedAcademicYearData"
          class="text-xs text-gray-500 mt-1"
        >
          Periode: {{ formatPeriodDate(selectedAcademicYearData.start_date) }} - {{ formatPeriodDate(selectedAcademicYearData.end_date) }}
        </p>
      </div>

      <div
        v-if="homeroomClass"
        class="flex items-center gap-2 shrink-0"
      >
        <span
          class="px-3 py-2 bg-green-100 text-green-700 text-xs font-bold rounded-lg"
        >
          {{ homeroomClass.name }}
        </span>
        <span
          v-if="summary"
          class="px-3 py-2 text-xs font-bold rounded-lg"
          :class="
            summary.notes_complete
              ? 'bg-green-100 text-green-700'
              : 'bg-amber-100 text-amber-700'
          "
        >
          {{ summary.completed_notes }}/{{ summary.total_students }} Catatan
        </span>
      </div>
    </div>

    <div
      v-if="!selectedAcademicYearId"
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
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">
        Pilih Tahun Ajaran
      </h3>
      <p class="text-gray-500 mt-1 text-sm max-w-md mx-auto">
        Silakan pilih tahun ajaran untuk mengisi catatan wali kelas.
      </p>
    </div>

    <div
      v-else-if="!homeroomClass && !isLoading"
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
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">
        Tidak Ada Kelas Perwalian
      </h3>
      <p class="text-gray-500 mt-1 text-sm max-w-md mx-auto">
        Anda belum ditetapkan sebagai wali kelas pada tahun ajaran ini.
      </p>
    </div>

    <div
      v-else-if="isLoading"
      class="flex flex-col items-center justify-center py-16"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"
      ></div>
      <p class="text-gray-500 font-medium text-sm">Memuat data siswa...</p>
    </div>

    <template v-else>
      <div
        v-if="homeroomClass.is_published"
        class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3"
      >
        <svg
          class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
          ></path>
        </svg>
        <div>
          <p class="text-sm font-bold text-amber-800">
            Rapor Sudah Dipublikasikan
          </p>
          <p class="text-xs text-amber-600">
            Catatan tidak dapat diubah setelah rapor dipublikasikan oleh admin.
          </p>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-gray-700">
              Daftar Siswa — {{ homeroomClass.name }}
            </h3>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="!homeroomClass.is_published"
              @click="saveAllNotes"
              :disabled="isSaving || !hasChanges"
              class="px-4 py-2 bg-brand-red hover:bg-brand-orange text-white text-xs font-bold rounded-lg disabled:opacity-50 transition-colors shadow-sm"
            >
              {{ isSaving ? 'Menyimpan...' : 'Simpan Semua' }}
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-10">
                  No
                </th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-24">
                  NIS
                </th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                  Nama Siswa
                </th>
                <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider w-28">
                  Rata-rata
                </th>
                <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider w-28">
                  Kehadiran
                </th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                  Catatan Wali Kelas
                </th>
                <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider w-20">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(student, index) in students"
                :key="student.id"
                class="hover:bg-gray-50"
              >
                <td class="px-4 py-3 text-center text-gray-500">
                  {{ index + 1 }}
                </td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                  {{ student.nis }}
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800">
                  {{ student.name }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    v-if="student.average_score != null"
                    class="font-bold"
                    :class="student.average_score >= 75 ? 'text-green-600' : 'text-red-600'"
                  >
                    {{ student.average_score.toFixed(1) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="font-bold"
                    :class="
                      student.attendance.rate >= 90
                        ? 'text-green-600'
                        : student.attendance.rate >= 75
                          ? 'text-amber-600'
                          : 'text-red-600'
                    "
                  >
                    {{ student.attendance.rate }}%
                  </span>
                </td>
                <td class="px-4 py-3">
                  <textarea
                    v-model="student.note"
                    :disabled="homeroomClass.is_published"
                    rows="2"
                    maxlength="500"
                    placeholder="Tulis catatan untuk siswa ini..."
                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-red focus:border-brand-red resize-none disabled:bg-gray-100 disabled:cursor-not-allowed"
                    @input="markChanged(student.id)"
                  ></textarea>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    v-if="homeroomClass.is_published"
                    @click="downloadPdf(student.id)"
                    class="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm"
                    title="Download PDF Rapor"
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
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                      ></path>
                    </svg>
                  </button>
                  <span
                    v-else
                    class="text-xs text-gray-400 italic"
                  >
                    Belum dipublikasi
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useToastStore } from "../../stores/toast";
import { reportService } from "../../services/modules/teacher/reportService";
import BaseSelect from "../../components/BaseSelect.vue";

const toastStore = useToastStore();

const isLoading = ref(false);
const isSaving = ref(false);
const selectedAcademicYearId = ref("");
const academicYears = ref([]);
const homeroomClass = ref(null);
const students = ref([]);
const summary = ref(null);
const changedStudentIds = ref(new Set());

const academicYearOptions = computed(() => {
  return academicYears.value.map((ay) => ({
    value: ay.id,
    label: `${ay.name || "Tahun Ajaran"} — Semester ${ay.semester === "odd" ? "Ganjil" : "Genap"}${ay.is_active ? " (Aktif)" : ""}`,
  }));
});

const selectedAcademicYearData = computed(() => {
  return academicYears.value.find((ay) => String(ay.id) === String(selectedAcademicYearId.value)) || null;
});

const formatPeriodDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const hasChanges = computed(() => {
  return changedStudentIds.value.size > 0;
});

const markChanged = (studentId) => {
  changedStudentIds.value.add(studentId);
};

const onAcademicYearChange = async () => {
  homeroomClass.value = null;
  students.value = [];
  summary.value = null;
  changedStudentIds.value.clear();

  if (!selectedAcademicYearId.value) {
    return;
  }

  await loadStudents();
};

const loadStudents = async () => {
  isLoading.value = true;
  try {
    const res = await reportService.getStudents(selectedAcademicYearId.value);
    const data = res.data?.data || res.data;
    homeroomClass.value = data.class;
    students.value = (data.students || []).map((s) => ({ ...s }));
    summary.value = data.summary || null;
  } catch (error) {
    toastStore.error("Gagal memuat data siswa.");
  } finally {
    isLoading.value = false;
  }
};

const saveAllNotes = async () => {
  if (!homeroomClass.value || !selectedAcademicYearId.value) {
    return;
  }

  isSaving.value = true;
  try {
    const notesPayload = students.value.map((s) => ({
      student_id: s.id,
      note: s.note || "",
    }));

    await reportService.saveNotes({
      academic_year_id: selectedAcademicYearId.value,
      class_id: homeroomClass.value.id,
      notes: notesPayload,
    });

    changedStudentIds.value.clear();
    toastStore.success("Catatan berhasil disimpan.");

    // Refresh summary
    await loadStudents();
  } catch (error) {
    toastStore.error("Gagal menyimpan catatan.");
  } finally {
    isSaving.value = false;
  }
};

const downloadPdf = async (studentId) => {
  try {
    const response = await reportService.downloadPdf(
      studentId,
      selectedAcademicYearId.value,
    );

    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;

    const contentDisposition = response.headers["content-disposition"];
    let fileName = "rapor.pdf";
    if (contentDisposition) {
      const fileNameMatch = contentDisposition.match(/filename="?(.+?)"?$/);
      if (fileNameMatch) {
        fileName = fileNameMatch[1];
      }
    }

    link.setAttribute("download", fileName);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    toastStore.success("PDF berhasil diunduh.");
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal mengunduh PDF.",
    );
  }
};

onMounted(async () => {
  try {
    const res = await reportService.getAcademicYears();
    academicYears.value = res.data?.data || res.data || [];
    const active = academicYears.value.find((ay) => ay.is_active);
    if (active) {
      selectedAcademicYearId.value = active.id;
      await loadStudents();
    }
  } catch (error) {
    toastStore.error("Gagal memuat tahun ajaran.");
  }
});
</script>
