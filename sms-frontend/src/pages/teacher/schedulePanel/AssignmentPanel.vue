<template>
  <div class="space-y-8">
    
    <div class="flex justify-start">
      <button
        @click="showForm = !showForm"
        :class="showForm ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300' : 'bg-brand-red text-white hover:bg-brand-orange shadow-sm'"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all"
      >
        <svg v-if="!showForm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        {{ showForm ? 'Batal Tambah Tugas' : 'Buat Tugas Baru' }}
      </button>
    </div>

    <transition
      enter-active-class="transition duration-300 ease-out origin-top"
      enter-from-class="transform scale-y-95 opacity-0 -translate-y-2"
      enter-to-class="transform scale-y-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-200 ease-in origin-top"
      leave-from-class="transform scale-y-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-y-95 opacity-0 -translate-y-2"
    >
      <div v-if="showForm" class="bg-white p-6 rounded-2xl shadow-sm border border-brand-red/20">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
          Detail Tugas Baru
        </h3>
        
        <form @submit.prevent="submitAssignment" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Tugas <span class="text-red-500">*</span></label>
              <input v-model="form.title" type="text" required placeholder="Misal: Latihan Soal Logaritma" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Tenggat Waktu (Deadline) <span class="text-red-500">*</span></label>
              <input v-model="form.due_date" type="datetime-local" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Instruksi / Deskripsi <span class="text-red-500">*</span></label>
            <textarea v-model="form.description" required rows="3" placeholder="Kerjakan soal di halaman..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm"></textarea>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Lampiran Soal (Maksimal 5 File)</label>
            
            <div 
              class="flex items-center justify-center w-full relative"
              @dragover.prevent="isDragging = true"
              @dragenter.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
            >
              <label 
                class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-xl cursor-pointer transition-colors" 
                :class="[
                  isDragging ? 'border-brand-red bg-red-100/80 scale-[1.02]' : 
                  selectedFiles.length > 0 ? 'border-brand-red/50 bg-red-50' : 
                  'border-gray-300 bg-gray-50 hover:bg-gray-100'
                ]"
              >
                <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4 pointer-events-none">
                  <svg :class="isDragging ? 'text-brand-red animate-bounce' : 'text-gray-400'" class="w-8 h-8 mb-3 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                  <p class="mb-1 text-sm" :class="isDragging ? 'text-brand-red font-bold' : 'text-gray-500'">
                    <span v-if="isDragging">Lepaskan file di sini!</span>
                    <span v-else><span class="font-semibold text-brand-red">Klik untuk upload</span> atau seret file ke sini</span>
                  </p>
                  <p class="text-xs text-gray-500" v-show="!isDragging">PDF, PPT, DOC, XLS, ZIP, PNG, JPG (Maks. 10MB/file)</p>
                </div>
                <input type="file" multiple class="hidden" @change="handleFileSelect" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.zip,.png,.jpg,.jpeg" />
              </label>
            </div>

            <div v-if="selectedFiles.length > 0" class="mt-3 flex flex-wrap gap-2">
              <div v-for="(file, index) in selectedFiles" :key="index" class="bg-gray-100 px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-semibold text-gray-700 border border-gray-200">
                <span class="truncate max-w-[150px]">{{ file.name }}</span>
                <button @click.prevent="removeFile(index)" class="text-red-500 hover:text-red-700 font-bold ml-1 text-sm leading-none">×</button>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button type="submit" :disabled="isSubmitting" class="px-6 py-2.5 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-sm font-bold shadow-sm transition-colors disabled:opacity-50">
              {{ isSubmitting ? 'Menyimpan...' : '📢 Kirim Tugas' }}
            </button>
          </div>
        </form>
      </div>
    </transition>

    <div>
      <div v-if="isLoading" class="flex justify-center items-center py-12">
      <div
        class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-red"
      ></div>
    </div>
      
      <div v-else>
        <h3 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Tugas Pertemuan Ini ({{ selectedDate }})</h3>
        <div v-if="currentAssignments.length === 0" class="bg-gray-50 p-4 rounded-xl text-center text-sm text-gray-500 border border-gray-200 mb-6">Tidak ada tugas yang ditambahkan pada pertemuan ini.</div>
        
        <div v-else class="space-y-3 mb-6">
          <div v-for="item in currentAssignments" :key="item.id" class="bg-white border border-gray-200 p-4 sm:p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col sm:flex-row justify-between gap-4">
            <div class="w-full">
              <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">Tenggat: {{ new Date(item.due_date).toLocaleString() }}</span>
              </div>
              <h4 class="text-lg font-bold text-gray-800">{{ item.title }}</h4>
              <p class="text-sm text-gray-600 mt-1 mb-3">{{ item.description }}</p>
              
              <div v-if="item.attachments && item.attachments.length > 0" class="flex flex-wrap gap-2 mb-3">
                <a v-for="(path, idx) in item.attachments" :key="idx" :href="getStorageUrl(path)" target="_blank" class="text-xs font-semibold text-brand-red bg-red-50 px-2 py-1 rounded-md border border-red-100 hover:bg-red-100">
                  📎 Lampiran {{ idx + 1 }}
                </a>
              </div>
            </div>
            <div class="flex sm:flex-col gap-2 shrink-0">
              <button @click="goToDetail(item.id)" class="flex-1 px-4 py-2 bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 font-bold text-sm rounded-xl transition-colors whitespace-nowrap">
                Periksa Jawaban ({{ item.submissions_count || 0 }})
              </button>
              <button @click="deleteAssignment(item.id)" class="px-4 py-2 bg-gray-50 hover:bg-red-50 text-gray-500 hover:text-red-600 border border-gray-200 font-bold text-sm rounded-xl transition-colors">
                Hapus
              </button>
            </div>
          </div>
        </div>

        <h3 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Tugas Terdahulu / Lainnya</h3>
        <div v-if="pastAssignments.length === 0" class="bg-gray-50 p-4 rounded-xl text-center text-sm text-gray-500 border border-gray-200">Belum ada riwayat tugas sebelumnya.</div>
        
        <div v-else class="space-y-3">
          <div v-for="item in pastAssignments" :key="item.id" class="bg-white border border-gray-200 p-4 sm:p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col sm:flex-row justify-between gap-4">
            <div class="w-full">
              <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded">Tenggat: {{ new Date(item.due_date).toLocaleString() }}</span>
                <span class="text-xs text-gray-500 font-medium">Tgl Dibuat: {{ item.date }}</span>
              </div>
              <h4 class="text-lg font-bold text-gray-800">{{ item.title }}</h4>
              <p class="text-sm text-gray-600 mt-1 mb-3">{{ item.description }}</p>
              
              <div v-if="item.attachments && item.attachments.length > 0" class="flex flex-wrap gap-2 mb-3">
                <a v-for="(path, idx) in item.attachments" :key="idx" :href="getStorageUrl(path)" target="_blank" class="text-xs font-semibold text-brand-red bg-red-50 px-2 py-1 rounded-md border border-red-100 hover:bg-red-100">
                  📎 Lampiran {{ idx + 1 }}
                </a>
              </div>
            </div>
            <div class="flex sm:flex-col gap-2 shrink-0">
              <button @click="goToDetail(item.id)" class="flex-1 px-4 py-2 bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 font-bold text-sm rounded-xl transition-colors whitespace-nowrap">
                Periksa Jawaban ({{ item.submissions_count || 0 }})
              </button>
              <button @click="deleteAssignment(item.id)" class="px-4 py-2 bg-gray-50 hover:bg-red-50 text-gray-500 hover:text-red-600 border border-gray-200 font-bold text-sm rounded-xl transition-colors">
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { assignmentService } from '../../../services/modules/teacher/assignmentService';
import { useToastStore } from '../../../stores/toast';

