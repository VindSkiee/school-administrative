<template>
  <div class="space-y-6">
    <div
      class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md"
    >
      <h1 class="text-2xl md:text-3xl font-bold font-serif">
        Buku Nilai (Gradebook)
      </h1>
      <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
        Kelola dan input nilai siswa secara langsung. Nilai akan tersimpan
        otomatis saat Anda mengubah sel.
      </p>
    </div>

    <div
  class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col lg:flex-row gap-4 items-stretch lg:items-end w-full"
>
  <div class="flex-1 min-w-0 w-full">
    <label
      class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1"
      >Tahun Ajaran</label
    >
    <BaseSelect
      v-model="selectedAcademicYearId"
      :options="academicYearOptions"
      placeholder="Pilih Tahun Ajaran"
      @update:modelValue="onAcademicYearChange"
    />
  </div>

  <div class="flex-1 min-w-0 w-full">
    <label
      class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1"
    >
      {{
        isHomeroomMode
          ? "Mata Pelajaran (Filter)"
          : "Kelas & Mata Pelajaran"
      }}
    </label>
    <BaseSelect
      v-if="!isHomeroomMode"
      v-model="selectedScheduleId"
      :options="scheduleOptions"
      placeholder="Pilih Kelas & Mapel"
      :disabled="!selectedAcademicYearId"
      @update:modelValue="onScheduleChange"
    />
    <BaseSelect
      v-else
      v-model="selectedSubjectFilter"
      :options="homeroomSubjectFilterOptions"
      placeholder="Semua Mata Pelajaran"
      @update:modelValue="onSubjectFilterChange"
    />
  </div>

  <div class="w-full lg:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-2 shrink-0">
    <span
      v-if="gradingWeights && !isHomeroomMode && selectedScheduleId"
      class="text-[10px] font-bold text-gray-400 bg-gray-50 px-3 py-2.5 rounded-lg border border-gray-100 text-center sm:text-left lg:whitespace-nowrap"
    >
      Bobot: Tugas {{ gradingWeights.task }}% | UTS
      {{ gradingWeights.uts }}% | UAS {{ gradingWeights.uas }}% | Kehadiran
      {{ gradingWeights.attendance }}%
    </span>

    <button
      v-if="hasHomeroomClass"
      @click="toggleHomeroomMode"
      class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-lg border transition-colors whitespace-nowrap"
      :class="
        isHomeroomMode
          ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700 shadow-sm'
          : 'bg-white text-blue-600 border-blue-200 hover:bg-blue-50'
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
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
        ></path>
      </svg>
      {{
        isHomeroomMode ? "Kembali ke Input Nilai" : "Lihat Kelas Wali Saya"
      }}
    </button>
  </div>
