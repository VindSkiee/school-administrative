<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <button @click="$router.back()" class="mt-1 text-gray-400 hover:text-brand-red transition-colors flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      </button>
      <div class="flex-1">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 font-serif">Detail Mata Pelajaran</h1>
        <p class="text-gray-500 text-sm mt-1" v-if="subject">
          {{ subject.code }} — {{ subject.name }}
        </p>
      </div>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4 items-end">
      <div class="flex-1 w-full md:w-auto">
        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pilih Tahun Ajaran</label>
        <BaseSelect
          v-model="selectedAcademicYearId"
          :options="academicYearOptions"
          placeholder="Pilih Tahun Ajaran"
          @update:modelValue="fetchDetail"
        />
      </div>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
      <p class="text-gray-500 font-medium text-sm">Memuat data...</p>
    </div>

    <template v-else-if="selectedAcademicYearId">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
          <h3 class="text-base font-bold text-gray-800">Guru Pengajar</h3>
          <p class="text-xs text-gray-500 mt-0.5">Daftar guru yang mengajar mata pelajaran ini pada tahun ajaran terpilih.</p>
        </div>
        <div v-if="teachers.length === 0" class="p-8 text-center text-gray-500 text-sm">
          Belum ada guru yang ditugaskan untuk mata pelajaran ini pada tahun ajaran terpilih.
        </div>
        <div v-else class="divide-y divide-gray-100">
          <div v-for="teacher in teachers" :key="teacher.teacher_id" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm shrink-0">
              {{ teacher.teacher_name.charAt(0) }}
            </div>
            <div>
              <p class="font-semibold text-gray-800 text-sm">{{ teacher.teacher_name }}</p>
              <p class="text-xs text-gray-500">Kelas {{ teacher.class_name }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
          <h3 class="text-base font-bold text-gray-800">Pengaturan Capaian Kompetensi</h3>
          <p class="text-xs text-gray-500 mt-0.5">
            Atur rentang nilai dan deskripsi capaian kompetensi yang akan ditampilkan di rapor PDF.
          </p>
        </div>

        <form @submit.prevent="saveCompetency" class="p-5 space-y-5">
          <div v-for="(level, idx) in competencyLevels" :key="level.key" class="border border-gray-200 rounded-xl p-4"
               :class="level.borderColor">
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="w-full sm:w-32 shrink-0">
                <label class="block text-xs font-bold mb-1.5" :class="level.labelColor">{{ level.label }}</label>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-400">≥</span>
                  <input
                    v-model.number="form[level.key + '_min']"
                    type="number"
                    min="0"
                    max="100"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-center font-bold focus:ring-1 focus:ring-brand-red outline-none text-sm"
                  />
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Nilai minimum</p>
              </div>
              <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Deskripsi Capaian</label>
                <textarea
                  v-model="form[level.key + '_text']"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-brand-red outline-none resize-none"
                  :placeholder="level.placeholder"
                ></textarea>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="isSaving"
              class="px-6 py-2.5 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-sm font-bold shadow-md transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="isSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              {{ isSaving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onActivated, reactive } from 'vue';
import { useRoute } from 'vue-router';
import { subjectService } from '../../services/modules/admin/subjectService';
import { useToastStore } from '../../stores/toast';
import { useGlobalDropdownsStore } from '../../stores/globalDropdowns';
import BaseSelect from '../../components/BaseSelect.vue';

const route = useRoute();
const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();
const subjectId = route.params.id;

const isLoading = ref(true);
const isSaving = ref(false);
const subject = ref(null);
const teachers = ref([]);
const selectedAcademicYearId = ref('');

const competencyLevels = [
  {
    key: 'sangat_baik',
    label: 'Sangat Baik',
    labelColor: 'text-green-700',
    borderColor: 'border-green-200 bg-green-50/30',
    placeholder: 'Mencapai kompetensi dengan sangat baik dalam memahami materi pembelajaran...',
  },
  {
    key: 'baik',
    label: 'Baik',
    labelColor: 'text-blue-700',
    borderColor: 'border-blue-200 bg-blue-50/30',
    placeholder: 'Mencapai kompetensi dengan baik dalam memahami materi pembelajaran...',
  },
  {
    key: 'kurang',
    label: 'Kurang',
    labelColor: 'text-amber-700',
    borderColor: 'border-amber-200 bg-amber-50/30',
    placeholder: 'Perlu peningkatan dalam hal memahami materi pembelajaran...',
  },
  {
    key: 'sangat_kurang',
    label: 'Sangat Kurang',
    labelColor: 'text-red-700',
    borderColor: 'border-red-200 bg-red-50/30',
    placeholder: 'Perlu bimbingan intensif untuk mencapai ketuntasan belajar...',
  },
];

const form = reactive({
  sangat_baik_min: 85,
  sangat_baik_text: 'Mencapai kompetensi dengan sangat baik dalam memahami materi pembelajaran.',
  baik_min: 75,
  baik_text: 'Mencapai kompetensi dengan baik dalam memahami materi pembelajaran.',
  kurang_min: 60,
  kurang_text: 'Perlu peningkatan dalam hal memahami materi pembelajaran.',
  sangat_kurang_min: 0,
  sangat_kurang_text: 'Perlu bimbingan intensif untuk mencapai ketuntasan belajar.',
});

const academicYearOptions = computed(() => dropdowns.academicYearOptions);

// PERF FIX: use store for academic years instead of separate API call
const fetchAcademicYears = async () => {
  try {
    await dropdowns.ensureAcademicYears();
    const data = dropdowns.academicYearsRaw;
    const active = data.find(y => y.is_active);
    if (active && !selectedAcademicYearId.value) selectedAcademicYearId.value = active.id;
  } catch {
    toastStore.error('Gagal memuat tahun ajaran.');
  }
};

const fetchDetail = async () => {
  if (!selectedAcademicYearId.value) return;
  isLoading.value = true;
  try {
    const res = await subjectService.getDetail(subjectId, selectedAcademicYearId.value);
    const data = res.data?.data || res.data;
    subject.value = data.subject;
    teachers.value = data.teachers || [];

    if (data.competency) {
      form.sangat_baik_min = data.competency.sangat_baik_min;
      form.sangat_baik_text = data.competency.sangat_baik_text;
      form.baik_min = data.competency.baik_min;
      form.baik_text = data.competency.baik_text;
      form.kurang_min = data.competency.kurang_min;
      form.kurang_text = data.competency.kurang_text;
      form.sangat_kurang_min = data.competency.sangat_kurang_min;
      form.sangat_kurang_text = data.competency.sangat_kurang_text;
    }
  } catch {
    toastStore.error('Gagal memuat detail mata pelajaran.');
  } finally {
    isLoading.value = false;
  }
};

const saveCompetency = async () => {
  isSaving.value = true;
  try {
    await subjectService.saveCompetency(subjectId, {
      academic_year_id: selectedAcademicYearId.value,
      sangat_baik_min: form.sangat_baik_min,
      sangat_baik_text: form.sangat_baik_text,
      baik_min: form.baik_min,
      baik_text: form.baik_text,
      kurang_min: form.kurang_min,
      kurang_text: form.kurang_text,
      sangat_kurang_min: form.sangat_kurang_min,
      sangat_kurang_text: form.sangat_kurang_text,
    });
    toastStore.success('Pengaturan capaian kompetensi berhasil disimpan.');
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan pengaturan.');
  } finally {
    isSaving.value = false;
  }
};

onMounted(async () => {
  await fetchAcademicYears();
  if (selectedAcademicYearId.value) {
    await fetchDetail();
  } else {
    isLoading.value = false;
  }
});

// Refresh years + detail when re-activated from keep-alive cache
onActivated(async () => {
  await fetchAcademicYears();
  if (selectedAcademicYearId.value) {
    await fetchDetail();
  }
});
</script>
