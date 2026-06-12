<template>
  <div class="space-y-6">
    <div
      class="bg-gradient-to-r from-brand-red to-brand-orange p-6 md:p-8 rounded-3xl text-white shadow-md relative overflow-hidden"
    >
      <svg
        class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10 w-64 h-64"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      </svg>

      <div
        class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div>
          <button
            @click="goBack"
            class="mb-4 flex items-center text-white/80 hover:text-white transition-colors text-sm font-semibold"
          >
            <svg
              class="w-4 h-4 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              ></path>
            </svg>
            Kembali ke Dashboard
          </button>
          <span
            class="bg-white/20 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide backdrop-blur-sm border border-white/20"
          >
            Kelas Perwalian Anda
          </span>
          <h1 class="text-3xl md:text-4xl font-bold font-serif mt-3">
            Kelas {{ classInfo.name }}
          </h1>
          <p class="text-white/90 text-sm mt-1">
            Tahun Ajaran 2023/2024 • Semester Ganjil
          </p>
        </div>
        <div
          class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/20 text-center min-w-[120px]"
        >
          <p class="text-4xl font-bold">{{ classInfo.total_students }}</p>
          <p
            class="text-xs text-white/80 font-medium uppercase tracking-wider mt-1"
          >
            Total Siswa
          </p>
        </div>
      </div>
    </div>

    <div
      class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 inline-flex w-full md:w-auto overflow-x-auto"
    >
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap"
        :class="
          activeTab === tab.id
            ? 'bg-brand-red text-white shadow-sm'
            : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'
        "
      >
        {{ tab.label }}
      </button>
    </div>

    <div
      class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center"
    >
      <div class="relative w-full md:w-96">
        <div
          class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
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
          placeholder="Cari nama atau NIS siswa..."
          class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
        />
      </div>

      <div class="flex flex-wrap gap-3 w-full md:w-auto">
        <input
          v-if="activeTab === 'attendance'"
          type="date"
          v-model="filterDate"
          class="px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-red/20 outline-none w-full md:w-auto text-gray-700"
        />

        <BaseSelect
          v-if="activeTab === 'grades'"
          v-model="selectedSubject"
          :options="subjectOptions"
          placeholder="Semua Mata Pelajaran"
        />
      </div>
    </div>

    <div
      class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
    >
      <BaseTable
        :columns="currentColumns"
        :data="filteredData"
        :isLoading="isLoading"
        emptyMessage="Tidak ada data siswa yang sesuai dengan filter."
      >
        <template #cell(name)="{ item }">
          <div class="flex items-center h-full font-medium text-gray-800">
            <router-link
              :to="{
                name: 'TeacherStudentProfile',
                params: { id: item.user_id || item.student_id },
              }"
              class="text-brand-red py-2 hover:text-brand-orange font-semibold hover:underline transition-colors"
            >
              {{ item.name }}
            </router-link>
          </div>
        </template>

        <template #cell(gender)="{ item }">
          <span
            :class="
              item.gender === 'L'
                ? 'text-blue-600 bg-blue-50'
                : 'text-pink-600 bg-pink-50'
            "
            class="px-2.5 py-1 rounded-md text-xs font-bold"
          >
            {{ item.gender === "L" ? "Laki-laki" : "Perempuan" }}
          </span>
        </template>

        <template #cell(attendance_rate)="{ item }">
          <div class="flex items-center gap-2">
            <div class="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
              <div
                class="bg-green-500 h-2 rounded-full"
                :style="`width: ${item.attendance_rate}%`"
              ></div>
            </div>
            <span class="text-xs font-semibold text-gray-700"
              >{{ item.attendance_rate }}%</span
            >
          </div>
        </template>
      </BaseTable>
    </div>

    <BaseModal
      :isOpen="isModalOpen"
      :title="`Detail: ${selectedStudent?.name}`"
      maxWidth="md"
      @close="isModalOpen = false"
    >
      <div class="p-2 space-y-4">
        <div class="flex items-center gap-4 border-b border-gray-100 pb-4">
          <div
            class="w-16 h-16 rounded-full bg-brand-red/10 flex items-center justify-center text-brand-red font-bold text-xl"
          >
            {{ selectedStudent?.name?.charAt(0) }}
          </div>
          <div>
            <p class="font-bold text-lg text-gray-800">
              {{ selectedStudent?.name }}
            </p>
            <p class="text-sm text-gray-500">
              NIS/NISN: {{ selectedStudent?.nis }}
            </p>
          </div>
        </div>
        <p class="text-sm text-gray-600">
          Fitur detail siswa secara mendalam (Grafik nilai individu, riwayat
          pelanggaran, dll) dapat ditambahkan di sini.
        </p>
        <div class="pt-4 text-right">
          <button
            @click="isModalOpen = false"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import BaseTable from "../../components/BaseTable.vue";