</div>

    <div
      v-if="
        selectedAcademicYearId &&
        !isActiveYear &&
        selectedScheduleId &&
        !isHomeroomMode
      "
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
          Mode Baca Saja (Read-Only)
        </p>
        <p class="text-xs text-amber-600">
          Tahun ajaran ini tidak aktif. Pengeditan nilai hanya tersedia pada
          tahun ajaran aktif.
        </p>
      </div>
    </div>

    <div
      v-if="isReportPublished && isActiveYear && !isHomeroomMode"
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
          Rapor Sudah Diterbitkan — Mode Baca Saja
        </p>
        <p class="text-xs text-amber-600">
          Pengeditan nilai dikunci. Semua nilai tidak dapat diubah setelah rapor
          diterbitkan.
        </p>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center py-16"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"
      ></div>
      <p class="text-gray-500 font-medium text-sm">Memuat data Buku Nilai...</p>
    </div>

    <!-- ==================== HOMEROOM MODE ==================== -->
    <template v-else-if="isHomeroomMode">
      <div
        class="bg-white border border-blue-200 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center shrink-0"
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
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              ></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-800">
              Rekap Kelas Wali — {{ homeroomClassDisplayName }}
            </h3>
            <p class="text-xs text-gray-500">
              Nilai akhir per mata pelajaran. Mode ini hanya baca. Gunakan
              filter di atas untuk fokus pada mapel tertentu.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span
            class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-lg"
          >
            {{ homeroomRowData.length }} Siswa
          </span>
          <span
            class="px-2.5 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-lg"
          >
            {{ homeroomAllSubjects.length }} Mapel
          </span>
        </div>
      </div>

      <div
        v-if="isLoadingRecap"
        class="flex flex-col items-center justify-center py-16"
      >
        <div
          class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-blue-600 mb-3"
        ></div>
        <p class="text-gray-500 font-medium text-sm">
          Memuat rekap wali kelas...
        </p>
      </div>

      <div
        v-else
        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
      >
        <div class="ag-theme-alpine w-full" style="height: 600px">
          <ag-grid-vue
            v-if="homeroomColumnDefs.length > 0"
            theme="legacy"
            :rowData="homeroomRowData"
            :columnDefs="homeroomColumnDefs"
            :defaultColDef="defaultColDef"
            :animateRows="true"
            :suppressMovableColumns="false"
            style="width: 100%; height: 100%"
          />
        </div>
      </div>
    </template>

    <!-- ==================== EMPTY STATE ==================== -->
    <div
      v-else-if="!selectedScheduleId"
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
            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">
        Pilih Tahun Ajaran dan Kelas
      </h3>
      <p class="text-gray-500 mt-1 text-sm max-w-md mx-auto">
        Silakan pilih tahun ajaran dan kelas/mata pelajaran di atas untuk mulai
        melihat dan menginput nilai siswa.
      </p>
    </div>

    <!-- ==================== STANDARD GRADEBOOK MODE ==================== -->
    <div
      v-else
      class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
    >
      <div
        class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center justify-between gap-3"
      >
        <h3 class="text-sm font-bold text-gray-700">
          Tabel Nilai — {{ selectedScheduleLabel }}
        </h3>
        <div class="flex items-center gap-2">
          <span
            class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-lg"
          >
            {{ rowData.length }} Siswa
          </span>
          <span
            class="px-2.5 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-lg"
          >
            {{ assignmentColumns.length }} Penilaian
          </span>
        </div>
      </div>
      <div class="ag-theme-alpine w-full" style="height: 600px">
        <ag-grid-vue
          v-if="columnDefs.length > 0"
          theme="legacy"
          :rowData="rowData"
          :columnDefs="columnDefs"
          :defaultColDef="defaultColDef"
          :animateRows="true"
          :stopEditingWhenCellsLoseFocus="true"
          :singleClickEdit="true"
          :suppressMovableColumns="false"
          :rowSelection="'single'"
          :getRowClass="getRowClass"
          @cell-value-changed="onCellValueChanged"
          @grid-ready="onGridReady"
          style="width: 100%; height: 100%"
        />
      </div>
    </div>

    <div
      v-if="savingIndicator"
      class="fixed bottom-6 right-6 z-50 bg-green-600 text-white px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2 text-sm font-bold animate-fade-in-up"
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
          d="M5 13l4 4L19 7"
        ></path>
      </svg>
      Nilai Tersimpan
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, shallowRef } from "vue";
import { AgGridVue } from "ag-grid-vue3";
import { 
  ModuleRegistry, 
  ClientSideRowModelModule, 
  CellStyleModule, 
  ColumnAutoSizeModule, 
  RowSelectionModule,
} from 'ag-grid-community';
import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-alpine.css";
import { assignmentService } from "../../services/modules/teacher/assignmentService";
import { useToastStore } from "../../stores/toast";
import { useReportStatus } from "../../composables/useReportStatus";
import BaseSelect from "../../components/BaseSelect.vue";
import api from "../../services/api";

ModuleRegistry.registerModules([
  ClientSideRowModelModule, 
  CellStyleModule, 
  ColumnAutoSizeModule, 
  RowSelectionModule,
]);

const toastStore = useToastStore();
const { isReportPublished } = useReportStatus("teacher");

// ==================== STATE ====================
const isLoading = ref(false);
const gridApi = shallowRef(null);

// Filter state
const academicYears = ref([]);
const teacherSchedules = ref([]);
const selectedAcademicYearId = ref("");
const selectedScheduleId = ref("");

// Gradebook data
const students = ref([]);
const assignments = ref([]);
const gradingWeights = ref(null);

// Save indicator
const savingIndicator = ref(false);
let savingTimeout = null;

// Homeroom mode state
const isHomeroomMode = ref(false);
const isLoadingRecap = ref(false);
const homeroomRecapData = ref([]);
const homeroomAllSubjects = ref([]);
const selectedSubjectFilter = ref("");

