<template>
  <div class="space-y-6">
    <div
      class="bg-red-50 border border-red-200 rounded-2xl p-3 sm:p-6 shadow-sm flex items-center gap-4"
    >
      <div
        class="w-6 h-6 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-brand-red shrink-0 shadow-sm"
      >
        <svg
          class="w-3 h-3 sm:w-6 sm:h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
          ></path>
        </svg>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-xs sm:text-lg font-bold text-red-900">
            Pustaka Materi Semester
          </h3>
          <BasePopoverInfo>
            <strong class="text-gray-800 block mb-1">Informasi Pustaka:</strong>
            Halaman ini berisi semua Materi di mapel ini pada semester ini. Materi diurutkan secara kronologis (materi awal semester di paling
            atas, terbaru di bawah).
            <br /><br />
            Jika Anda mencari materi untuk belajar, cukup ketikkan nama
            bab pada kolom pencarian di bawah.
          </BasePopoverInfo>
        </div>
      </div>
    </div>

    <div
      class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4"
    >
      <div class="relative flex-1">
        <div
          class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
        >
          <svg
            class="w-5 h-5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            ></path>
          </svg>
        </div>
        <input
          type="text"
          v-model="searchQuery"
          @input="onSearchInput"
          placeholder="Cari judul materi atau bab..."
          class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red focus:bg-white outline-none transition-all"
        />
      </div>

      <div class="relative w-full sm:w-48 shrink-0">
        <input
          type="date"
          v-model="filterDate"
          @change="fetchMaterials"
          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red focus:bg-white outline-none transition-all"
        />
        <button
          v-if="filterDate"
          @click="clearDate"
          class="absolute inset-y-0 right-10 flex items-center text-gray-400 hover:text-brand-red"
        >
          <svg
            class="w-4 h-4"
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
        </button>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center py-16"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"
      ></div>
      <p class="text-gray-500 font-medium text-sm">Memuat pustaka materi...</p>
    </div>

    <div
      v-else-if="materials.length === 0"
      class="bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200"
    >
      <div
        class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4"
      >
        <svg
          class="w-8 h-8 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          ></path>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">Belum Ada Materi Tersedia</h3>
      <p class="text-gray-500 mt-1 max-w-md mx-auto text-sm">
        {{
          searchQuery || filterDate
            ? "Coba ubah kata kunci pencarian atau filter tanggal Anda."
            : "Guru belum mengunggah materi pelajaran apapun untuk kelas ini."
        }}
      </p>
      <button
        v-if="searchQuery || filterDate"
        @click="resetFilters"
        class="mt-5 px-6 py-2 bg-brand-red hover:bg-brand-orange text-white text-sm font-bold rounded-xl shadow-sm transition-colors"
      >
        Reset Filter
      </button>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="item in materials"
        :key="item.id"
        class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:border-brand-red/30 transition-colors flex flex-col md:flex-row gap-5 items-start relative overflow-hidden group"
      >
        <div
          class="absolute left-0 top-0 bottom-0 w-1 bg-brand-red opacity-0 group-hover:opacity-100 transition-opacity"
        ></div>

        <div class="flex-1 w-full pl-1">
          <div class="flex items-center gap-2 mb-2 justify-end">
            <span class="text-xs font-semibold text-gray-500">
              {{ formatDate(item.created_at) }}
            </span>
          </div>
          <h3
            class="text-lg font-bold text-gray-800 group-hover:text-brand-red transition-colors"
          >
            {{ item.title }}
          </h3>
          <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
            {{ item.description || "Tidak ada deskripsi tambahan." }}
          </p>

          <div
            v-if="item.attachments && item.attachments.length > 0"
            class="mt-5 space-y-3 border-t border-gray-100 pt-4"
          >
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
              Lampiran File:
            </p>

            <div
              v-for="(path, idx) in item.attachments"
              :key="idx"
              class="flex flex-col sm:flex-row gap-3 w-full"
            >
              <button
                @click="previewMaterial(path)"
                class="flex-1 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5"
              >
                <svg
                  class="w-4 h-4 text-gray-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  ></path>
                </svg>
                Baca Lampiran {{ idx + 1 }}
              </button>

              <button
                @click="downloadFile(item.id, path, item.title, idx)"
                :disabled="isDownloading === path"
                class="flex-1 py-2 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-xs font-bold shadow-sm transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50"
              >
                <svg
                  v-if="isDownloading === path"
                  class="animate-spin w-4 h-4 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  ></path>
                </svg>
                {{
                  isDownloading === path
                    ? "Loading..."
                    : `Unduh Lampiran ${idx + 1}`
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { studentMaterialService } from "../../../services/modules/student/materialService";
import { useToastStore } from "../../../stores/toast";
import BasePopoverInfo from "../../../components/BasePopoverInfo.vue";

const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true }, // Disimpan tapi tidak lagi membatasi fetch per tanggal default
});

const toastStore = useToastStore();
const materials = ref([]);
const isLoading = ref(true);
const isDownloading = ref(null);

// Fitur Filter
const searchQuery = ref("");
const filterDate = ref("");
let searchTimeout = null;

const formatDate = (dateString) => {
  if (!dateString) return "";
  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  }).format(new Date(dateString));
};

const fetchMaterials = async () => {
  isLoading.value = true;
  try {
    // Meminta backend untuk mengambil materi spesifik untuk Mata Pelajaran ini
    // KITA TIDAK LAGI mengirim `date: props.selectedDate` agar semua materi semester ini ditarik
    const params = {
      schedule_id: props.scheduleId,
      search: searchQuery.value || undefined,
      date: filterDate.value || undefined,
    };

    const res = await studentMaterialService.getMaterials(params);
    let dataList = res.data.data || res.data;

    // Sortir Array: Materi TERLAMA di atas, TERBARU di bawah (Kronologis)
    materials.value = dataList.sort(
      (a, b) => new Date(a.created_at) - new Date(b.created_at),
    );
  } catch (error) {
    toastStore.error("Gagal memuat pustaka materi sesi ini.");
  } finally {
    isLoading.value = false;
  }
};

// DEBOUNCE PENCARIAN (Menunggu 500ms setelah user berhenti mengetik)
const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchMaterials();
  }, 500);
};

const clearDate = () => {
  filterDate.value = "";
  fetchMaterials();
};

const resetFilters = () => {
  searchQuery.value = "";
  filterDate.value = "";
  fetchMaterials();
};

const previewMaterial = (filePath) => {
  if (!filePath) return;
  const baseUrl = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";
  window.open(`${baseUrl}/storage/${filePath}`, "_blank");
};

// Aksi Download (Membutuhkan 4 parameter sekarang)
const downloadFile = async (id, filePath, title, idx) => {
  if (isDownloading.value) return;
  isDownloading.value = filePath;

  try {
    const response = await studentMaterialService.downloadMaterial(
      id,
      filePath,
    );
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;

    // PERBAIKAN: Ambil ekstensi asli dari nama path file di database (misal: 'pdf', 'png')
    const fileExtension = filePath.split(".").pop();

    // PERBAIKAN: Gabungkan judul materi, nomor lampiran, dan ekstensinya!
    link.setAttribute(
      "download",
      `${title}-lampiran-${idx + 1}.${fileExtension}`,
    );

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    toastStore.success("Materi berhasil diunduh.");
  } catch (error) {
    toastStore.error("Gagal mengunduh file materi.");
  } finally {
    isDownloading.value = null;
  }
};

onMounted(() => {
  fetchMaterials();
});
</script>
