<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Penilaian Ekstrakurikuler
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          Input dan kelola nilai eskul untuk siswa yang terdaftar.
        </p>
      </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ekstrakurikuler</label>
          <select
            v-model="selectedEskulId"
            @change="fetchStudents"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors text-sm"
          >
            <option :value="null">Semua Eskul</option>
            <option v-for="eskul in assignedEskuls" :key="eskul.id" :value="eskul.id">
              {{ eskul.name }}
            </option>
          </select>
        </div>
        <div class="flex-1">
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Filter Kelas</label>
          <select
            v-model="selectedClassId"
            @change="fetchStudents"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors text-sm"
          >
            <option :value="null">Semua Kelas</option>
            <option v-for="cls in classOptions" :key="cls.id" :value="cls.id">
              {{ cls.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-brand-red border-t-transparent"></div>
      <p class="mt-4 text-gray-500">Memuat data siswa...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="groupedStudents.length === 0" class="text-center py-12 bg-white rounded-xl border border-gray-100">
      <Icon icon="mdi:account-group-outline" class="w-16 h-16 mx-auto text-gray-300 mb-3" />
      <p class="text-gray-500 font-medium">Tidak ada siswa yang terdaftar di eskul ini.</p>
    </div>

    <!-- Students by Class -->
    <div v-else class="space-y-6">
      <div
        v-for="group in groupedStudents"
        :key="group.class_name"
        class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
      >
        <!-- Class Header -->
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-700">
              <Icon icon="mdi:domain" class="w-5 h-5 inline mr-2 text-brand-red" />
              {{ group.class_name }}
            </h3>
            <span class="text-sm text-gray-500">{{ group.students.length }} siswa</span>
          </div>
        </div>

        <!-- Students Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left px-6 py-3 font-semibold text-gray-600">No</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600">Nama Siswa</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600">Eskul</th>
                <th class="text-center px-6 py-3 font-semibold text-gray-600 w-32">Nilai</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600">Deskripsi/Catatan</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(student, index) in group.students"
                :key="student.student_eskul_id"
                class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
              >
                <td class="px-6 py-3 text-gray-500">{{ index + 1 }}</td>
                <td class="px-6 py-3 font-medium text-gray-800">{{ student.student_name }}</td>
                <td class="px-6 py-3 text-gray-600">{{ student.eskul_name }}</td>
                <td class="px-6 py-3">
                  <input
                    v-model.number="student.score"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="0-100"
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-center focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors"
                  />
                </td>
                <td class="px-6 py-3">
                  <input
                    v-model="student.description"
                    type="text"
                    placeholder="Catatan (opsional)"
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors text-sm"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveGrades"
          :disabled="isSaving || !hasChanges"
          class="px-6 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-50 transition-colors shadow-sm flex items-center gap-2"
        >
          <Icon v-if="isSaving" icon="mdi:loading" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="mdi:content-save" class="w-5 h-5" />
          {{ isSaving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { eskulService } from '../../services/modules/teacher/eskulService';

const toastStore = useToastStore();

const assignedEskuls = ref([]);
const groupedStudents = ref([]);
const classOptions = ref([]);
const selectedEskulId = ref(null);
const selectedClassId = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const originalData = ref([]);

const hasChanges = computed(() => {
  return JSON.stringify(groupedStudents.value) !== JSON.stringify(originalData.value);
});

const fetchAssignedEskuls = async () => {
  try {
    const response = await eskulService.getAssignedEskuls();
    assignedEskuls.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data eskul.');
  }
};

const fetchStudents = async () => {
  isLoading.value = true;
  try {
    const params = {};
    if (selectedEskulId.value) params.eskul_id = selectedEskulId.value;
    if (selectedClassId.value) params.class_id = selectedClassId.value;

    const response = await eskulService.getStudents(params);
    groupedStudents.value = response.data.data;

    // Extract unique classes for filter
    const classes = [];
    const classNames = new Set();
    for (const group of response.data.data) {
      if (!classNames.has(group.class_name)) {
        classNames.add(group.class_name);
        classes.push({ id: classes.length + 1, name: group.class_name });
      }
    }
    classOptions.value = classes;

    // Store original for change detection
    originalData.value = JSON.parse(JSON.stringify(groupedStudents.value));
  } catch {
    toastStore.error('Gagal memuat data siswa.');
  } finally {
    isLoading.value = false;
  }
};

const saveGrades = async () => {
  isSaving.value = true;
  try {
    const grades = [];
    for (const group of groupedStudents.value) {
      for (const student of group.students) {
        grades.push({
          student_id: student.student_id,
          eskul_id: student.eskul_id,
          score: student.score || null,
          description: student.description || null,
        });
      }
    }

    const res = await eskulService.gradeStudents(grades);
    toastStore.success(res.data.message || 'Nilai eskul berhasil disimpan.');
    originalData.value = JSON.parse(JSON.stringify(groupedStudents.value));
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan nilai eskul.');
  } finally {
    isSaving.value = false;
  }
};

onMounted(() => {
  fetchAssignedEskuls();
  fetchStudents();
});
</script>