// ==================== HELPERS ====================
const extractClassName = (s) => {
  if (!s) return "";
  return (
    s.class_name ||
    s.class?.name ||
    s.schoolClass?.name ||
    s.school_class?.name ||
    ""
  );
};

const extractSubjectName = (s) => {
  if (!s) return "";
  return s.subject_name || s.subject?.name || "";
};

// ==================== COMPUTED — FILTERS ====================
const academicYearOptions = computed(() => {
  return academicYears.value.map((ay) => ({
    value: ay.id,
    label: `${ay.name || ay.year || "Tahun Ajaran"} — Semester ${ay.semester === "odd" ? "Ganjil" : "Genap"}${ay.is_active ? " (Aktif)" : ""}`,
  }));
});

const scheduleOptions = computed(() => {
  return teacherSchedules.value.map((s) => ({
    value: s.id,
    label: `${extractClassName(s) || "Kelas"} — ${extractSubjectName(s) || "Mapel"}`,
  }));
});

const selectedSchedule = computed(() => {
  return (
    teacherSchedules.value.find((s) => s.id === selectedScheduleId.value) ||
    null
  );
});

const selectedScheduleLabel = computed(() => {
  const s = selectedSchedule.value;
  if (!s) return "";
  return `${extractClassName(s) || "Kelas"} — ${extractSubjectName(s) || "Mapel"}`;
});

const selectedYear = computed(() => {
  return academicYears.value.find(
    (ay) => ay.id === selectedAcademicYearId.value,
  );
});

const isActiveYear = computed(() => {
  return selectedYear.value?.is_active === true;
});

// ==================== HOMEROOM COMPUTED ====================
const homeroomSchedule = computed(() => {
  return teacherSchedules.value.find((s) => s.is_homeroom === true) || null;
});

const hasHomeroomClass = computed(() => {
  return homeroomSchedule.value !== null;
});

const homeroomClassId = computed(() => {
  return homeroomSchedule.value?.class_id || null;
});

const homeroomClassDisplayName = computed(() => {
  return extractClassName(homeroomSchedule.value) || "";
});

const homeroomSubjectFilterOptions = computed(() => {
  const opts = [{ value: "", label: "Semua Mata Pelajaran" }];
  homeroomAllSubjects.value.forEach((sub) => {
    opts.push({ value: sub.id, label: sub.name });
  });
  return opts;
});

const filteredHomeroomSubjects = computed(() => {
  if (!selectedSubjectFilter.value) return homeroomAllSubjects.value;
  return homeroomAllSubjects.value.filter(
    (s) => s.id === selectedSubjectFilter.value,
  );
});

const assignmentColumns = computed(() => {
  const tasks = assignments.value.filter((a) => a.type === "task");
  const uts = assignments.value.filter((a) => a.type === "uts");
  const uas = assignments.value.filter((a) => a.type === "uas");
  return [...tasks, ...uts, ...uas];
});

const typeLabel = (type) => {
  switch (type) {
    case "uts":
      return "UTS";
    case "uas":
      return "UAS";
    default:
      return "Tugas";
  }
};

const typeColor = (type) => {
  switch (type) {
    case "uts":
      return "bg-brand-orange/10 text-brand-orange";
    case "uas":
      return "bg-brand-red/10 text-brand-red";
    default:
      return "bg-blue-50 text-blue-700";
  }
};

// ==================== DEFAULT COL DEF ====================
const defaultColDef = {
  resizable: true,
  sortable: true,
  minWidth: 100,
  wrapHeaderText: true, // Membungkus teks header yang panjang
  autoHeaderHeight: true, // Menyesuaikan tinggi header otomatis
};

