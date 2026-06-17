import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';

/**
 * Global cache store for master/lookup data that rarely changes during a session.
 * Prevents redundant API calls across pages (academic years, teacher/student options, etc.)
 *
 * Usage:
 *   const dropdowns = useGlobalDropdownsStore();
 *   await dropdowns.ensureAcademicYears();  // fetches only if cache is empty
 *   dropdowns.academicYearOptions;           // pre-formatted for BaseSelect
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
      const res = await api.get('/v1/admin/classes?per_page=200');
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

  /** Force-refresh all cached data (e.g., after CRUD operations that change master data) */
  function invalidateAll() {
    academicYearsRaw.value = [];
    teacherOptionsRaw.value = [];
    studentOptionsRaw.value = [];
    classesRaw.value = [];
    subjectsRaw.value = [];
  }

  /** Force-refresh academic years only */
  function invalidateAcademicYears() {
    academicYearsRaw.value = [];
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
    // Invalidation
    invalidateAll,
    invalidateAcademicYears,
  };
});
