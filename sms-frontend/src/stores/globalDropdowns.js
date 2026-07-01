import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';

/**
 * Global cache store for master/lookup data that rarely changes during a session.
 * Prevents redundant API calls across pages (academic years, teacher/student options, etc.)
 *
 * Dirty Flag System:
 *   - When data is mutated (CRUD), call invalidateX() or refreshX() which sets a dirty flag
 *   - Pages check dirty flags in onActivated to decide whether to refresh
 *   - consumeDirtyFlag(type) returns true if dirty and clears the flag atomically
 *
 * Usage:
 *   const dropdowns = useGlobalDropdownsStore();
 *   await dropdowns.ensureAcademicYears();  // fetches only if cache is empty
 *   dropdowns.academicYearOptions;           // pre-formatted for BaseSelect
 *   dropdowns.consumeDirtyFlag('academicYears'); // check+clear in onActivated
 */
export const useGlobalDropdownsStore = defineStore('globalDropdowns', () => {
  // ─── Raw Data ───
  const academicYearsRaw = ref([]);
  const teacherOptionsRaw = ref([]);
  const studentOptionsRaw = ref([]);
  const classesRaw = ref([]);
  const subjectsRaw = ref([]);

  // ─── Loading Flags (prevents duplicate concurrent requests) ───
  const loadingFlags = ref({
    academicYears: false,
    teachers: false,
    students: false,
    classes: false,
    subjects: false,
  });

  // ─── Dirty Flags (tracks which data types were mutated since last check) ───
  const dirtyFlags = ref({
    academicYears: false,
    teachers: false,
    students: false,
    classes: false,
    subjects: false,
    competencies: false,
  });

  // ─── Formatted Options for BaseSelect ───
  const academicYearOptions = computed(() =>
    academicYearsRaw.value.map((y) => ({
      value: y.id,
      label: `${y.name} ${y.semester === 'odd' ? '(Ganjil)' : '(Genap)'}${y.is_active ? ' - Aktif' : ''}`,
    }))
  );

  const teacherDropdownOptions = computed(() =>
    teacherOptionsRaw.value.map((t) => ({
      value: t.id,
      label: t.name,
    }))
  );

  const classDropdownOptions = computed(() =>
    classesRaw.value.map((c) => ({
      value: c.id,
      label: c.name || `Class #${c.id}`,
    }))
  );

  const subjectDropdownOptions = computed(() =>
    subjectsRaw.value.map((s) => ({
      value: s.id,
      label: s.name || `Subject #${s.id}`,
    }))
  );

  // ─── Active Year Helper ───
  const activeAcademicYear = computed(() =>
    academicYearsRaw.value.find((y) => y.is_active) || null
  );

  // ─── Dirty Flag Consumers (pages call these in onActivated) ───

  /**
   * Check if a data type is dirty AND clear the flag atomically.
   * Returns true if the data was mutated since last check.
   */
  function consumeDirtyFlag(type) {
    if (dirtyFlags.value[type]) {
      dirtyFlags.value[type] = false;
      return true;
    }
    return false;
  }

  /** Check if any data type is dirty (without clearing) */
  function isAnyDirty() {
    return Object.values(dirtyFlags.value).some(Boolean);
  }

  // ─── Fetch Functions (fetch only if cache is empty) ───

  async function ensureAcademicYears() {
    if (academicYearsRaw.value.length > 0 || loadingFlags.value.academicYears) return;
    loadingFlags.value.academicYears = true;
    try {
      const res = await api.get('/v1/admin/academic-years');
      academicYearsRaw.value = res.data.data || res.data;
    } finally {
      loadingFlags.value.academicYears = false;
    }
  }

  async function ensureTeacherOptions() {
    if (teacherOptionsRaw.value.length > 0 || loadingFlags.value.teachers) return;
    loadingFlags.value.teachers = true;
    try {
      const res = await api.get('/v1/admin/teachers/options');
      teacherOptionsRaw.value = res.data.data || res.data;
    } finally {
      loadingFlags.value.teachers = false;
    }
  }

  async function ensureStudentOptions() {
    if (studentOptionsRaw.value.length > 0 || loadingFlags.value.students) return;
    loadingFlags.value.students = true;
    try {
      const res = await api.get('/v1/admin/students/options');
      studentOptionsRaw.value = res.data.data || res.data;
    } finally {
      loadingFlags.value.students = false;
    }
  }

  async function ensureClasses() {
    if (classesRaw.value.length > 0 || loadingFlags.value.classes) return;
    loadingFlags.value.classes = true;
    try {
      // PERF FIX: use lightweight /classes/options endpoint (no relations, no withCount)
      const res = await api.get('/v1/admin/classes/options');
      classesRaw.value = res.data.data || res.data;
    } finally {
      loadingFlags.value.classes = false;
    }
  }

  async function ensureSubjects() {
    if (subjectsRaw.value.length > 0 || loadingFlags.value.subjects) return;
    loadingFlags.value.subjects = true;
    try {
      const res = await api.get('/v1/admin/subjects?per_page=200');
      subjectsRaw.value = res.data.data || res.data;
    } finally {
      loadingFlags.value.subjects = false;
    }
  }

  /** Force-refresh all cached data (nuclear option — prefer granular methods) */
  function invalidateAll() {
    academicYearsRaw.value = [];
    teacherOptionsRaw.value = [];
    studentOptionsRaw.value = [];
    classesRaw.value = [];
    subjectsRaw.value = [];
    // Mark all as dirty
    Object.keys(dirtyFlags.value).forEach((key) => {
      dirtyFlags.value[key] = true;
    });
  }

  // ─── Granular Invalidation (clear cache + set dirty flag) ───

  function invalidateAcademicYears() {
    academicYearsRaw.value = [];
    dirtyFlags.value.academicYears = true;
  }

  function invalidateTeachers() {
    teacherOptionsRaw.value = [];
    dirtyFlags.value.teachers = true;
  }

  function invalidateStudents() {
    studentOptionsRaw.value = [];
    dirtyFlags.value.students = true;
  }

  function invalidateClasses() {
    classesRaw.value = [];
    dirtyFlags.value.classes = true;
  }

  function invalidateSubjects() {
    subjectsRaw.value = [];
    dirtyFlags.value.subjects = true;
  }

  function invalidateCompetencies() {
    dirtyFlags.value.competencies = true;
  }

  // ─── Dynamic Refresh (clear + force-fetch + clear dirty flag) ───

  async function refreshAcademicYears() {
    academicYearsRaw.value = [];
    loadingFlags.value.academicYears = false;
    await ensureAcademicYears();
    dirtyFlags.value.academicYears = false; // data is now fresh
  }

  async function refreshTeacherOptions() {
    teacherOptionsRaw.value = [];
    loadingFlags.value.teachers = false;
    await ensureTeacherOptions();
    dirtyFlags.value.teachers = false;
  }

  async function refreshStudentOptions() {
    studentOptionsRaw.value = [];
    loadingFlags.value.students = false;
    await ensureStudentOptions();
    dirtyFlags.value.students = false;
  }

  async function refreshClasses() {
    classesRaw.value = [];
    loadingFlags.value.classes = false;
    await ensureClasses();
    dirtyFlags.value.classes = false;
  }

  async function refreshSubjects() {
    subjectsRaw.value = [];
    loadingFlags.value.subjects = false;
    await ensureSubjects();
    dirtyFlags.value.subjects = false;
  }

  return {
    // Raw data (for pages that need full objects)
    academicYearsRaw,
    teacherOptionsRaw,
    studentOptionsRaw,
    classesRaw,
    subjectsRaw,
    // Formatted options (for BaseSelect)
    academicYearOptions,
    teacherDropdownOptions,
    classDropdownOptions,
    subjectDropdownOptions,
    // Helpers
    activeAcademicYear,
    // Fetch (idempotent)
    ensureAcademicYears,
    ensureTeacherOptions,
    ensureStudentOptions,
    ensureClasses,
    ensureSubjects,
    // Granular Invalidation (clear + mark dirty)
    invalidateAll,
    invalidateAcademicYears,
    invalidateTeachers,
    invalidateStudents,
    invalidateClasses,
    invalidateSubjects,
    invalidateCompetencies,
    // Dynamic Refresh (clear + force-fetch + mark dirty)
    refreshAcademicYears,
    refreshTeacherOptions,
    refreshStudentOptions,
    refreshClasses,
    refreshSubjects,
    // Dirty Flag Consumers (for onActivated)
    consumeDirtyFlag,
    isAnyDirty,
  };
});
