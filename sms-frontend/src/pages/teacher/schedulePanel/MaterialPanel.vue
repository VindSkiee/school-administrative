<template>
  <div class="space-y-6">
    <div class="flex justify-start">
      <button
        @click="showForm = !showForm"
        :class="
          showForm
            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300'
            : 'bg-brand-red text-white hover:bg-brand-orange shadow-sm'
        "
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all"
      >
        <svg
          v-if="!showForm"
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          ></path>
        </svg>
        <svg
          v-else
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          ></path>
        </svg>
        {{ showForm ? "Batal Kirim Materi" : "Kirim Materi Baru" }}
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
      <div
        v-if="showForm"
        class="bg-white p-6 rounded-2xl shadow-sm border border-brand-red/20"
      >
        <h3
          class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"
        >
          <svg
            class="w-5 h-5 text-brand-red"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            ></path>
          </svg>
          Unggah Materi Baru
        </h3>

        <form @submit.prevent="submitMaterial" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Judul Materi <span class="text-red-500">*</span></label
            >
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Misal: Modul Bab 1 - Trigonometri Dasar"
              class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Deskripsi Tambahan</label
            >
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Tuliskan pesan atau instruksi untuk siswa membaca materi ini..."
              class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Pilih File (Maksimal 10 File)
              <span class="text-red-500">*</span></label
            >

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
                  isDragging
                    ? 'border-brand-red bg-red-100/80 scale-[1.02]'
                    : selectedFiles.length > 0
                      ? 'border-brand-red/50 bg-red-50'
                      : 'border-gray-300 bg-gray-50 hover:bg-gray-100',
                ]"
              >
                <div
                  class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4 pointer-events-none"
                >
                  <svg
                    :class="
                      isDragging
                        ? 'text-brand-red animate-bounce'
                        : 'text-gray-400'
                    "
                    class="w-8 h-8 mb-3 transition-colors"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                    ></path>
                  </svg>
                  <p
                    class="mb-1 text-sm"
                    :class="
                      isDragging ? 'text-brand-red font-bold' : 'text-gray-500'
                    "
                  >
                    <span v-if="isDragging">Lepaskan file di sini!</span>
                    <span v-else
                      ><span class="font-semibold text-brand-red"
                        >Klik untuk upload</span
                      >
                      atau seret file ke sini</span
                    >
                  </p>
                  <p class="text-xs text-gray-500" v-show="!isDragging">
                    PDF, PPT, DOC, XLS, ZIP, MP4, PNG, JPG (Maks. 10MB/file)
                  </p>
                </div>
                <input
                  type="file"
                  multiple
                  class="hidden"
                  @change="handleFileSelect"
                  accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.zip,.png,.jpg,.jpeg"
                />
              </label>
            </div>

            <div
              v-if="selectedFiles.length > 0"
              class="mt-3 flex flex-wrap gap-2"
            >
              <div
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="bg-gray-100 px-3 py-1.5 rounded-lg flex items-center gap-2 text-xs font-semibold text-gray-700 border border-gray-200"
              >
                <span class="truncate max-w-[150px]">{{ file.name }}</span>
                <button
                  @click.prevent="removeFile(index)"
                  class="text-red-500 hover:text-red-700 font-bold ml-1 text-sm leading-none"
                >
                  ×
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="isSubmitting || selectedFiles.length === 0"
              class="px-6 py-2.5 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-sm font-bold shadow-sm transition-colors disabled:opacity-50"
            >
              {{ isSubmitting ? "Mengunggah..." : "🚀 Unggah Materi" }}
            </button>
          </div>
        </form>
      </div>
    </transition>

    <div>
      <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
        Materi pada Pertemuan Ini
      </h3>

      <div v-if="isLoading" class="py-8 flex justify-center">
        <div
        class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-red"
      ></div>
      </div>

      <div
        v-else-if="materials.length === 0"
        class="bg-gray-50 rounded-2xl p-8 text-center border border-gray-200"
      >
        <p class="text-gray-500">
          Belum ada materi yang diunggah untuk tanggal ini.
        </p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="item in materials"
          :key="item.id"
          class="bg-white border border-gray-200 p-5 rounded-2xl shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="flex justify-between items-start gap-4">
            <div class="w-full">
              <h4 class="text-lg font-bold text-brand-red">{{ item.title }}</h4>
              <p class="text-sm text-gray-600 mt-1 mb-4">
                {{ item.description || "Tidak ada deskripsi." }}
              </p>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                <a
                  v-for="(path, idx) in item.attachments"
                  :key="idx"
                  :href="getStorageUrl(path)"
                  target="_blank"
                  class="flex items-center gap-2 p-2 bg-red-50 hover:bg-red-100 border border-red-100 rounded-lg text-sm text-red-800 transition-colors group"
                >
                  <svg
                    class="w-5 h-5 flex-shrink-0 text-red-500 group-hover:text-red-700"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                  </svg>
                  <span class="truncate font-semibold">{{
                    getFileName(path)
                  }}</span>
                </a>
              </div>
            </div>

            <button
              @click="deleteMaterial(item.id)"
              class="text-gray-400 hover:text-red-600 transition-colors p-2 bg-gray-50 hover:bg-red-50 rounded-lg shrink-0"
              title="Hapus Materi"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                ></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { materialService } from "../../../services/modules/teacher/materialService";
