<template>
  <div class="space-y-6">
    <div class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md">
      <h1 class="text-2xl md:text-3xl font-bold font-serif">Pengaturan Bobot Nilai</h1>
      <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
        Atur persentase bobot penilaian untuk tahun ajaran aktif. Total keseluruhan bobot harus tepat 100%.
      </p>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
      <p class="text-gray-500 font-medium text-sm">Memuat pengaturan bobot...</p>
    </div>

    <template v-else>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
          <div class="w-10 h-10 bg-brand-red/10 text-brand-red rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-800">Tahun Ajaran: {{ academicYearName }}</h3>
            <p class="text-xs text-gray-500">Pengaturan ini berlaku untuk seluruh kelas pada tahun ajaran aktif.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Bobot Tugas Harian</label>
            <div class="relative">
              <input
                v-model.number="form.task_weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Rata-rata dari seluruh tugas harian yang diberikan.</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Bobot Ujian Harian</label>
            <div class="relative">
              <input
                v-model.number="form.daily_exam_weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Ujian harian beserta remedialnya (jika ada).</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Bobot UTS</label>
            <div class="relative">
              <input
                v-model.number="form.uts_weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Nilai Ujian Tengah Semester.</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Bobot UAS</label>
            <div class="relative">
              <input
                v-model.number="form.uas_weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Nilai Ujian Akhir Semester.</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Bobot Kehadiran</label>
            <div class="relative">
              <input
                v-model.number="form.attendance_weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Persentase kehadiran siswa. Set 0 untuk menonaktifkan.</p>
          </div>
        </div>

        <div class="mb-8 mt-2 pt-6 border-t border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-800">Kriteria Kenaikan Kelas</h3>
              <p class="text-xs text-gray-500">Minimum rata-rata nilai seluruh mata pelajaran agar siswa dinyatakan naik kelas.</p>
            </div>
          </div>
          <div class="max-w-xs">
            <label class="block text-sm font-bold text-gray-700 mb-2">Minimum Nilai Kelulusan</label>
            <div class="relative">
              <input
                v-model.number="form.min_score_to_pass"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl font-black text-center focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1.5">Siswa harus memiliki rata-rata nilai di atas angka ini di <strong>semua</strong> mata pelajaran untuk naik kelas.</p>
          </div>
        </div>

        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-bold text-gray-700">Total Bobot</span>
            <span class="text-2xl font-black" :class="totalWeight === 100 ? 'text-green-600' : 'text-brand-red'">
              {{ totalWeight }}%
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-300"
              :class="totalWeight === 100 ? 'bg-green-500' : totalWeight > 100 ? 'bg-brand-red' : 'bg-amber-500'"
              :style="{ width: Math.min(totalWeight, 100) + '%' }"
            ></div>
          </div>
          <div class="mt-2 flex items-center gap-2">
            <template v-if="totalWeight === 100">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              <span class="text-xs font-bold text-green-600">Valid — Total bobot sudah tepat 100%</span>
            </template>
            <template v-else>
              <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
              <span class="text-xs font-bold text-brand-red">
                Total harus 100%, saat ini {{ totalWeight }}%
                <span v-if="totalWeight < 100"> (kurang {{ 100 - totalWeight }}%)</span>
                <span v-else> (lebih {{ totalWeight - 100 }}%)</span>
              </span>
            </template>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            @click="saveSettings"
            :disabled="totalWeight !== 100 || isSaving"
            class="px-8 py-3 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-sm font-bold shadow-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="isSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ isSaving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { principalService } from '../../services/modules/principal/principalService';
import { adminGradingService } from '../../services/modules/admin/gradingService';
import { useGlobalDropdownsStore } from '../../stores/globalDropdowns';
import { useToastStore } from '../../stores/toast';

const route = useRoute();
const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();

const isLoading = ref(true);
const isSaving = ref(false);
const academicYearName = ref('');

const isAdmin = computed(() => route.path.startsWith('/admin'));

const form = ref({
  task_weight: 30,
  daily_exam_weight: 10,
  uts_weight: 25,
  uas_weight: 25,
  attendance_weight: 10,
  min_score_to_pass: 60,
});

const totalWeight = computed(() => {
  const t = Number(form.value.task_weight) || 0;
  const de = Number(form.value.daily_exam_weight) || 0;
  const u = Number(form.value.uts_weight) || 0;
  const a = Number(form.value.uas_weight) || 0;
  const att = Number(form.value.attendance_weight) || 0;
  return t + de + u + a + att;
});

const fetchSettings = async () => {
  isLoading.value = true;
  try {
    let data;
    if (isAdmin.value) {
      await dropdowns.ensureAcademicYears();
      const activeYear = dropdowns.academicYearsRaw.find(y => y.is_active);
      if (!activeYear) {
        toastStore.error('Tidak ada tahun ajaran aktif.');
        return;
      }
      const res = await adminGradingService.getGradingSettings(activeYear.id);
      data = res.data?.data || res.data;
      academicYearName.value = `${activeYear.name} ${activeYear.semester === 'odd' ? 'Ganjil' : 'Genap'}`;
    } else {
      const res = await principalService.getGradingSettings();
      data = res.data?.data || res.data;
      academicYearName.value = data.academic_year_name || 'Tahun Ajaran Aktif';
    }
    form.value = {
      task_weight: data.task_weight ?? 30,
      daily_exam_weight: data.daily_exam_weight ?? 10,
      uts_weight: data.uts_weight ?? 25,
      uas_weight: data.uas_weight ?? 25,
      attendance_weight: data.attendance_weight ?? 10,
      min_score_to_pass: data.min_score_to_pass ?? 60,
    };
  } catch (error) {
    toastStore.error('Gagal memuat pengaturan bobot nilai.');
  } finally {
    isLoading.value = false;
  }
};

const saveSettings = async () => {
  if (totalWeight.value !== 100) return;

  isSaving.value = true;
  try {
    const payload = {
      task_weight: Number(form.value.task_weight),
      daily_exam_weight: Number(form.value.daily_exam_weight),
      uts_weight: Number(form.value.uts_weight),
      uas_weight: Number(form.value.uas_weight),
      attendance_weight: Number(form.value.attendance_weight),
      min_score_to_pass: Number(form.value.min_score_to_pass),
    };
    if (isAdmin.value) {
      await dropdowns.ensureAcademicYears();
      const activeYear = dropdowns.academicYearsRaw.find(y => y.is_active);
      payload.academic_year_id = activeYear.id;
      await adminGradingService.updateGradingSettings(payload);
    } else {
      await principalService.updateGradingSettings(payload);
    }
    toastStore.success('Pengaturan bobot nilai berhasil disimpan.');
  } catch (error) {
    const msg = error.response?.data?.message || 'Gagal menyimpan pengaturan.';
    toastStore.error(msg);
  } finally {
    isSaving.value = false;
  }
};

onMounted(fetchSettings);
</script>