const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true }
});

const router = useRouter();
const toastStore = useToastStore();

// STATE UTAMA
const showForm = ref(false);
const assignments = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const form = ref({ title: '', description: '', due_date: '' });
const selectedFiles = ref([]);

// STATE DRAG & DROP (Ini yang sebelumnya memicu error)
const isDragging = ref(false);

// Memisahkan data berdasarkan tanggal terpilih
const currentAssignments = computed(() => assignments.value.filter(a => a.date === props.selectedDate));
const pastAssignments = computed(() => assignments.value.filter(a => a.date !== props.selectedDate));

const getStorageUrl = (path) => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
  return `${baseUrl}/storage/${path}`;
};

// ----------------- FUNGSI UPLOAD & DRAG DROP -----------------

// Fungsi pusat untuk validasi file
const processFiles = (filesArray) => {
  if (selectedFiles.value.length + filesArray.length > 5) {
    toastStore.error("Maksimal hanya 5 file lampiran soal yang bisa diunggah.");
    return;
  }

  const validFiles = filesArray.filter((f) => f.size <= 10 * 1024 * 1024);
  if (validFiles.length < filesArray.length) {
    toastStore.error("Beberapa file ditolak karena ukurannya melebihi 10MB.");
  }

  selectedFiles.value = [...selectedFiles.value, ...validFiles];
};

// Memicu saat lewat tombol 'Klik' manual
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  processFiles(files);
  event.target.value = ""; // Reset input form
};

// Memicu saat pengguna melepaskan (Drop) file ke area zona
const handleDrop = (event) => {
  isDragging.value = false; // Matikan efek hover
  
  if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
    const files = Array.from(event.dataTransfer.files);
    processFiles(files);
  }
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

// ----------------- MANAJEMEN TUGAS -----------------
const fetchAssignments = async () => {
  isLoading.value = true;
  try {
    const res = await assignmentService.getAssignments(props.scheduleId);
    assignments.value = res.data;
  } catch (error) {
    toastStore.error("Gagal memuat daftar tugas.");
  } finally {
    isLoading.value = false;
  }
};

const submitAssignment = async () => {
  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append("schedule_id", props.scheduleId);
    formData.append("date", props.selectedDate);
    formData.append("title", form.value.title);
    formData.append("description", form.value.description);

    const formattedDate = form.value.due_date.replace("T", " ") + ":00";
    formData.append("due_date", formattedDate);

    selectedFiles.value.forEach((file) => formData.append("files[]", file));

    await assignmentService.createAssignment(formData);
    toastStore.success("Tugas berhasil disebarkan!");

    form.value = { title: "", description: "", due_date: "" };
    selectedFiles.value = [];
    fetchAssignments();
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal membuat tugas.");
  } finally {
    isSubmitting.value = false;
  }
};

const deleteAssignment = async (id) => {
  if (!confirm("Hapus tugas ini beserta seluruh jawaban siswa yang sudah terkumpul?")) return;
  try {
    await assignmentService.deleteAssignment(id);
    toastStore.success("Tugas dihapus.");
    fetchAssignments();
  } catch (error) {
    toastStore.error("Gagal menghapus tugas.");
  }
};

// ----------------- NAVIGASI KE DETAIL -----------------
const goToDetail = (assignmentId) => {
  router.push({ name: 'TeacherAssignmentDetail', params: { id: assignmentId } });
};

onMounted(() => fetchAssignments());

watch(() => props.selectedDate, () => {
  // Tidak perlu fetch ulang karena computed property menangani filter otomatis
});
</script>