<template>
  <div class="space-y-6">
    <div
      class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Tugas & Penilaian
        </h1>
        <p class="text-gray-500 mt-1">
          Pantau dan periksa semua tugas dari seluruh kelas Anda di satu tempat.
        </p>
      </div>
      <div
        class="bg-brand-red/10 text-brand-red px-4 py-2 rounded-xl font-bold border border-brand-red/20 whitespace-nowrap"
      >
        Total Tugas: {{ assignments.length }}
      </div>
    </div>

    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="currentFilter = tab.id"
          class="w-full px-6 py-2 rounded-xl text-sm font-semibold transition-all"
          :class="
            currentFilter === tab.id
              ? 'bg-brand-red text-white shadow-sm'
              : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
          "
        >
          <div class="flex flex-col items-center">
            <span>{{ tab.label }}</span>

            <span v-if="tab.id !== 'all'" class="text-xs mt-1 opacity-80">
              {{ getCountByFilter(tab.id) }}
            </span>
          </div>
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="py-16 flex justify-center">
      <div
        class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-red"
      ></div>
    </div>

    <div
      v-else-if="filteredAssignments.length === 0"
      class="bg-white rounded-3xl p-12 text-center border border-gray-200 shadow-sm"
    >
      <div
        class="w-20 h-20 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4"
      >
        <svg
          class="w-10 h-10"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
          ></path>
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-800 mb-2">Tidak Ada Tugas</h3>
      <p class="text-gray-500">
        {{
          currentFilter === "active"
            ? "Tidak ada tugas yang sedang berjalan saat ini."
            : currentFilter === "closed"
              ? "Belum ada riwayat tugas yang sudah ditutup."
              : "Anda belum menyebarkan tugas apa pun ke kelas-kelas Anda."
        }}
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="item in filteredAssignments"
        :key="item.id"
        class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-lg transition-all flex flex-col overflow-hidden group"
        :class="
          isClosed(item.due_date)
            ? 'opacity-90 hover:opacity-100'
            : 'hover:border-brand-red/30'
        "
      >
        <div
          class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-start gap-2"
        >
          <div>
            <span
              class="px-2.5 py-1 bg-gray-200 text-gray-700 text-[10px] font-extrabold uppercase tracking-wider rounded-lg"
            >
              {{ item.schedule?.school_class?.name || "Tanpa Kelas" }}
            </span>
            <p class="text-xs font-bold text-brand-red mt-2">
              {{ item.schedule?.subject?.name || "Mata Pelajaran" }}
            </p>
          </div>
          <span
            :class="
              isClosed(item.due_date)
                ? 'bg-gray-100 text-gray-600 border-gray-200'
                : 'bg-green-50 text-green-600 border-green-200'
            "
            class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border whitespace-nowrap"
          >
            {{ isClosed(item.due_date) ? "Selesai / Ditutup" : "Aktif" }}
          </span>
        </div>

        <div class="p-5 flex-1 flex flex-col">
          <h4
            class="text-lg font-bold text-gray-800 line-clamp-1"
            :title="item.title"
          >
            {{ item.title }}
          </h4>

          <div
            class="mt-4 bg-gray-50 p-3 rounded-xl border border-gray-100 flex-1 flex flex-col justify-center"
          >
            <div class="flex justify-between items-center text-sm mb-2">
              <span class="text-gray-500">Tenggat Waktu:</span>
              <span
                class="font-bold"
                :class="
                  isClosed(item.due_date) ? 'text-red-600' : 'text-gray-800'
                "
              >
                {{ formatDate(effectiveDeadline(item.due_date)) }}
              </span>
              <p v-if="isReportPublished" class="text-[10px] text-amber-600 font-semibold mt-1">
                Dikunci saat penerbitan rapor
              </p>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Terkumpul:</span>
              <span
                class="font-bold"
                :class="
                  item.submissions_count > 0
                    ? 'text-green-600'
                    : 'text-orange-500'
                "
              >
                {{ item.submissions_count || 0 }} Siswa
              </span>
            </div>
          </div>
        </div>

        <div class="p-5 pt-0 mt-auto">
          <button
            @click="goToDetail(item.id)"
            :class="
              isClosed(item.due_date)
                ? 'bg-gray-800 hover:bg-gray-900'
                : 'bg-brand-red hover:bg-brand-orange'
            "
            class="w-full py-2.5 text-white text-sm font-bold rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2"
          >
            <svg
              v-if="isClosed(item.due_date)"
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
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
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              ></path>
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
              ></path>
            </svg>

            {{
              isClosed(item.due_date) ? "Lihat Rekap Nilai" : "Periksa & Nilai"
            }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { assignmentService } from "../../services/modules/teacher/assignmentService";
import { useToastStore } from "../../stores/toast";
import { useReportStatus } from '../../composables/useReportStatus';

const router = useRouter();
const toastStore = useToastStore();
const { isReportPublished, publishedAt } = useReportStatus('teacher');

const assignments = ref([]);
const isLoading = ref(true);

// Sistem Tab Filter
const currentFilter = ref("active"); // Default langsung ke tugas aktif
const tabs = [
  { id: "all", label: "Semua Tugas" },
  { id: "active", label: "Aktif" },
  { id: "closed", label: "Tenggat Selesai" },
];

// Helper Pendeteksi Waktu — when report is published, all assignments are treated as closed
const isClosed = (dueDate) => {
  if (isReportPublished.value) return true;
  if (!dueDate) return false;
  return new Date() > new Date(dueDate);
};

// Effective deadline: when report is published, show published date as the cutoff
const effectiveDeadline = (dueDate) => {
  if (isReportPublished.value && publishedAt.value) return publishedAt.value;
  return dueDate;
};

// Filter Otomatis Data Tugas
const filteredAssignments = computed(() => {
  if (currentFilter.value === "active")
    return assignments.value.filter((a) => !isClosed(a.due_date));
  if (currentFilter.value === "closed")
    return assignments.value.filter((a) => isClosed(a.due_date));
  return assignments.value; // 'all'
});

// Hitung jumlah tugas untuk angka di dalam Tab
const getCountByFilter = (filterId) => {
  if (filterId === "active")
    return assignments.value.filter((a) => !isClosed(a.due_date)).length;
  if (filterId === "closed")
    return assignments.value.filter((a) => isClosed(a.due_date)).length;
  return 0;
};

const fetchGlobalAssignments = async () => {
  isLoading.value = true;
  try {
    const res = await assignmentService.getAllAssignments();
    // PERF FIX: paginated response wraps data in { data: [...], total: ..., ... }
    assignments.value = res.data.data || res.data || [];
  } catch (error) {
    toastStore.error("Gagal memuat daftar tugas global.");
    assignments.value = [];
  } finally {
    isLoading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "-";
  const options = {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  return new Date(dateString).toLocaleDateString("id-ID", options);
};

const goToDetail = (assignmentId) => {
  router.push({
    name: "TeacherAssignmentDetail",
    params: { id: assignmentId },
  });
};

onMounted(() => {
  fetchGlobalAssignments();
});
</script>

<style scoped>
/* Menyembunyikan scrollbar bawaan agar Tab lebih cantik di Mobile */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
