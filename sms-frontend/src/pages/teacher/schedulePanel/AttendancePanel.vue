<template>
  <div class="space-y-6">
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <div
        class="animate-spin rounded-full h-10 w-10 border-b-2 border-brand-red"
      ></div>
    </div>

    <template v-else>
      <div
        v-if="pendingRequests.length > 0"
        class="bg-orange-50 border border-orange-200 rounded-2xl p-4 shadow-sm"
      >
        <h3 class="text-orange-800 font-bold flex items-center gap-2 mb-3">
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
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            ></path>
          </svg>
          Persetujuan Izin/Sakit ({{ pendingRequests.length }})
        </h3>
        <div class="space-y-3">
          <div
            v-for="req in pendingRequests"
            :key="req.id"
            class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-3.5 rounded-xl border border-orange-100 gap-3"
          >
            <div>
              <p class="font-bold text-gray-800 text-lg">
                {{ req.student?.user?.name || 'Siswa' }}
              </p>
              <div class="flex items-center gap-2 mt-1 text-sm text-gray-600">
                <span>Mengajukan status:
                  <span class="font-bold uppercase text-orange-600">
                    {{ req.type === 'sick' ? 'Sakit' : 'Izin' }}
                  </span>
                </span>
                <span>•</span>
                
                <button
                  v-if="req.proof_file_path"
                  @click="previewAttachment(req.proof_file_path)"
                  type="button"
                  class="text-blue-600 hover:text-blue-800 font-semibold hover:underline flex items-center gap-1 focus:outline-none"
                >
                  Lihat Bukti
                </button>
                <span v-else class="text-gray-400 italic">
                  Tidak ada lampiran
                </span>
              </div>
              <p v-if="req.reason" class="text-xs text-gray-500 mt-1 italic border-l-2 border-gray-200 pl-2">
                "{{ req.reason }}"
              </p>
            </div>

            <div class="flex gap-2 w-full md:w-auto mt-2 md:mt-0">
              <button
                @click="handleReviewRequest(req.id, req.student_id, 'approved', req.type)"
                class="flex-1 md:flex-none px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-bold rounded-lg transition-colors shadow-sm"
              >
                Setujui
              </button>
              
              <button
                @click="handleReviewRequest(req.id, req.student_id, 'rejected')"
                class="flex-1 md:flex-none px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-sm font-bold rounded-lg transition-colors"
              >
                Tolak
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-200"
      >
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <button
            @click="markAllAs('present')"
            :disabled="isAlreadySubmitted"
            class="flex-1 sm:flex-none px-4 py-2.5 bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 rounded-xl text-sm font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Hadir Semua
          </button>
          <button
            @click="resetAttendance"
            :disabled="isAlreadySubmitted"
            class="px-4 py-2.5 bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Reset
          </button>
        </div>

        <button
          @click="submitAttendance"
          :disabled="isSubmitting || isAlreadySubmitted"
          :class="
            isAlreadySubmitted
              ? 'bg-gray-400 text-white'
              : 'bg-brand-red hover:bg-brand-orange text-white shadow-sm'
          "
          class="px-8 py-2.5 rounded-xl text-sm font-bold transition-colors w-full sm:w-auto"
        >
          <span v-if="isAlreadySubmitted">🔒 Absensi Terkunci (Selesai)</span>
          <span v-else>{{
            isSubmitting ? "Menyimpan..." : "Simpan Absensi"
          }}</span>
        </button>
      </div>

      <div
        class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
      >
        <BaseTable
          :columns="tableColumns"
          :data="students"
          :isLoading="isLoading"
          emptyMessage="Tidak ada data siswa di kelas ini."
        >
          <template #cell(name)="{ item }">
            <div class="flex items-center h-full font-medium text-gray-800">
              <router-link
                :to="{ name: 'TeacherStudentProfile', params: { id: String(item.user_id || item.user?.id || item.id) } }"
                class="text-brand-red py-2 hover:text-brand-orange font-semibold hover:underline transition-colors"
              >
                {{ item.user?.name || "Tanpa Nama" }}
              </router-link>
            </div>
          </template>

          <template #cell(status)="{ item }">
            <div class="flex items-center gap-1.5 md:gap-2 justify-center">
              <button
                @click="setAttendance(item.user?.id || item.id, 'present')"
                :disabled="isAlreadySubmitted"
                :class="
                  getBtnClass(
                    item.user?.id || item.id,
                    'present',
                    'bg-green-500 text-white shadow-sm',
                    'bg-gray-50 text-gray-500 hover:bg-gray-200 border border-gray-200',
                  )
                "
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all w-9 md:w-auto disabled:cursor-not-allowed"
              >
                <span class="hidden md:inline">Hadir</span
                ><span class="md:hidden">H</span>
              </button>

              <button
                @click="setAttendance(item.user?.id || item.id, 'sick')"
                :disabled="isAlreadySubmitted"
                :class="
                  getBtnClass(
                    item.user?.id || item.id,
                    'sick',
                    'bg-blue-500 text-white shadow-sm',
                    'bg-gray-50 text-gray-500 hover:bg-gray-200 border border-gray-200',
                  )
                "
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all w-9 md:w-auto disabled:cursor-not-allowed"
              >
                <span class="hidden md:inline">Sakit</span
                ><span class="md:hidden">S</span>
              </button>

              <button
                @click="setAttendance(item.user?.id || item.id, 'permission')"
                :disabled="isAlreadySubmitted"
                :class="
                  getBtnClass(
                    item.user?.id || item.id,
                    'permission',
                    'bg-orange-500 text-white shadow-sm',
                    'bg-gray-50 text-gray-500 hover:bg-gray-200 border border-gray-200',
                  )
                "
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all w-9 md:w-auto disabled:cursor-not-allowed"
              >
                <span class="hidden md:inline">Izin</span
                ><span class="md:hidden">I</span>
              </button>

              <button
                @click="setAttendance(item.user?.id || item.id, 'absent')"
                :disabled="isAlreadySubmitted"
                :class="
                  getBtnClass(
                    item.user?.id || item.id,
                    'absent',
                    'bg-red-500 text-white shadow-sm',
                    'bg-gray-50 text-gray-500 hover:bg-gray-200 border border-gray-200',
                  )
                "
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all w-9 md:w-auto disabled:cursor-not-allowed"
              >
                <span class="hidden md:inline">Alpa</span
                ><span class="md:hidden">A</span>
              </button>

              <button
                @click="setAttendance(item.user?.id || item.id, 'late')"
                :disabled="isAlreadySubmitted"
                :class="
                  getBtnClass(
                    item.user?.id || item.id,
                    'late',
                    'bg-purple-500 text-white shadow-sm',
                    'bg-gray-50 text-gray-500 hover:bg-gray-200 border border-gray-200',
                  )
                "
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all w-9 md:w-auto disabled:cursor-not-allowed"
              >
                <span class="hidden md:inline">Telat</span
                ><span class="md:hidden">T</span>
              </button>
            </div>
          </template>
        </BaseTable>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onActivated } from "vue";