// ==================== STANDARD GRADEBOOK COLUMNS ====================
const columnDefs = computed(() => {
  if (!selectedScheduleId.value) return [];

  const cols = [];

  cols.push({
    headerName: "No",
    valueGetter: (params) => (params.node ? params.node.rowIndex + 1 : ""),
    pinned: "left",
    width: 70,
    resizable: false,
    sortable: false,
    cellStyle: { textAlign: "center" },
  });

  cols.push({
    headerName: "Nama Siswa",
    field: "name",
    pinned: "left",
    width: 200,
    headerClass: "header-align-left",
    cellStyle: { fontWeight: "bold" },
  });

  cols.push({
    headerName: "Kehadiran %",
    field: "attendance_rate",
    width: 120,
    cellStyle: (params) => {
      const val = Number(params.value);
      if (isNaN(val)) return { textAlign: "center", color: "#9ca3af" };
      if (val >= 90)
        return { textAlign: "center", color: "#16a34a", fontWeight: "bold" };
      if (val >= 75)
        return { textAlign: "center", color: "#d97706", fontWeight: "bold" };
      return { textAlign: "center", color: "#E02E2B", fontWeight: "bold" };
    },
    valueFormatter: (params) =>
      params.value != null ? `${params.value}%` : "-",
  });

  const editable = isActiveYear.value && !isReportPublished.value;

  assignmentColumns.value.forEach((assignment) => {
    const colId = `assignment_${assignment.id}`;
    cols.push({
      colId,
      // PERBAIKAN: Tambahkan label tipe di depan judul tugas
      headerName: `[${typeLabel(assignment.type)}] ${assignment.title}`,
      headerTooltip: `${typeLabel(assignment.type)}: ${assignment.title}`,
      headerClass: typeColor(assignment.type),
      width: 140,
      editable,
      cellDataType: "number",
      // ... (biarkan sisa konfigurasi di bawahnya tetap sama seperti valueGetter, cellStyle, dll)
      valueGetter: (params) => {
        const raw = params.data?.assignments?.[assignment.id];
        if (raw == null || raw === "") return null;
        const num = Number(raw);
        return isNaN(num) ? null : num;
      },
      valueSetter: (params) => {
        if (params.newValue == null || params.newValue === "") {
          if (!params.data.assignments) params.data.assignments = {};
          params.data.assignments[assignment.id] = null;
          return true;
        }
        const newVal = Number(params.newValue);
        if (isNaN(newVal) || newVal < 0 || newVal > 100) {
          toastStore.error("Nilai harus antara 0 - 100.");
          return false;
        }
        if (!params.data.assignments) params.data.assignments = {};
        params.data.assignments[assignment.id] = newVal;
        return true;
      },
      cellStyle: (params) => {
        if (!editable) return { textAlign: "center", color: "#9ca3af" };
        const val = params.value;
        if (val == null) return { textAlign: "center", color: "#d1d5db" };
        if (val >= 75) return { textAlign: "center", color: "#16a34a" };
        return { textAlign: "center", color: "#E02E2B" };
      },
      valueFormatter: (params) =>
        params.value != null ? String(params.value) : "—",
      cellEditorSelector: () => {
        return { component: "agNumberCellEditor", popup: false };
      },
    });
  });

  cols.push({
    headerName: "Nilai Akhir",
    pinned: "right",
    width: 150,
    cellStyle: {
      fontWeight: "bold",
      backgroundColor: "#f9fafb",
      textAlign: "center",
    },
    valueGetter: (params) => calculateWeightedAverage(params.data),
    valueFormatter: (params) => {
      const val = params.value;
      if (val == null || isNaN(val)) return "—";
      return Number(val).toFixed(1);
    },
  });

  return cols;
});

