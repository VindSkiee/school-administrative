<template>
  <div class="space-y-6 relative">
    
    <div class="flex items-center gap-4">
      <button @click="$router.back()" class="p-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-brand-red transition-colors shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      </button>
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 font-serif">Detail Penilaian</h1>
        <p class="text-sm text-gray-500">Periksa tugas dan berikan nilai kepada siswa.</p>
      </div>
    </div>

    <div v-if="isLoading" class="py-16 flex justify-center">
      <div class="animate-spin h-10 w-10 border-4 border-gray-200 border-t-brand-red rounded-full"></div>
    </div>

    <template v-else-if="assignment">
      <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-6">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span :class="isClosed ? 'bg-gray-100 text-gray-600' : 'bg-green-50 text-green-600'" class="px-2.5 py-1 text-xs font-bold uppercase rounded-lg border whitespace-nowrap">
                {{ isClosed ? 'Waktu Habis / Ditutup' : 'Sedang Berjalan' }}
              </span>
              <span class="text-sm font-semibold text-gray-500">
                Tenggat: <span :class="isClosed ? 'text-red-500' : 'text-gray-800'">{{ formatDate(assignment.due_date) }}</span>
              </span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">{{ assignment.title }}</h2>
          </div>
          <div class="flex gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100 w-full md:w-auto">
            <div class="text-center px-4 border-r border-gray-200">
              <p class="text-xs text-gray-500 font-semibold uppercase">Terkumpul</p>
              <p class="text-xl font-bold text-gray-800">{{ assignment.submissions?.length || 0 }}</p>
            </div>
            <div class="text-center px-4">
              <p class="text-xs text-gray-500 font-semibold uppercase">Dinilai</p>
              <p class="text-xl font-bold text-green-600">{{ gradedCount }}</p>
            </div>
          </div>
        </div>

        <div class="prose prose-sm max-w-none text-gray-600 mb-6 bg-blue-50/50 p-4 rounded-xl border border-blue-100/50">
          <p class="whitespace-pre-wrap">{{ assignment.description }}</p>
        </div>

        <div v-if="assignment.attachments && assignment.attachments.length > 0">
          <h4 class="text-sm font-bold text-gray-700 mb-2">Lampiran Soal:</h4>
          <div class="flex flex-wrap gap-3">
            <a v-for="(path, idx) in assignment.attachments" :key="idx" :href="getStorageUrl(path)" target="_blank" 
               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:border-brand-red hover:bg-red-50 rounded-xl text-sm font-semibold text-gray-700 hover:text-brand-red transition-all shadow-sm group">
              <svg class="w-5 h-5 text-gray-400 group-hover:text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Lihat Lampiran {{ idx + 1 }}
            </a>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden mt-6">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
          <h3 class="text-lg font-bold text-gray-800">Daftar Pengumpulan Tugas</h3>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-sm border-y border-gray-100">
                <th class="p-4 font-semibold w-12 text-center">No</th>
                <th class="p-4 font-semibold">Nama Siswa</th>
                <th class="p-4 font-semibold">Waktu Pengumpulan</th>
                <th class="p-4 font-semibold text-center">Status</th>
                <th class="p-4 font-semibold text-center w-32">Nilai</th>
                <th class="p-4 font-semibold text-right w-40">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!assignment.submissions || assignment.submissions.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-500">Belum ada siswa yang mengumpulkan tugas ini.</td>
              </tr>
              <tr v-for="(sub, index) in assignment.submissions" :key="sub.id" class="hover:bg-gray-50/50 transition-colors">
                <td class="p-4 text-center text-gray-500 font-medium">{{ index + 1 }}</td>
                <td class="p-4 font-bold text-gray-800">{{ sub.student?.user?.name || 'Siswa NN' }}</td>
                <td class="p-4 text-sm text-gray-600">
                  {{ formatDate(sub.submitted_at) }}
                  <span v-if="isLate(sub.submitted_at)" class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 text-xs font-bold rounded">Terlambat</span>
                </td>
                <td class="p-4 text-center">
                  <span v-if="sub.grade" class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Sudah Dinilai</span>
                  <span v-else class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-full">Belum Dinilai</span>
                </td>
                <td class="p-4 text-center font-black text-lg" :class="sub.grade ? 'text-brand-red' : 'text-gray-300'">
                  {{ sub.grade ? sub.grade.score : '-' }}
                </td>
                <td class="p-4 text-right">
                  <button @click="openGradingDrawer(sub)" class="px-4 py-2 bg-brand-red hover:bg-brand-orange text-white text-sm font-bold rounded-xl shadow-sm transition-colors">
                    {{ sub.grade ? 'Ubah Nilai' : 'Periksa' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-if="activeSubmission" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-40 transition-opacity" @click="closeDrawer"></div>
    
    <div 
      class="fixed inset-y-0 right-0 w-full md:w-[450px] bg-white shadow-2xl z-50 transform transition-transform duration-300 ease-in-out flex flex-col"
      :class="activeSubmission ? 'translate-x-0' : 'translate-x-full'"
    >
      <div v-if="activeSubmission" class="flex flex-col h-full">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
          <div>
            <h3 class="text-xl font-bold text-gray-800">Panel Penilaian</h3>
            <p class="text-sm font-semibold text-brand-red">{{ activeSubmission.student?.user?.name }}</p>
          </div>
          <button @click="closeDrawer" class="p-2 bg-gray-200 hover:bg-red-100 hover:text-red-600 rounded-full transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <div class="p-6 flex-1 overflow-y-auto space-y-6">
          
          <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
            <h4 class="text-sm font-bold text-blue-900 mb-2">Jawaban / File yang Dikumpulkan:</h4>
            <a :href="getStorageUrl(activeSubmission.file_path)" target="_blank" 
               class="flex items-center justify-center gap-2 w-full py-3 bg-white border-2 border-blue-200 hover:border-blue-500 rounded-xl text-blue-600 font-bold shadow-sm transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              Buka / Unduh Jawaban
            </a>
            <p class="text-xs text-blue-600 mt-2 text-center">Dikumpulkan: {{ formatDate(activeSubmission.submitted_at) }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Beri Nilai (0 - 100) <span class="text-red-500">*</span></label>
            <input 
              v-model="gradeForm.score" 
              type="number" 
              step="0.1" min="0" max="100" 
              class="w-full text-3xl font-black text-center py-4 border-2 border-gray-200 rounded-2xl focus:border-brand-red focus:ring-0 outline-none text-brand-red bg-gray-50"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan / Umpan Balik (Opsional)</label>
            <textarea 
              v-model="gradeForm.feedback" 
              rows="4" 
              placeholder="Pekerjaanmu sangat bagus, tapi coba perbaiki di bagian..."
              class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm"
            ></textarea>
          </div>

        </div>

        <div class="p-6 border-t border-gray-100 bg-white">
          <button 
            @click="submitGrade" 
            :disabled="isGrading || gradeForm.score === ''"
            class="w-full py-3.5 bg-brand-red hover:bg-brand-orange text-white font-bold rounded-xl shadow-md transition-colors disabled:opacity-50 text-lg flex justify-center items-center gap-2"
          >
            {{ isGrading ? 'Menyimpan...' : '💾 Simpan Nilai' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { assignmentService } from '../../services/modules/teacher/assignmentService';
import { useToastStore } from '../../stores/toast';

const route = useRoute();
const toastStore = useToastStore();

const assignment = ref(null);
const isLoading = ref(true);

// State Drawer Penilaian
const activeSubmission = ref(null);
const gradeForm = ref({ score: '', feedback: '' });
const isGrading = ref(false);

const isClosed = computed(() => {
  if (!assignment.value?.due_date) return false;
  return new Date() > new Date(assignment.value.due_date);
});

const gradedCount = computed(() => {
  if (!assignment.value?.submissions) return 0;
  return assignment.value.submissions.filter(s => s.grade !== null).length;
});

const isLate = (submitDate) => {
  if (!assignment.value?.due_date || !submitDate) return false;
  return new Date(submitDate) > new Date(assignment.value.due_date);
};

const getStorageUrl = (path) => {
  if (!path) return '#';
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
  return `${baseUrl}/storage/${path}`;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const options = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

// ----------------- FETCH DATA -----------------
const fetchDetail = async () => {
  isLoading.value = true;
  try {
    const res = await assignmentService.getAssignmentDetail(route.params.id);
    assignment.value = res.data;
  } catch (error) {
    toastStore.error("Gagal memuat detail tugas.");
  } finally {
    isLoading.value = false;
  }
};

// ----------------- DRAWER LOGIC -----------------
const openGradingDrawer = (submission) => {
  activeSubmission.value = submission;
  // Pre-fill form jika sudah pernah dinilai
  gradeForm.value = {
    score: submission.grade ? submission.grade.score : '',
    feedback: submission.grade ? submission.grade.feedback : ''
  };
};

const closeDrawer = () => {
  activeSubmission.value = null;
  gradeForm.value = { score: '', feedback: '' };
};

const submitGrade = async () => {
  if (gradeForm.value.score === '' || gradeForm.value.score < 0 || gradeForm.value.score > 100) {
    toastStore.error("Masukkan nilai antara 0 hingga 100.");
    return;
  }

  isGrading.value = true;
  try {
    const submissionId = activeSubmission.value.id;
    await assignmentService.submitGrade(submissionId, gradeForm.value);
    
    toastStore.success("Nilai berhasil disimpan!");
    
    // Perbarui state lokal secara instan tanpa reload halaman
    const subIndex = assignment.value.submissions.findIndex(s => s.id === submissionId);
    if (subIndex !== -1) {
      assignment.value.submissions[subIndex].grade = {
        score: gradeForm.value.score,
        feedback: gradeForm.value.feedback
      };
    }
    
    closeDrawer();
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal menyimpan nilai.");
  } finally {
    isGrading.value = false;
  }
};

onMounted(() => {
  fetchDetail();
});
</script>