import BaseTable from "../../../components/BaseTable.vue";
import { useToastStore } from "../../../stores/toast";
import { attendanceService } from "../../../services/modules/teacher/attendanceService";
import { useAttendanceDetailStore } from "../../../stores/attendanceDetail";

const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true },
  locked: { type: Boolean, default: false },
});

const toastStore = useToastStore();
const attendanceDetailStore = useAttendanceDetailStore();

const _isAlreadySubmitted = ref(false);
const isAlreadySubmitted = computed(() => {
  if (props.locked) return true;
  return _isAlreadySubmitted.value;
});
const isSubmitting = ref(false);
const isLoading = ref(false); 

const students = ref([]);
const pendingRequests = ref([]);
const attendanceForm = ref({});

const tableColumns = [
  { key: "nis", label: "NIS / NISN" },
  { key: "name", label: "Nama Lengkap Siswa" },
  { key: "status", label: "Aksi Kehadiran", align: "center" },
];

// FUNGSI BARU: Memetakan data dari Store ke UI
const mapStoreDataToUI = () => {
  students.value = attendanceDetailStore.students;
  pendingRequests.value = attendanceDetailStore.pendingRequests;
  const existingData = attendanceDetailStore.existingAttendances;

  _isAlreadySubmitted.value = false;
  attendanceForm.value = {};

  if (existingData && existingData.length > 0) {
    _isAlreadySubmitted.value = true;
    existingData.forEach((record) => {
      const student = students.value.find(
        (s) =>
          String(s.id) === String(record.student_id) ||
          String(s.user_id) === String(record.student_id) ||
          String(s.user?.id) === String(record.student_id),
      );

      if (student) {
        const uniqueKey = student.user?.id || student.id || student.nisn;
        attendanceForm.value[uniqueKey] = record.status;
      }
    });
  }
};

// Fetching: Jika data sudah di-store (prefetched oleh AttendanceSchedule), langsung map.
const fetchPanelData = async () => {
  if (attendanceDetailStore.isDataLoaded(String(props.scheduleId))) {
    mapStoreDataToUI();
    return;
  }
  isLoading.value = true;
  try {
    await attendanceDetailStore.prefetchAllData(props.scheduleId, props.selectedDate);
    mapStoreDataToUI();
  } catch (error) {
    console.error("Fetch Data Error:", error);
    toastStore.error("Gagal memuat ulang data absensi kelas.");
  } finally {
    isLoading.value = false;
  }
};

const setAttendance = (studentId, status) => {
  attendanceForm.value[studentId] = status;
};