import { useToastStore } from "../../../stores/toast";

const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true },
});

const toastStore = useToastStore();

const showForm = ref(false);
const materials = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);

const form = ref({
  title: "",
  description: "",
});
const selectedFiles = ref([]);
const isDragging = ref(false);

// ----------------- FUNGSI UPLOAD & DRAG DROP -----------------

// Fungsi pusat untuk validasi file materi (Maks 10 file)
const processFiles = (filesArray) => {
  if (selectedFiles.value.length + filesArray.length > 10) {
    toastStore.error("Maksimal hanya 10 file yang bisa diunggah sekaligus.");
    return;
  }

  const validFiles = filesArray.filter((f) => f.size <= 10 * 1024 * 1024);
  if (validFiles.length < filesArray.length) {
    toastStore.error("Beberapa file ditolak karena ukurannya melebihi 10MB.");
  }

  selectedFiles.value = [...selectedFiles.value, ...validFiles];
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  processFiles(files);
  event.target.value = ""; // Reset input
};

const handleDrop = (event) => {
  isDragging.value = false;
  if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
    const files = Array.from(event.dataTransfer.files);
    processFiles(files);
  }
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

// ----------------- HELPER -----------------
const getStorageUrl = (path) => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";
  return `${baseUrl}/storage/${path}`;
};

const getFileName = (path) => {
  if (!path) return "Dokumen";
  const parts = path.split("/");
  return parts[parts.length - 1].substring(0, 20) + "...";
};

// ----------------- MANAJEMEN MATERI -----------------
const fetchMaterials = async () => {
  isLoading.value = true;
  try {
    const response = await materialService.getMaterials(
      props.scheduleId,
      props.selectedDate,
    );
    materials.value = response.data;
  } catch (error) {
    toastStore.error("Gagal memuat daftar materi.");
  } finally {
    isLoading.value = false;
  }
};

const submitMaterial = async () => {
  if (selectedFiles.value.length === 0) return;

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append("schedule_id", props.scheduleId);
    formData.append("date", props.selectedDate);
    formData.append("title", form.value.title);
    if (form.value.description) {
      formData.append("description", form.value.description);
    }

    selectedFiles.value.forEach((file) => {
      formData.append("files[]", file);
    });

    await materialService.uploadMaterial(formData);

    toastStore.success("Materi berhasil diunggah!");

    form.value.title = "";
    form.value.description = "";
    selectedFiles.value = [];

    fetchMaterials();
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal mengunggah materi.",
    );
  } finally {
    isSubmitting.value = false;
  }
};

const deleteMaterial = async (id) => {
  if (
    !confirm(
      "Apakah Anda yakin ingin menghapus materi beserta seluruh filenya?",
    )
  )
    return;

  try {
    await materialService.deleteMaterial(id);
    toastStore.success("Materi berhasil dihapus.");
    fetchMaterials();
  } catch (error) {
    toastStore.error("Gagal menghapus materi.");
  }
};

watch(
  () => props.selectedDate,
  () => {
    fetchMaterials();
  },
);

onMounted(() => {
  fetchMaterials();
});
</script>
