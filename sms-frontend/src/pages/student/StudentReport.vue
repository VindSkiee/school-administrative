<template>
  <div class="space-y-6 waves-container pb-12">
    
    <div class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
      <div class="relative z-10">
        <div class="flex items-center gap-2">
          <h1 class="text-2xl md:text-3xl font-bold font-serif">Nilai & Rapor Semester</h1>
          <BasePopoverInfo>
            <strong class="text-gray-800 block mb-1">Informasi Nilai Akhir:</strong>
            Nilai Akhir diperoleh dari kalkulasi rata-rata seluruh nilai tugas mata pelajaran yang telah dinilai oleh guru pengampu selama satu semester berjalan.
          </BasePopoverInfo>
        </div>
        <p class="text-red-100 text-sm mt-1 max-w-xl font-medium">
          Pantau grafik kemajuan belajarmu secara mandiri dan unduh dokumen rapor digital resmimu di sini.
        </p>
      </div>

      <div class="relative z-10 w-full md:w-auto shrink-0 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 flex flex-col sm:flex-row md:flex-col items-start sm:items-center md:items-start justify-between gap-4">
        <div>
          <p class="text-[10px] text-red-100 font-bold uppercase tracking-wider mb-1">Status Dokumen Rapor</p>
          <span 
            class="px-2.5 py-1 rounded-md text-xs font-bold inline-flex items-center gap-1"
            :class="isPublished ? 'bg-green-500 text-white' : 'bg-amber-500 text-white'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isPublished ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'"></path>
            </svg>
            {{ isPublished ? 'Sudah Diterbitkan' : 'Menunggu Penerbitan' }}
          </span>
        </div>

        <button 
          @click="downloadRapor" 
          :disabled="!isPublished || isDownloading"
          class="w-full sm:w-auto md:w-full px-5 py-2.5 rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center gap-2"
          :class="isPublished ? 'bg-white text-brand-red hover:bg-red-50' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
        >
          <svg v-if="isDownloading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          {{ isDownloading ? 'Mengunduh...' : 'Unduh Rapor PDF' }}
        </button>
      </div>
    </div>

    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="p-6 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Transkrip Nilas Akademis</h3>
        <p class="text-sm text-gray-500 mt-1">Klik ikon panah pada mata pelajaran untuk melihat rincian nilai tugas harian.</p>
      </div>

      <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
        <p class="text-gray-500 font-medium text-sm">Menghitung akumulasi nilai harian...</p>
      </div>

      <div v-else-if="aggregates.length === 0" class="p-12 text-center text-gray-500">
        Tidak ada data mata pelajaran aktif yang ditemukan.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
              <th class="py-4 px-6 w-12 text-center"></th>
              <th class="py-4 px-6">Kode</th>
              <th class="py-4 px-6">Mata Pelajaran</th>
              <th class="py-4 px-6 text-center">Tugas Dinilai</th>
              <th class="py-4 px-6 text-center w-36">Nilai Akhir</th>
              <th class="py-4 px-6 text-center w-32">Predikat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-for="row in aggregates" :key="row.subject_id">
              <tr class="hover:bg-gray-50/60 transition-colors text-sm font-medium text-gray-700">
                <td class="py-4 px-6 text-center">
                  <button 
                    @click="toggleRow(row.subject_id)"
                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-all transform focus:outline-none"
                    :class="{ 'rotate-90 text-brand-red bg-red-50': expandedRows.includes(row.subject_id) }"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </button>
                </td>
                <td class="py-4 px-6 font-mono text-xs text-gray-400 font-bold">{{ row.subject_code }}</td>
                <td class="py-4 px-6 font-bold text-gray-800">{{ row.subject_name }}</td>
                <td class="py-4 px-6 text-center text-gray-500 font-semibold">{{ row.total_graded_assignments }} Sesi</td>
                <td class="py-4 px-6 text-center">
                  <span 
                    v-if="row.final_grade !== null" 
                    class="text-base font-black px-3 py-1 rounded-xl"
                    :class="row.final_grade >= 75 ? 'text-green-600 bg-green-50' : 'text-brand-red bg-red-50'"
                  >
                    {{ row.final_grade }}
                  </span>
                  <span v-else class="text-xs text-gray-400 italic">Belum ada nilai</span>
                </td>
                <td class="py-4 px-6 text-center">
                  <span v-if="row.final_grade !== null" class="font-bold text-gray-700">
                    {{ getPredicate(row.final_grade) }}
                  </span>
                  <span v-else class="text-gray-300">-</span>
                </td>
              </tr>

              <tr v-if="expandedRows.includes(row.subject_id)" class="bg-gray-50/50">
                <td colspan="6" class="py-4 px-8 border-b border-gray-100">
                  <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-inner max-w-3xl space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                      <span>📋</span> Komponen Nilai Tugas Harian:
                    </p>
                    
                    <div v-if="row.details.length === 0" class="text-xs text-gray-400 italic py-2 pl-2">
                      Belum ada penugasan terdaftar untuk mata pelajaran ini.
                    </div>

                    <div 
                      v-else
                      v-for="(task, idx) in row.details" :key="idx"
                      class="flex items-center justify-between py-2 px-3 hover:bg-gray-50 rounded-lg text-xs transition-colors border-b border-gray-50 last:border-0"
                    >
                      <span class="font-semibold text-gray-700">{{ task.title }}</span>
                      <span 
                        class="font-black px-2 py-0.5 rounded-md"
                        :class="typeof task.score === 'number' ? (task.score >= 75 ? 'text-green-600 bg-green-50' : 'text-brand-red bg-red-50') : 'text-gray-400 bg-gray-100'"
                      >
                        {{ task.score }}
                      </span>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { studentReportService } from '../../services/modules/student/reportService';
