<template>
  <div class="space-y-6 pb-12">
    <div class="bg-gradient-to-r from-brand-orange to-brand-red rounded-3xl p-6 md:p-8 text-white shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-2xl md:text-3xl font-bold font-serif">Daftar Tugas</h1>
        </div>
        <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
          Pantau tenggat waktu (deadline) dan jangan sampai ada tugas yang terlewat!
        </p>
      </div>
      <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl items-center justify-center border border-white/30 shrink-0 hidden md:flex">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
      </div>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4 z-20 relative">
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="onSearchInput"
          placeholder="Cari tugas..." 
          class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-orange/20 focus:border-brand-orange outline-none transition-all"
        >
      </div>

      <div class="w-full md:w-56 shrink-0">
        <BaseSelect
          v-model="filterSubject"
          :options="subjectOptions"
          placeholder="Semua Mapel"
          emptyMessage="Tidak ada mapel"
          @update:modelValue="applyLocalFilter"
        />
      </div>

      <div class="relative w-full md:w-48 shrink-0">
        <input 
          type="date" 
          v-model="filterDate"
          @change="fetchAssignments"
          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 focus:ring-2 focus:ring-brand-orange/20 focus:border-brand-orange outline-none transition-all"
        >
        <button v-if="filterDate" @click="clearDate" class="absolute inset-y-0 right-10 flex items-center text-gray-400 hover:text-brand-red">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
    </div>

    <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-2">
      <button 
        v-for="tf in typeFilters" :key="tf.id"
        @click="filterType = tf.id"
        class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all border"
        :class="filterType === tf.id ? 'bg-brand-red border-brand-red text-white shadow-sm' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
      >
        {{ tf.label }}
      </button>
    </div>

    <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-2">
      <button 
        v-for="tab in tabs" :key="tab.id"
        @click="activeTab = tab.id"
        class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all shadow-sm border"
        :class="activeTab === tab.id ? tab.activeClass : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
      >
        {{ tab.label }}
        <span class="ml-2 px-2 py-0.5 rounded-md text-[10px]" :class="activeTab === tab.id ? 'bg-white/30 text-current' : 'bg-gray-100 text-gray-500'">
          {{ getFilteredCount(tab.id) }}
        </span>
      </button>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-orange mb-3"></div>
      <p class="text-gray-500 font-medium text-sm">Menarik daftar tugas...</p>
    </div>

    <div v-else-if="filteredAssignments.length === 0" class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-200">
      <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
      <h3 class="text-xl font-bold text-gray-800">Tidak Ada Tugas</h3>
      <p class="text-gray-500 mt-2 max-w-md mx-auto text-sm">
        Tidak ada tugas yang cocok dengan kategori atau filter yang Anda pilih.
      </p>
    </div>

    <div v-else class="space-y-5">
      <div v-for="task in filteredAssignments" :key="task.id" class="bg-white border border-gray-200 rounded-2xl p-5 md:p-6 shadow-sm flex flex-col lg:flex-row gap-6 relative overflow-hidden transition-all hover:shadow-md">
        
        <div class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors" :class="taskStatusMap.get(task.id)?.barColor"></div>

        <div class="flex-1 pl-2 w-full">
          <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
              <div class="flex items-center gap-2 mb-1.5">
                <span class="px-2.5 py-1 bg-red-50 text-brand-red text-[10px] font-bold rounded-lg uppercase tracking-wider">
                  {{ task.schedule?.subject?.name || 'Mata Pelajaran' }}
                </span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg" :class="getTypeBadge(task.type).classes">
                  {{ getTypeBadge(task.type).label }}
                </span>
                <span class="text-xs font-semibold text-gray-500">Diunggah: {{ formatDate(task.created_at) }}</span>
              </div>
              <h3 class="text-xl font-bold text-gray-800">{{ task.title }}</h3>
            </div>
            
            <div class="px-3 py-1.5 rounded-lg border flex items-center gap-1.5 text-xs font-bold shrink-0" :class="getDeadlineClass(task.due_date)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              {{ isReportPublished ? 'Dikunci saat rapor' : 'Tenggat' }}: {{ formatDateTime(effectiveDeadline(task.due_date)) }}
            </div>
          </div>

          <p class="text-sm text-gray-600 leading-relaxed mb-4 line-clamp-3">{{ task.description }}</p>

          <div v-if="task.attachments && task.attachments.length > 0" class="mb-4 flex flex-wrap gap-2">
            <button v-for="(path, idx) in task.attachments" :key="idx" @click="previewAttachment(path)" class="px-3 py-1.5 bg-gray-50 border border-gray-200 hover:border-brand-orange hover:text-brand-orange text-gray-600 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              Soal {{ idx + 1 }}
            </button>
          </div>

          <button @click="goToMaterial(task.schedule_id)" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1 w-fit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Buka Pustaka Materi Pelajaran Ini
          </button>
        </div>

        <div class="w-full lg:w-64 shrink-0 flex flex-col justify-between bg-gray-50 rounded-xl p-4 border border-gray-100">
          <div class="mb-4">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Status</p>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold" :class="taskStatusMap.get(task.id)?.badgeClass">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="taskStatusMap.get(task.id)?.icon"></path></svg>
              {{ taskStatusMap.get(task.id)?.text }}
            </div>
          </div>

          <div v-if="task.submission && task.submission.grade" class="mt-auto pt-3 border-t border-gray-200">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Nilai Anda</p>
            <div class="flex items-end gap-2">
              <span class="text-3xl font-black" :class="task.submission.grade.score >= 75 ? 'text-green-600' : 'text-brand-red'">{{ task.submission.grade.score }}</span>
              <span class="text-xs font-bold text-gray-400 mb-1">/ 100</span>
            </div>
            <p v-if="task.submission.grade.feedback" class="text-xs text-gray-500 mt-1 italic">"{{ task.submission.grade.feedback }}"</p>
          </div>

          <div v-else class="mt-auto">
            <div v-if="task.submission" class="mb-2">
              <p class="text-xs text-gray-500 font-medium">Jawaban: <button @click="previewAttachment(task.submission.file_path)" class="font-semibold text-blue-600 hover:underline">Lihat File ✓</button></p>
            </div>
            <button v-if="canSubmit(task)" @click="openModal(task)" class="w-full py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center justify-center gap-2" :class="task.submission ? 'bg-white border-2 border-brand-orange text-brand-orange hover:bg-orange-50' : 'bg-brand-orange hover:bg-orange-600 text-white'">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              {{ task.submission ? 'Edit Jawaban' : 'Kerjakan' }}
            </button>
            <button v-else disabled class="w-full py-2.5 bg-gray-200 text-gray-400 rounded-xl text-sm font-bold cursor-not-allowed">
              {{ isReportPublished ? 'Rapor Diterbitkan' : 'Akses Ditutup' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in-up">
        <div class="p-6 md:p-8">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kumpul Jawaban</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-brand-red p-1 bg-gray-50 hover:bg-red-50 rounded-full">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>
          <div class="relative border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:bg-gray-50 hover:border-brand-orange transition-colors" :class="{'border-brand-orange bg-orange-50': selectedFile}">
            <input type="file" @change="handleFileUpload" accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
            <div v-if="!selectedFile" class="pointer-events-none">
              <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm text-gray-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></div>
              <p class="text-sm text-gray-600 font-medium mb-1"><span class="text-brand-orange font-bold">Pilih File</span> atau seret ke sini</p>
              <p class="text-xs text-gray-400">Maks 10MB</p>
            </div>
            <div v-else class="pointer-events-none flex flex-col items-center">
              <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3 text-brand-orange"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
              <p class="text-sm font-bold text-gray-800 break-all">{{ selectedFile.name }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
            <button @click="submitTask" :disabled="!selectedFile || isSubmitting" class="px-6 py-2.5 bg-brand-orange hover:bg-orange-600 text-white text-sm font-bold rounded-xl shadow-sm disabled:opacity-50 flex items-center gap-2">
              <svg v-if="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
              {{ isSubmitting ? 'Mengirim...' : 'Kirim Jawaban' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { studentAssignmentService } from '../../services/modules/student/assignmentService';
import { useToastStore } from '../../stores/toast';
import BaseSelect from '../../components/BaseSelect.vue';
import BasePopoverInfo from '../../components/BasePopoverInfo.vue';
import { useReportStatus } from '../../composables/useReportStatus';

const router = useRouter();
const route = useRoute();
const toastStore = useToastStore();
const { isReportPublished, publishedAt } = useReportStatus('student');

// STATE UTAMA
const allAssignments = ref([]); // Data mentah dari API
const isLoading = ref(true);

// FILTER STATE
const searchQuery = ref('');
const filterDate = ref('');
const filterSubject = ref('');
const filterType = ref('all');
const subjectOptions = ref([]); // Diekstrak dinamis dari data
let searchTimeout = null;

// TABS STATE
const activeTab = ref(route.query.tab || 'all');
const tabs = [
  { id: 'all', label: 'Semua Tugas', activeClass: 'bg-gray-800 border-gray-800 text-white' },
  { id: 'pending', label: 'Belum Dikerjakan', activeClass: 'bg-brand-orange border-brand-orange text-white' },
  { id: 'submitted', label: 'Menunggu Nilai', activeClass: 'bg-blue-600 border-blue-600 text-white' },
  { id: 'graded', label: 'Sudah Dinilai', activeClass: 'bg-green-600 border-green-600 text-white' },
];

// Type filter options
const typeFilters = [
  { id: 'all', label: 'Semua' },
  { id: 'task', label: 'Tugas Harian' },
  { id: 'uts', label: 'UTS' },
  { id: 'uas', label: 'UAS' },
];

const getTypeBadge = (type) => {
  switch (type) {
    case 'uts': return { label: 'UTS', classes: 'bg-brand-orange/10 text-brand-orange' };
    case 'uas': return { label: 'UAS', classes: 'bg-brand-red/10 text-brand-red' };
    default: return { label: 'Tugas Harian', classes: 'bg-blue-50 text-blue-700' };
  }
};

// MODAL STATE
const showModal = ref(false);
const isSubmitting = ref(false);
const activeTask = ref(null);
const selectedFile = ref(null);

// LOGIKA UX & TANGGAL
const formatDate = (d) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(d));
const formatDateTime = (d) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(d));
const isPastDeadline = (d) => {
  if (isReportPublished.value) return true;
  return new Date() > new Date(d);
};

// Effective deadline: show published date as cutoff when report is published
const effectiveDeadline = (d) => {
  if (isReportPublished.value && publishedAt.value) return publishedAt.value;
  return d;
};

const getDeadlineClass = (d) => isPastDeadline(d) ? 'border-red-200 text-brand-red bg-red-50' : 'border-gray-200 text-gray-600 bg-gray-50';
const canSubmit = (task) => !isReportPublished.value && !task.submission?.grade && !isPastDeadline(task.due_date);

// DATA FETCHING
const fetchAssignments = async () => {
  isLoading.value = true;
  try {
    const params = {
      search: searchQuery.value || undefined,
      date: filterDate.value || undefined,
    };
    const res = await studentAssignmentService.getAssignments(params);
    allAssignments.value = res.data.data || res.data;

    const subjects = new Map();
    allAssignments.value.forEach(t => {
      if (t.schedule?.subject) subjects.set(t.schedule.subject.id, t.schedule.subject.name);
    });
    subjectOptions.value = [{ value: '', label: 'Semua Mapel' }, ...Array.from(subjects, ([value, label]) => ({ value, label }))];
  } catch (error) {
    toastStore.error('Gagal memuat daftar tugas global.');
  } finally {
    isLoading.value = false;
  }
};

const onSearchInput = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(fetchAssignments, 500); };
const clearDate = () => { filterDate.value = ''; fetchAssignments(); };
const resetFilters = () => { searchQuery.value = ''; filterDate.value = ''; filterSubject.value = ''; filterType.value = 'all'; fetchAssignments(); };
const applyLocalFilter = () => { /* Select terhubung ke computed filteredAssignments */ };

// PERF FIX: cached status map — computed once per assignments change, not per render
const taskStatusMap = computed(() => {
  const map = new Map();
  allAssignments.value.forEach(task => {
    let status;
    if (task.submission?.grade) {
      status = { text: 'Sudah Dinilai', badgeClass: 'bg-green-100 text-green-700', barColor: 'bg-green-500', icon: 'M5 13l4 4L19 7', code: 'graded' };
    } else if (task.submission) {
      status = { text: 'Menunggu Nilai', badgeClass: 'bg-blue-100 text-blue-700', barColor: 'bg-blue-500', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', code: 'submitted' };
    } else if (isPastDeadline(task.due_date)) {
      status = { text: 'Terlewat', badgeClass: 'bg-red-100 text-brand-red', barColor: 'bg-brand-red', icon: 'M6 18L18 6M6 6l12 12', code: 'pending' };
    } else {
      status = { text: 'Belum Dikerjakan', badgeClass: 'bg-orange-100 text-brand-orange', barColor: 'bg-brand-orange', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', code: 'pending' };
    }
    map.set(task.id, status);
  });
  return map;
});

// PERF FIX: combined filter + count in single computed — avoids redundant iterations
const processedAssignments = computed(() => {
  const groups = { all: [], pending: [], submitted: [], graded: [] };

  allAssignments.value.forEach(task => {
    const status = taskStatusMap.value.get(task.id);
    if (!status) return;
    groups.all.push(task);
    if (groups[status.code]) groups[status.code].push(task);
  });

  return groups;
});

// PERF FIX: filtered list derives from processed groups + subject/type filters
const filteredAssignments = computed(() => {
  const base = processedAssignments.value[activeTab.value] || [];

  return base.filter(task => {
    const passSubject = filterSubject.value === '' || task.schedule?.subject?.id === filterSubject.value;
    const passType = filterType.value === 'all' || task.type === filterType.value;
    return passSubject && passType;
  });
});

// PERF FIX: counts derived from processed groups — no separate filter pass
const getFilteredCount = (tabId) => {
  return (processedAssignments.value[tabId] || []).length;
};

// ACTION ROUTING
const goToMaterial = (scheduleId) => {
  router.push({ path: `/student/schedules/${scheduleId}/detail`, query: { tab: 'materials' } });
};

// MODAL UPLOAD ACTIONS
const previewAttachment = (filePath) => window.open(`${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/storage/${filePath}`, '_blank');
const openModal = (task) => { activeTask.value = task; selectedFile.value = null; showModal.value = true; };
const closeModal = () => { showModal.value = false; activeTask.value = null; selectedFile.value = null; };

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 10 * 1024 * 1024) { toastStore.error("Maksimal file 10MB."); e.target.value = ''; return; }
  selectedFile.value = file;
};

const submitTask = async () => {
  if (!selectedFile.value || !activeTask.value) return;
  isSubmitting.value = true;
  try {
    const fd = new FormData(); fd.append('file', selectedFile.value);
    await studentAssignmentService.submitAssignment(activeTask.value.id, fd);
    toastStore.success("Jawaban tugas berhasil dikirim!");
    closeModal();
    fetchAssignments();
  } catch (error) {
    toastStore.error(error.response?.data?.error || "Gagal mengirim jawaban.");
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(fetchAssignments);
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>