const markAllAs = (status) => {
  students.value.forEach((student) => {
    const uniqueId = student.user?.id || student.id;
    attendanceForm.value[uniqueId] = status;
  });
};

const resetAttendance = () => {
  attendanceForm.value = {};
};

const getBtnClass = (studentId, statusToCheck, activeClass, inactiveClass) => {
  const currentStatus = attendanceForm.value[studentId];

  if (currentStatus === statusToCheck) {
    if (isAlreadySubmitted.value) {
      if (statusToCheck === "present") return "bg-green-500 text-white opacity-60 shadow-none";
      if (statusToCheck === "sick") return "bg-blue-500 text-white opacity-60 shadow-none";
      if (statusToCheck === "permission") return "bg-orange-500 text-white opacity-60 shadow-none";
      if (statusToCheck === "absent") return "bg-red-500 text-white opacity-60 shadow-none";
      if (statusToCheck === "late") return "bg-purple-500 text-white opacity-60 shadow-none";
    }
    return activeClass;
  }

  return isAlreadySubmitted.value
    ? "bg-gray-50 text-gray-400 border border-gray-100 opacity-50"
    : inactiveClass;
};

const submitAttendance = async () => {
  const missingAttendance = students.value.filter((s) => {
    const uniqueKey = s.user?.id || s.id || s.nisn;
    return !attendanceForm.value[uniqueKey];
  });

  if (missingAttendance.length > 0) {
    toastStore.error(`Gagal! Masih ada ${missingAttendance.length} siswa yang belum diabsen.`);
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      schedule_id: props.scheduleId,
      date: props.selectedDate,
      attendances: students.value.map((s) => {
        const uniqueKey = s.user?.id || s.id || s.nisn;
        const theStudentId = s.id || s.user_id || s.user?.id;

        return {
          student_id: theStudentId,
          status: attendanceForm.value[uniqueKey],
        };
      }),
    };

    await attendanceService.storeBulk(payload);

    toastStore.success("Mantap! Data absensi berhasil disimpan.");
    _isAlreadySubmitted.value = true;
  } catch (error) {
    const errorMessage = error.response?.data?.message || "Terjadi kesalahan saat menyimpan absensi.";
    toastStore.error(errorMessage);
  } finally {
    isSubmitting.value = false;
  }
};

// Tambahkan parameter ke-4 (reqType) untuk menyimpan status 'sick'/'permission' secara lokal
const handleReviewRequest = async (requestId, studentId, actionStatus, reqType = null) => {
  try {
    // actionStatus berisi 'approved' atau 'rejected' (Dikirim ke Backend)
    await attendanceService.reviewRequest(requestId, actionStatus); 
    
    toastStore.success(`Pengajuan berhasil ${actionStatus === "rejected" ? "ditolak" : "disetujui"}.`);

    // Hapus card pengajuan dari daftar
    pendingRequests.value = pendingRequests.value.filter((r) => r.id !== requestId);

    // Jika disetujui, update UI absen guru dengan reqType ('sick' atau 'permission')
    if (actionStatus === "approved" && reqType) {
      attendanceForm.value[studentId] = reqType;
    }
  } catch (error) {
    toastStore.error("Gagal memproses pengajuan izin siswa.");
  }
};

// Fungsi untuk membuka lampiran surat Izin/Sakit dari siswa
const previewAttachment = (filePath) => {
  if (!filePath) {
    toastStore.error("Siswa tidak menyertakan file lampiran.");
    return;
  }
  // Sesuaikan dengan URL backend Laravel Anda
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';
  window.open(`${baseUrl}/storage/${filePath}`, '_blank');
};

// Jika user ganti tanggal lewat dropdown/kalender di halaman Detail, kita harus memuat data lagi
watch(
  () => props.selectedDate,
  () => {
    // Karena URL Date berubah, panggil fetchPanelData untuk tarik ke Store lalu petakan ulang
    fetchPanelData();
  },
);

// FIX: Watch scheduleId prop — re-fetch saat schedule berubah (A → B)
// Ini menangani keep-alive scenario: AttendanceSchedule → Schedule A → Back → Schedule B
watch(
  () => props.scheduleId,
  (newId, oldId) => {
    if (newId && String(newId) !== String(oldId)) {
      fetchPanelData();
    }
  },
);

// onMounted: initial load (F5 refresh atau pertama kali)
onMounted(() => {
  if (attendanceDetailStore.isDataLoaded(String(props.scheduleId))) {
    mapStoreDataToUI();
  } else {
    fetchPanelData();
  }
});

// FIX: onActivated: skip fetch jika data sudah di-load oleh AttendanceSchedule
onActivated(() => {
  if (attendanceDetailStore.isDataLoaded(String(props.scheduleId))) {
    mapStoreDataToUI();
  } else {
    fetchPanelData();
  }
});
</script>
