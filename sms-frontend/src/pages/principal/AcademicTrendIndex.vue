<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800 font-serif">Analisis Tren Akademik</h1>
      <p class="text-gray-500 text-sm mt-1">
        Pantau perkembangan mutu kurikulum dan progres kognitif angkatan.
      </p>
    </div>

    <!-- Control Panel -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <!-- Pill Toggle -->
      <div class="bg-gray-100 rounded-xl p-1 flex shrink-0">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-5 py-2 rounded-lg text-xs font-bold transition-all"
          :class="activeTab === tab.id
            ? 'bg-white text-gray-800 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Contextual Dropdown -->
      <div class="w-full sm:w-56 shrink-0">
        <BaseSelect
          v-model="selectedFilter"
          :options="currentOptions"
          :placeholder="activeTab === 'curriculum' ? 'Pilih Kelas' : 'Pilih Angkatan'"
        />
      </div>

      <!-- Info Badge -->
      <div v-if="activeTab === 'curriculum'" class="text-[10px] text-gray-400 font-medium ml-auto hidden sm:block">
        Menampilkan data tahun ajaran dengan rapor diterbitkan
      </div>
      <div v-else class="text-[10px] text-gray-400 font-medium ml-auto hidden sm:block">
        Melacak progres angkatan dari Kelas 7 → 9
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Left Column: Chart (8/12) -->
      <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h3 class="text-sm font-bold text-gray-800">{{ chartTitle }}</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ chartSubtitle }}</p>
          </div>
          <div
            v-if="trendDelta !== null"
            class="px-3 py-1.5 rounded-lg text-xs font-bold"
            :class="trendDelta > 0 ? 'bg-emerald-50 text-emerald-600' : trendDelta < 0 ? 'bg-red-50 text-red-500' : 'bg-gray-50 text-gray-500'"
          >
            {{ trendDelta > 0 ? '+' : '' }}{{ trendDelta }}
          </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex-1 flex items-center justify-center min-h-[300px]">
          <div class="flex flex-col items-center gap-3">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-gray-100 border-t-indigo-500"></div>
            <p class="text-xs text-gray-400 font-medium">Memuat data tren...</p>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex-1 flex flex-col items-center justify-center min-h-[300px] text-center">
          <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-gray-500">{{ error }}</p>
        </div>

        <!-- Empty -->
        <div v-else-if="!chartSeries[1]?.data?.length" class="flex-1 flex flex-col items-center justify-center min-h-[300px] text-center">
          <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <p class="text-sm text-gray-500 font-medium">Belum ada data untuk ditampilkan.</p>
          <p class="text-xs text-gray-400 mt-1">
            {{ activeTab === 'cohort'
              ? 'Belum ada tahun ajaran yang memiliki rapor Ganjil dan Genap diterbitkan secara bersamaan.'
              : 'Pastikan rapor telah diterbitkan untuk tahun ajaran terkait.' }}
          </p>
        </div>

        <!-- No Combined Years (both tabs) -->
        <div v-else-if="!hasCombinedYears" class="flex-1 flex flex-col items-center justify-center min-h-[300px] text-center px-6">
          <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <p class="text-sm font-bold text-gray-700 mb-2">
            {{ activeTab === 'curriculum' ? 'Data Kurikulum Belum Tersedia' : 'Data Angkatan Belum Tersedia' }}
          </p>
          <p class="text-xs text-gray-500 leading-relaxed max-w-xs">
            {{ activeTab === 'curriculum'
              ? 'Tren kurikulum membutuhkan tahun ajaran yang memiliki rapor semester Ganjil dan Genap yang keduanya sudah diterbitkan. Saat ini belum ada tahun ajaran yang memenuhi syarat tersebut.'
              : 'Tren angkatan membutuhkan tahun ajaran yang memiliki rapor semester Ganjil dan Genap yang keduanya sudah diterbitkan. Saat ini belum ada tahun ajaran yang memenuhi syarat tersebut.' }}
          </p>
        </div>

        <!-- Chart -->
        <div v-else class="flex-1 min-h-0">
          <apexchart
            type="area"
            :height="320"
            :options="chartOptions"
            :series="chartSeries"
          />
        </div>
      </div>

      <!-- Right Column: Demographics (4/12) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Population Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Populasi Siswa</p>
          <p v-if="demographicsLabel" class="text-[10px] text-gray-400 mb-4">{{ demographicsLabel }}</p>

          <div v-if="!currentDemo" class="flex flex-col items-center justify-center py-8 text-center">
            <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-xs text-gray-400">Tidak ada data demografi.</p>
          </div>

          <template v-else>
            <p class="text-4xl font-black text-gray-800 mb-1">{{ currentDemo.total }}</p>
            <p class="text-xs text-gray-400 mb-5">Total siswa terdaftar</p>

            <!-- Gender Breakdown -->
            <div class="space-y-3">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-xs font-semibold text-blue-600 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    Laki-laki
                  </span>
                  <span class="text-xs font-bold text-gray-700">{{ currentDemo.male }}</span>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500 rounded-full transition-all duration-500" :style="{ width: malePercent + '%' }"></div>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 text-right">{{ malePercent }}%</p>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-xs font-semibold text-pink-500 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-pink-400"></span>
                    Perempuan
                  </span>
                  <span class="text-xs font-bold text-gray-700">{{ currentDemo.female }}</span>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-pink-400 rounded-full transition-all duration-500" :style="{ width: femalePercent + '%' }"></div>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 text-right">{{ femalePercent }}%</p>
              </div>
            </div>
          </template>
        </div>

        <!-- Context Info Card -->
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-5 border border-indigo-100">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-xs font-bold text-indigo-800 mb-1">{{ infoCardTitle }}</p>
              <p class="text-[11px] text-indigo-600/80 leading-relaxed">{{ infoCardText }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { principalService } from '../../services/modules/principal/principalService';
import { useToastStore } from '../../stores/toast';
import BaseSelect from '../../components/BaseSelect.vue';

const apexchart = VueApexCharts;
const toastStore = useToastStore();

// ==================== STATE ====================
const activeTab = ref('curriculum');
const selectedFilter = ref('');
const isLoading = ref(false);
const error = ref(null);

const chartCategories = ref([]);
const chartSeriesData = ref([]);
const demographics = ref(null);
const cohortYears = ref([]);
const gradeDemographics = ref([]);
const curriculumHasCombinedYears = ref(true); // Default true until API says otherwise

// ==================== TABS ====================
const tabs = [
  { id: 'curriculum', label: 'Tren Kurikulum' },
  { id: 'cohort', label: 'Tren Angkatan' },
];

const gradeOptions = [
  { value: 7, label: 'Kelas 7' },
  { value: 8, label: 'Kelas 8' },
  { value: 9, label: 'Kelas 9' },
];

const cohortOptions = computed(() => {
  return cohortYears.value.map(y => ({
    value: y.id,
    label: `Angkatan ${y.name}`,
  }));
});

const currentOptions = computed(() => {
  return activeTab.value === 'curriculum' ? gradeOptions : cohortOptions.value;
});

/** Whether any combined cohort years exist */
const hasCohortData = computed(() => cohortYears.value.length > 0);

/** Whether the current tab has combined years available */
const hasCombinedYears = computed(() => {
  if (activeTab.value === 'curriculum') {
    // Curriculum: check from API response (set in fetchData)
    return curriculumHasCombinedYears.value;
  }
  // Cohort: check from loaded cohort years
  return cohortYears.value.length > 0;
});

// ==================== COMPUTED — DISPLAY ====================
const chartTitle = computed(() => {
  if (activeTab.value === 'curriculum') {
    const g = gradeOptions.find(o => o.value === Number(selectedFilter.value));
    return `Tren Indeks ${g?.label || ''} — Tahun ke Tahun`;
  }
  const y = cohortYears.value.find(c => c.id === Number(selectedFilter.value));
  return `Progres Kognitif Angkatan ${y?.name || ''}`;
});

const chartSubtitle = computed(() => {
  return activeTab.value === 'curriculum'
    ? 'Rata-rata performa pada tahun ajaran dengan rapor diterbitkan'
    : 'Rata-rata nilai per tingkat kelas (7 → 8 → 9)';
});

const demographicsLabel = computed(() => {
  if (activeTab.value === 'curriculum' && demographics.value?.length) {
    const latest = demographics.value[demographics.value.length - 1];
    return `Data terbaru: ${latest.academic_year}`;
  }
  if (activeTab.value === 'cohort') return 'Populasi aktif angkatan saat ini';
  return '';
});

/** Current demographics object (latest year for curriculum, single object for cohort) */
const currentDemo = computed(() => {
  if (!demographics.value) return null;
  if (activeTab.value === 'curriculum' && Array.isArray(demographics.value)) {
    return demographics.value.length ? demographics.value[demographics.value.length - 1] : null;
  }
  if (activeTab.value === 'cohort' && !Array.isArray(demographics.value)) {
    return demographics.value;
  }
  return null;
});

const malePercent = computed(() => {
  const d = currentDemo.value;
  if (!d || d.total === 0) return 0;
  return Math.round((d.male / d.total) * 100);
});

const femalePercent = computed(() => {
  const d = currentDemo.value;
  if (!d || d.total === 0) return 0;
  return 100 - malePercent.value;
});

const trendDelta = computed(() => {
  const data = chartSeriesData.value;
  if (!data || data.length < 2) return null;
  const valid = data.filter(v => v !== null);
  if (valid.length < 2) return null;
  return +(valid[valid.length - 1] - valid[valid.length - 2]).toFixed(1);
});

const infoCardTitle = computed(() => {
  return activeTab.value === 'curriculum' ? 'Evaluasi Kurikulum' : 'Evaluasi Angkatan';
});

const infoCardText = computed(() => {
  return activeTab.value === 'curriculum'
    ? 'Membandingkan performa rata-rata tingkat kelas yang sama dari tahun ke tahun. Berguna untuk mengukur efektivitas kurikulum dan metode pengajaran.'
    : 'Melacak pertumbuhan kognitif satu angkatan siswa dari Kelas 7 hingga Kelas 9. Berguna untuk mengevaluasi konsistensi mutu pengajaran.';
});

// ==================== CHART ====================
const chartSeries = computed(() => [
  {
    name: 'Garis Vertikal',
    type: 'bar',
    data: chartSeriesData.value,
  },
  {
    name: activeTab.value === 'curriculum' ? 'Indeks Kurikulum' : 'Indeks Angkatan',
    type: 'area',
    data: chartSeriesData.value,
  },
]);

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  colors: ['#6366f1', '#6366f1'],
  fill: {
    type: ['solid', 'gradient'],
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.35,
      opacityTo: 0.05,
      stops: [0, 100],
    },
  },
  stroke: {
    curve: 'smooth',
    width: [0, 3],
  },
  plotOptions: {
    bar: {
      columnWidth: '3px',
      borderRadius: 0,
    },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: chartCategories.value,
    labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    min: 0,
    max: 100,
    tickAmount: 5,
    labels: {
      style: { fontSize: '11px', colors: '#9ca3af' },
      formatter: (val) => val.toFixed(0),
    },
  },
  grid: {
    borderColor: 'rgba(156, 163, 175, 0.2)',
    strokeDashArray: 0,
    padding: { left: 8, right: 8 },
    yaxis: { lines: { show: true } },
  },
  markers: {
    size: [0, 5],
    colors: ['#6366f1'],
    strokeColors: '#fff',
    strokeWidth: 2,
    hover: { size: 7 },
  },
  tooltip: {
    shared: true,
    intersect: false,
    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
      const idx = dataPointIndex;
      const val = series[seriesIndex]?.[idx];
      if (val == null) return '';
      const cat = w.globals.categoryLabels?.[idx] || w.config.xaxis.categories?.[idx] || '';
      const numVal = Number(val).toFixed(1);

      if (activeTab.value === 'curriculum') {
        const demo = demographics.value?.[idx];
        const total = demo?.total || 0;
        const male = demo?.male || 0;
        const female = demo?.female || 0;
        return `<div style="padding:10px 14px;font-family:Inter,system-ui,sans-serif;min-width:170px;">
          <p style="font-size:11px;font-weight:700;color:#374151;margin:0 0 6px;">${cat}</p>
          <p style="font-size:20px;font-weight:900;color:#6366f1;margin:0 0 8px;">${numVal}</p>
          <div style="border-top:1px solid #f3f4f6;padding-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:10px;color:#6b7280;margin-bottom:3px;">
              <span>Total Siswa</span><span style="font-weight:700;color:#374151;">${total}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:10px;color:#6b7280;margin-bottom:3px;">
              <span>♂ Laki-laki</span><span style="font-weight:700;color:#3b82f6;">${male}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:10px;color:#6b7280;">
              <span>♀ Perempuan</span><span style="font-weight:700;color:#ec4899;">${female}</span>
            </div>
          </div>
        </div>`;
      } else {
        const gradeDemo = gradeDemographics.value?.[idx];
        const count = gradeDemo?.student_count || 0;
        return `<div style="padding:10px 14px;font-family:Inter,system-ui,sans-serif;min-width:150px;">
          <p style="font-size:11px;font-weight:700;color:#374151;margin:0 0 6px;">${cat}</p>
          <p style="font-size:20px;font-weight:900;color:#6366f1;margin:0 0 8px;">${numVal}</p>
          <div style="border-top:1px solid #f3f4f6;padding-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:10px;color:#6b7280;">
              <span>Siswa Terlacak</span><span style="font-weight:700;color:#374151;">${count}</span>
            </div>
          </div>
        </div>`;
      }
    },
  },
}));