// ==================== HOMEROOM RECAP COLUMNS ====================
const homeroomColumnDefs = computed(() => {
  if (homeroomRecapData.value.length === 0) return [];

  const cols = [];

  cols.push({
    headerName: "No",
    valueGetter: (params) => (params.node ? params.node.rowIndex + 1 : ""),
    pinned: "left",
    width: 60,
    resizable: false,
    sortable: false,
    cellStyle: { textAlign: "center" },
  });

  cols.push({
    headerName: "NIS",
    field: "nis",
    pinned: "left",
    width: 100,
  });

  cols.push({
    headerName: "Nama Siswa",
    field: "name",
    pinned: "left",
    width: 200,
    headerClass: "header-align-left",
    cellStyle: { fontWeight: "bold" },
  });

  cols.push({
    headerName: "Kehadiran %",
    field: "attendance_rate",
    width: 120,
    cellStyle: (params) => {
      const val = Number(params.value);
      if (isNaN(val)) return { textAlign: "center", color: "#9ca3af" };
      if (val >= 90)
        return { textAlign: "center", color: "#16a34a", fontWeight: "bold" };
      if (val >= 75)
        return { textAlign: "center", color: "#d97706", fontWeight: "bold" };
      return { textAlign: "center", color: "#E02E2B", fontWeight: "bold" };
    },
    valueFormatter: (params) =>
      params.value != null ? `${params.value}%` : "-",
  });

  filteredHomeroomSubjects.value.forEach((subject) => {
    cols.push({
      headerName: subject.name,
      headerTooltip: subject.name,
      width: 130,
      editable: false,
      valueGetter: (params) => {
        const raw = params.data?.subjects?.[subject.id];
        if (raw == null || raw === "") return null;
        const num = Number(raw);
        return isNaN(num) ? null : num;
      },
      cellStyle: (params) => {
        const val = params.value;
        if (val == null) return { textAlign: "center", color: "#d1d5db" };
        if (val >= 75) return { textAlign: "center", color: "#16a34a" };
        return { textAlign: "center", color: "#E02E2B" };
      },
      valueFormatter: (params) =>
        params.value != null ? Number(params.value).toFixed(1) : "—",
    });
  });

  cols.push({
    headerName: "Rata-rata",
    pinned: "right",
    width: 130,
    editable: false,
    cellStyle: {
      fontWeight: "bold",
      backgroundColor: "#f9fafb",
      textAlign: "center",
    },
    valueGetter: (params) => {
      if (!params.data?.subjects) return null;
      const scores = Object.values(params.data.subjects)
        .map((v) => Number(v))
        .filter((v) => !isNaN(v) && v != null);
      if (scores.length === 0) return null;
      return (
        Math.round((scores.reduce((a, b) => a + b, 0) / scores.length) * 100) /
        100
      );
    },
    valueFormatter: (params) => {
      const val = params.value;
      if (val == null || isNaN(val)) return "—";
      return Number(val).toFixed(1);
    },
  });

  return cols;
});

// ==================== ROW DATA ====================
const rowData = computed(() => students.value.map((s) => ({ ...s })));
const homeroomRowData = computed(() =>
  homeroomRecapData.value.map((s) => ({ ...s })),
);

// ==================== WEIGHTED AVERAGE CALCULATION ====================
const calculateWeightedAverage = (studentData) => {
  if (!studentData?.assignments) return null;

  const weights = gradingWeights.value || {
    task: 40,
    uts: 25,
    uas: 25,
    attendance: 10,
  };
  const cols = assignmentColumns.value;

  const scoresByType = { task: [], uts: [], uas: [] };

  cols.forEach((assignment) => {
    const raw = studentData.assignments[assignment.id];
    if (raw != null && raw !== "") {
      const num = Number(raw);
      if (!isNaN(num)) {
        scoresByType[assignment.type].push(num);
      }
    }
  });

  const avg = (arr) =>
    arr.length > 0 ? arr.reduce((a, b) => a + b, 0) / arr.length : null;
  const taskAvg = avg(scoresByType.task);
  const utsAvg = avg(scoresByType.uts);
  const uasAvg = avg(scoresByType.uas);

  // Attendance rate — always available from the row data
  const attendanceRate = Number(studentData.attendance_rate) || 0;

  let activeWeight = 0;
  let weightedSum = 0;

  if (taskAvg !== null) {
    weightedSum += taskAvg * weights.task;
    activeWeight += weights.task;
  }
  if (utsAvg !== null) {
    weightedSum += utsAvg * weights.uts;
    activeWeight += weights.uts;
  }
  if (uasAvg !== null) {
    weightedSum += uasAvg * weights.uas;
    activeWeight += weights.uas;
  }

  // Attendance is always included if its weight > 0
  if (weights.attendance > 0) {
    weightedSum += attendanceRate * weights.attendance;
    activeWeight += weights.attendance;
  }

  if (activeWeight === 0) return null;
  const result = weightedSum / activeWeight;
  return isNaN(result) ? null : Math.round(result * 100) / 100;
};

// ==================== GRID EVENTS ====================
// UX: Row highlight — selected row stays highlighted via AG Grid row selection
const getRowClass = (params) => {
  return params.node.isSelected() ? "ag-row-selected" : "";
};

const onGridReady = (params) => {
  gridApi.value = params.api;

  // PERBAIKAN: Paksa grid untuk menyesuaikan lebar kolom dengan lebar layar
  // setTimeout digunakan agar DOM selesai di-render sebelum ukuran dihitung ulang
  setTimeout(() => {
    if (params.api) {
      params.api.sizeColumnsToFit();
    }
  }, 100);
};

// PERF FIX: per-cell debounce — prevents request flood when tabbing between cells
const pendingSaves = new Map();