import BaseSelect from "../../components/BaseSelect.vue";
import BaseModal from "../../components/BaseModal.vue";
import { homeroomService } from "../../services/modules/teacher/homeroomService";
import { useToastStore } from "../../stores/toast";

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

// State Dasar
const isLoading = ref(true);
const activeTab = ref("students");
const searchQuery = ref("");
const isModalOpen = ref(false);
const selectedStudent = ref(null);

// Konfigurasi Filter Select
const filterDate = ref(""); // Filter Tanggal Baru
const selectedSubject = ref("all");

const tabs = [
  { id: "students", label: "Daftar Siswa" },
  { id: "attendance", label: "Rekap Kehadiran" },
];

// Konfigurasi Kolom BaseTable (Dinamis sesuai Tab)
const currentColumns = computed(() => {
  if (activeTab.value === "students") {
    return [
      { key: "nis", label: "NIS" },
      { key: "name", label: "Nama Lengkap" }, // Akan di-override slot
      { key: "gender", label: "L/P", align: "center" },
      { key: "status", label: "Status", align: "center" },
      // Kolom 'actions' dihapus karena nama sudah bisa diklik
    ];
  } else {
    return [
      { key: "name", label: "Nama Lengkap" },
      { key: "present", label: "Hadir" },
      { key: "sick", label: "Sakit" },
      { key: "permission", label: "Izin" },
      { key: "alpa", label: "Alpa" },
      { key: "attendance_rate", label: "Persentase" },
    ];
  }
});

// State Data (Nanti diganti response API)
const classInfo = ref({});
const rawData = ref([]);

const fetchClassDetail = async (dateFilter = null) => {
  isLoading.value = true;

  try {
    // Kirim parameter tanggal ke Backend (Opsional, tergantung kesiapan Backend)
    const params = {};
    if (dateFilter) params.date = dateFilter;

    // TODO: Sesuaikan homeroomService Anda agar bisa menerima parameter params
    const { data } = await homeroomService.getHomeroomDetail({ params });

    classInfo.value = data.class_info;
    rawData.value = data.students;
  } catch (error) {
    console.error("Error fetching homeroom detail:", error);
    toastStore.error(
      error.response?.data?.error || "Gagal memuat detail kelas perwalian.",
    );
  } finally {
    isLoading.value = false;
  }
};

// Logika Pencarian & Filter (Dijalankan di Frontend untuk UX super cepat)
const filteredData = computed(() => {
  let result = rawData.value;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(
      (s) => s.name.toLowerCase().includes(q) || s.nis.includes(q),
    );
  }

  return result;
});

// Watcher: Saat guru mengubah tanggal, panggil API lagi dengan parameter tanggal
watch(filterDate, (newDate) => {
  if (activeTab.value === "attendance") {
    fetchClassDetail(newDate);
  }
});

// Helper Fungsi
const getScoreColor = (score) => {
  if (score >= 90) return "bg-green-50 text-green-700 border-green-200";
  if (score >= 75) return "bg-blue-50 text-blue-700 border-blue-200";
  return "bg-red-50 text-red-700 border-red-200";
};

const goBack = () => router.push("/teacher/dashboard");

const openStudentDetail = (student) => {
  selectedStudent.value = student;
  isModalOpen.value = true;
};

onMounted(() => {
  fetchClassDetail();
});
</script>