import { useToastStore } from '../../stores/toast';
import BasePopoverInfo from '../../components/BasePopoverInfo.vue';

const toastStore = useToastStore();

const aggregates = ref([]);
const expandedRows = ref([]); // Menyimpan array ID mapel yang sedang di-expand dropdown-nya
const isLoading = ref(true);
const isPublished = ref(false);
const isDownloading = ref(false);

const getPredicate = (score) => {
  if (score >= 90) return 'A (Sangat Baik)';
  if (score >= 80) return 'B (Baik)';
  if (score >= 75) return 'C (Cukup)';
  return 'D (Kurang)';
};

const toggleRow = (subjectId) => {
  if (expandedRows.value.includes(subjectId)) {
    expandedRows.value = expandedRows.value.filter(id => id !== subjectId);
  } else {
    expandedRows.value.push(subjectId);
  }
};

const loadPageData = async () => {
  isLoading.value = true;
  try {
    // 1. Ambil data agregasi nilai harian & detail tugas
    const resAggregate = await studentReportService.getGradesAggregate();
    aggregates.value = resAggregate.data.data || resAggregate.data;

    // 2. Cek gerbang apakah rapor resmi sudah diterbitkan admin?
    try {
      await studentReportService.getSemesterReportStatus();
      isPublished.value = true; // Jika sukses 200, berarti sudah dipublikasikan
    } catch (err) {
      if (err.response?.status === 403) {
        isPublished.value = false; // Dikunci oleh kebijakan sekolah
      }
    }
  } catch (error) {
    toastStore.error("Gagal menyusun transkrip nilai semester.");
  } finally {
    isLoading.value = false;
  }
};

const downloadRapor = async () => {
  if (!isPublished.value || isDownloading.value) return;
  isDownloading.value = true;
  
  try {
    const response = await studentReportService.downloadReportPdf();
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Rapor_Semester_Siswa.pdf`);
    document.body.appendChild(link);
    link.click();
    
    // Clean up
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    toastStore.success("Rapor resmi berhasil diunduh.");
  } catch (error) {
    // Handle blob error: extract message from the error response
    if (error.response && error.response.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const json = JSON.parse(text);
        toastStore.error(json.error || json.message || 'Gagal mengunduh rapor.');
      } catch {
        toastStore.error('Gagal mengunduh dokumen PDF rapor.');
      }
    } else {
      toastStore.error(error.response?.data?.error || 'Gagal mengunduh dokumen PDF rapor.');
    }
  } finally {
    isDownloading.value = false;
  }
};

onMounted(loadPageData);
</script>

<style scoped>
/* Menghilangkan scrollbar bawaan pada list tugas dropdown */
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>