const onCellValueChanged = (params) => {
  if (!isActiveYear.value || isReportPublished.value) return;

  const colId = params.column.getColId();
  if (!colId || !colId.startsWith("assignment_")) return;

  const assignmentId = colId.replace("assignment_", "");
  const studentId = params.data.id;
  const rawScore = params.data.assignments?.[Number(assignmentId)];
  const newScore =
    rawScore != null && rawScore !== "" ? Number(rawScore) : null;

  // PERF FIX: debounce per cell key — 600ms after last edit
  const cellKey = `${studentId}_${assignmentId}`;

  if (pendingSaves.has(cellKey)) {
    clearTimeout(pendingSaves.get(cellKey));
  }

  pendingSaves.set(
    cellKey,
    setTimeout(async () => {
      try {
        await saveScoreSilently(studentId, assignmentId, newScore);
      } catch (e) {
        toastStore.error("Gagal menyimpan nilai.");
      } finally {
        pendingSaves.delete(cellKey);
      }
    }, 600),
  );

  // Force grid to recalculate final grade column
  if (gridApi.value) {
    gridApi.value.refreshCells({
      rowNodes: [params.node],
      columns: ["Nilai Akhir"],
      force: true,
    });
  }
};

const saveScoreSilently = async (studentId, assignmentId, score) => {
  try {
    await assignmentService.inlineSaveGrade({
      student_id: studentId,
      assignment_id: Number(assignmentId),
      score: score,
    });
    showSavingIndicator();
  } catch (error) {
    toastStore.error("Gagal menyimpan nilai. Silakan coba lagi.");
  }
};

const showSavingIndicator = () => {
  savingIndicator.value = true;
  if (savingTimeout) clearTimeout(savingTimeout);
  savingTimeout = setTimeout(() => {
    savingIndicator.value = false;
  }, 2000);
};

// ==================== DATA LOADING ====================
const loadAcademicYears = async () => {
  try {
    const res = await api.get("/v1/teacher/gradebook/academic-years");
    academicYears.value = res.data?.data || res.data || [];
    const active = academicYears.value.find((ay) => ay.is_active);
    if (active) selectedAcademicYearId.value = active.id;
  } catch (error) {
    toastStore.error("Gagal memuat tahun ajaran.");
  }
};

const loadSchedules = async () => {
  if (!selectedAcademicYearId.value) return;
  try {
    const res = await api.get("/v1/teacher/gradebook/schedules", {
      params: { academic_year_id: selectedAcademicYearId.value },
    });
    teacherSchedules.value = res.data?.data || res.data || [];
  } catch (error) {
    toastStore.error("Gagal memuat jadwal mengajar.");
  }
};

const loadGradebook = async () => {
  if (!selectedScheduleId.value || !selectedAcademicYearId.value) return;

  isLoading.value = true;
  try {
    const res = await api.get("/v1/teacher/gradebook", {
      params: {
        schedule_id: selectedScheduleId.value,
        academic_year_id: selectedAcademicYearId.value,
      },
    });
    const payload = res.data?.data || res.data;
    assignments.value = payload.assignments || [];
    students.value = (payload.students || []).map((s) => ({
      ...s,
      assignments: s.assignments || {},
    }));
    gradingWeights.value = payload.weights || {
      task: 40,
      uts: 25,
      uas: 25,
      attendance: 10,
    };
  } catch (error) {
    toastStore.error("Gagal memuat data gradebook.");
  } finally {
    isLoading.value = false;
  }
};

const loadHomeroomRecap = async () => {
  if (!homeroomClassId.value || !selectedAcademicYearId.value) return;

  isLoadingRecap.value = true;
  try {
    const res = await api.get("/v1/teacher/homeroom/gradebook-recap", {
      params: {
        class_id: homeroomClassId.value,
        academic_year_id: selectedAcademicYearId.value,
      },
    });
    const payload = res.data?.data || res.data;
    homeroomAllSubjects.value = payload.subjects || [];
    homeroomRecapData.value = (payload.students || []).map((s) => ({
      ...s,
      subjects: s.subjects || {},
    }));
  } catch (error) {
    toastStore.error("Gagal memuat rekap wali kelas.");
  } finally {
    isLoadingRecap.value = false;
  }
};