// ==================== DATA FETCHING ====================
const fetchData = async () => {
  if (!selectedFilter.value) return;

  isLoading.value = true;
  error.value = null;

  try {
    let res;
    if (activeTab.value === 'curriculum') {
      res = await principalService.getCurriculumTrend(selectedFilter.value);
    } else {
      // Look up the combined year entry to get both odd and even IDs
      const combinedYear = cohortYears.value.find(y => y.id === Number(selectedFilter.value));
      if (combinedYear && combinedYear.odd_year_id && combinedYear.even_year_id) {
        res = await principalService.getCohortTrend([combinedYear.odd_year_id, combinedYear.even_year_id]);
      } else {
        res = await principalService.getCohortTrend([selectedFilter.value]);
      }
    }

    const payload = res.data?.data || res.data || {};
    chartCategories.value = payload.categories || [];
    chartSeriesData.value = payload.series?.[0]?.data || [];

    if (activeTab.value === 'curriculum') {
      demographics.value = payload.demographics || [];
      gradeDemographics.value = [];
      curriculumHasCombinedYears.value = payload.has_combined_years !== false;
    } else {
      demographics.value = payload.cohort_demographics || null;
      gradeDemographics.value = payload.grade_demographics || [];
    }
  } catch (e) {
    error.value = 'Gagal memuat data tren akademik.';
    chartCategories.value = [];
    chartSeriesData.value = [];
    demographics.value = null;
    gradeDemographics.value = [];
  } finally {
    isLoading.value = false;
  }
};

// Load cohort years for dropdown
const loadCohortYears = async () => {
  try {
    const res = await principalService.getCohortYears();
    cohortYears.value = res.data?.data || res.data || [];
  } catch {
    cohortYears.value = [];
  }
};

// ==================== WATCHERS ====================
// Reset filter when switching tabs
watch(activeTab, (newTab) => {
  // Clear chart data immediately on tab switch
  chartCategories.value = [];
  chartSeriesData.value = [];
  demographics.value = null;
  gradeDemographics.value = [];
  error.value = null;
  curriculumHasCombinedYears.value = true; // Reset

  if (newTab === 'curriculum') {
    selectedFilter.value = 7;
  } else if (cohortYears.value.length > 0) {
    selectedFilter.value = cohortYears.value[0].id;
  } else {
    // No combined years — clear selection, show empty state
    selectedFilter.value = '';
  }
});

// Fetch data when filter changes
watch(selectedFilter, (val) => {
  if (val) fetchData();
});

// ==================== LIFECYCLE ====================
onMounted(async () => {
  await loadCohortYears();
  selectedFilter.value = 7; // Default to grade 7
});
</script>
