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
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <div class="flex flex-1 overflow-x-auto hide-scrollbar gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="currentFilter = tab.id"
            class="px-5 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap"
            :class="
              currentFilter === tab.id
                ? 'bg-brand-red text-white shadow-sm'
                : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
            "
          >
            <span>{{ tab.label }}</span>
            <span v-if="tab.id !== 'all'" class="ml-1.5 text-xs opacity-80">
              ({{ getCountByFilter(tab.id) }})
            </span>
          </button>
        </div>
        <div class="flex gap-2 shrink-0">
          <BaseSelect
            v-model="filterType"
            :options="typeOptions"
            placeholder="Semua Tipe"
            class="w-40"
          />
          <BaseSelect
            v-model="filterClass"
            :options="classOptions"
            placeholder="Semua Kelas"
            class="w-40"
          />
        </div>
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
          currentFilter === "belum_graded"
            ? "Semua tugas sudah dinilai."
            : currentFilter === "sudah_graded"
              ? "Belum ada tugas yang semua pengumpulannya sudah dinilai."
              : "Anda belum menyebarkan tugas apa pun ke kelas-kelas Anda."
        }}
      </p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="item in filteredAssignments"
        :key="item.id"
        class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-lg hover:border-brand-red/30 transition-all flex flex-col overflow-hidden group"
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
            <div class="flex items-center gap-1.5 mt-2">
              <p class="text-xs font-bold text-brand-red">
                {{ item.schedule?.subject?.name || "Mata Pelajaran" }}
              </p>
              <span
                class="px-1.5 py-0.5 text-[9px] font-bold rounded-md"
                :class="getTypeBadge(item.type).classes"
              >
                {{ getTypeBadge(item.type).label }}
              </span>
            </div>
          </div>
          <span
            v-if="item.submissions_count > 0 && (item.submissions_graded_count || 0) >= item.submissions_count"
            class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border whitespace-nowrap bg-green-50 text-green-600 border-green-200"
          >
            Selesai
          </span>
          <span
            v-else-if="item.submissions_count > 0"
            class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border whitespace-nowrap bg-amber-50 text-amber-600 border-amber-200"
          >
            Perlu Nilai
          </span>
          <span
            v-else
            class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg border whitespace-nowrap bg-blue-50 text-blue-600 border-blue-200"
          >
            Belum Ada
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
            <div v-if="item.submissions_count > 0" class="flex justify-between items-center text-sm mt-1.5">
              <span class="text-gray-500">Dinilai:</span>
              <span
                class="font-bold"
                :class="
                  (item.submissions_graded_count || 0) >= item.submissions_count
                    ? 'text-green-600'
                    : 'text-amber-600'
                "
              >
                {{ item.submissions_graded_count || 0 }} / {{ item.submissions_count }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-5 pt-0 mt-auto">
          <button
            @click="goToDetail(item.id)"
            class="w-full py-2.5 bg-brand-red hover:bg-brand-orange text-white text-sm font-bold rounded-xl shadow-sm transition-colors flex justify-center items-center gap-2"
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
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              ></path>
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
              ></path>
            </svg>
            Periksa & Nilai
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { assignmentService } from "../../services/modules/teacher/assignmentService";
import { useToastStore } from "../../stores/toast";
import BaseSelect from "../../components/BaseSelect.vue";

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

const assignments = ref([]);
const isLoading = ref(true);

// Tab Filter — default ke "Belum Dinilai"
const currentFilter = ref("belum_graded");
const tabs = [
  { id: "belum_graded", label: "Belum Dinilai" },
  { id: "sudah_graded", label: "Sudah Dinilai" },
  { id: "all", label: "Semua" },
];

// Filter state
const filterType = ref("");
const filterClass = ref("");

const typeOptions = [
  { value: "task", label: "Tugas" },
  { value: "ujian_harian", label: "Ujian Harian" },
  { value: "uts", label: "UTS" },
  { value: "uas", label: "UAS" },
];

const classOptions = computed(() => {
  const classes = new Map();
  assignments.value.forEach((a) => {
    const name = a.schedule?.school_class?.name;
    if (name) classes.set(name, name);
  });
  return Array.from(classes, ([value, label]) => ({ value, label }));
});

const getTypeBadge = (type) => {
  switch (type) {
    case "ujian_harian":
      return { label: "UH", classes: "bg-green-50 text-green-700" };
    case "uts":
      return { label: "UTS", classes: "bg-brand-orange/10 text-brand-orange" };
    case "uas":
      return { label: "UAS", classes: "bg-brand-red/10 text-brand-red" };
    default:
      return { label: "Tugas", classes: "bg-blue-50 text-blue-700" };
  }
};

// Filter Otomatis Data Tugas
const filteredAssignments = computed(() => {
  let result = assignments.value;

  // Tab filter
  if (currentFilter.value === "belum_graded") {
    result = result.filter(
      (a) =>
        a.submissions_count > 0 &&
        (a.submissions_graded_count || 0) < a.submissions_count,
    );
  } else if (currentFilter.value === "sudah_graded") {
    result = result.filter(
      (a) =>
        a.submissions_count > 0 &&
        (a.submissions_graded_count || 0) >= a.submissions_count,
    );
  }

  // Type filter
  if (filterType.value !== "") {
    result = result.filter((a) => a.type === filterType.value);
  }

  // Class filter
  if (filterClass.value !== "") {
    result = result.filter(
      (a) => a.schedule?.school_class?.name === filterClass.value,
    );
  }

  return result;
});

// Hitung jumlah tugas untuk angka di dalam Tab
const getCountByFilter = (filterId) => {
  if (filterId === "belum_graded")
    return assignments.value.filter(
      (a) =>
        a.submissions_count > 0 &&
        (a.submissions_graded_count || 0) < a.submissions_count,
    ).length;
  if (filterId === "sudah_graded")
    return assignments.value.filter(
      (a) =>
        a.submissions_count > 0 &&
        (a.submissions_graded_count || 0) >= a.submissions_count,
    ).length;
  return 0;
};

const fetchGlobalAssignments = async () => {
  isLoading.value = true;
  try {
    const res = await assignmentService.getAllAssignments();
    assignments.value = res.data.data || res.data || [];
  } catch (error) {
    toastStore.error("Gagal memuat daftar tugas global.");
    assignments.value = [];
  } finally {
    isLoading.value = false;
  }
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

watch(
  () => route.name,
  (newName) => {
    if (newName === "TeacherAssignments") {
      fetchGlobalAssignments();
    }
  },
);
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