// ==================== EVENT HANDLERS ====================
const onAcademicYearChange = () => {
  selectedScheduleId.value = "";
  students.value = [];
  assignments.value = [];
  gradingWeights.value = null;
  isHomeroomMode.value = false;
  homeroomRecapData.value = [];
  homeroomAllSubjects.value = [];
  selectedSubjectFilter.value = "";
  loadSchedules();
};

const onScheduleChange = () => {
  students.value = [];
  assignments.value = [];
  loadGradebook();
};

const onSubjectFilterChange = () => {
  // The homeroomColumnDefs computed will react to filteredHomeroomSubjects change
};

const toggleHomeroomMode = () => {
  isHomeroomMode.value = !isHomeroomMode.value;

  if (isHomeroomMode.value) {
    // Entering homeroom mode
    selectedScheduleId.value = "";
    selectedSubjectFilter.value = "";
    students.value = [];
    assignments.value = [];
    loadHomeroomRecap();
  } else {
    // Exiting homeroom mode
    homeroomRecapData.value = [];
    homeroomAllSubjects.value = [];
    selectedSubjectFilter.value = "";
  }
};

// ==================== LIFECYCLE ====================
onMounted(async () => {
  await loadAcademicYears();
  if (selectedAcademicYearId.value) {
    await loadSchedules();
  }
  if (selectedAcademicYearId.value && selectedScheduleId.value) {
    loadGradebook();
  }
});

onBeforeUnmount(() => {
  // PERF FIX: clear all pending debounced saves on unmount
  pendingSaves.forEach((timer) => clearTimeout(timer));
  pendingSaves.clear();
  if (savingTimeout) clearTimeout(savingTimeout);
});
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

:deep(.ag-theme-alpine) {
  --ag-header-background-color: #f9fafb;
  --ag-header-foreground-color: #374151;
  --ag-header-font-weight: 700;
  --ag-header-font-size: 12px;
  --ag-row-hover-color: #f3f4f6;
  --ag-border-color: #e5e7eb;
  --ag-cell-horizontal-padding: 12px;
}

:deep(.ag-theme-alpine .ag-cell) {
  font-size: 13px;
}

:deep(.ag-theme-alpine .ag-cell-editing) {
  border: 2px solid #e02e2b;
  border-radius: 4px;
}

/* Row highlight: selected row stays highlighted until another row is clicked */
/* 1. Warna latar dan teks tebal untuk baris yang dipilih */
:deep(.ag-theme-alpine .ag-row-selected) {
  background-color: #ffffff !important;
  font-weight: 700 !important;
}

/* 2. Garis luar Atas dan Bawah untuk semua cell di baris tersebut */
:deep(.ag-theme-alpine .ag-row-selected .ag-cell) {
  border-top: 0.5px solid #000000 !important;
  border-bottom: 0.5px solid #000000 !important;
}

/* 3. Garis luar Kiri HANYA untuk kolom paling ujung kiri (Kolom 'No') */
:deep(.ag-pinned-left-cols-container .ag-row-selected .ag-cell:first-child) {
  border-left: 0.5px solid #000000 !important;
}

/* 4. Garis luar Kanan HANYA untuk kolom paling ujung kanan (Kolom 'Nilai Akhir') */
:deep(.ag-pinned-right-cols-container .ag-row-selected .ag-cell:last-child) {
  border-right: 0.5px solid #000000 !important;
}

/* Memastikan warna font tidak berubah, dan memaksa input box/cell edit tetap mengikuti style */
:deep(.ag-theme-alpine .ag-row-selected .ag-cell) {
  color: red !important; /* Override warna teks saat row dipilih */
  font-weight: inherit; 
}

:deep(.ag-theme-alpine .ag-header-cell-comp-wrapper) {
  white-space: normal;
  line-height: 1.3;
}

:deep(.bg-blue-50) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.bg-brand-orange\/10) {
  background-color: rgba(198, 103, 22, 0.1);
}

:deep(.bg-brand-red\/10) {
  background-color: rgba(224, 46, 43, 0.1);
}

/* Tengahkan SEMUA teks di thead (Header) */
:deep(.ag-header-cell-label) {
  justify-content: center !important;
  text-align: center;
}

/* Rata kiri KHUSUS untuk thead yang diberi class header-align-left */
:deep(.header-align-left .ag-header-cell-label) {
  justify-content: flex-start !important;
  text-align: left !important;
}

</style>
