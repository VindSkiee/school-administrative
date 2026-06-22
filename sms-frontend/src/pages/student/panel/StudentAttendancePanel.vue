<template>
  <div class="space-y-6">
    
    <div v-if="isLoading" class="py-12 flex flex-col items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-red border-t-transparent mb-3"></div>
      <p class="text-gray-500 text-sm font-medium">Memeriksa status kehadiran...</p>
    </div>

    <template v-else>
      <div v-if="currentRequest" class="bg-gray-50 border border-gray-200 rounded-2xl p-6 md:p-8 text-center max-w-2xl mx-auto shadow-sm">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
             :class="{
               'bg-orange-100 text-brand-orange': currentRequest.status === 'pending',
               'bg-green-100 text-green-600': currentRequest.status === 'approved',
               'bg-red-100 text-brand-red': currentRequest.status === 'rejected'
             }">
          <svg v-if="currentRequest.status === 'pending'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <svg v-else-if="currentRequest.status === 'approved'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>

        <h3 class="text-xl font-bold text-gray-800 mb-2">
          {{ currentRequest.status === 'pending' ? 'Pengajuan Sedang Diproses' : 
             currentRequest.status === 'approved' ? 'Pengajuan Disetujui' : 'Pengajuan Ditolak' }}
        </h3>
        <p class="text-gray-600 font-medium mb-4">
          Anda telah mengajukan <span class="font-bold uppercase text-brand-red">{{ currentRequest.type === 'sick' ? 'Sakit' : 'Izin' }}</span> untuk pertemuan ini.
        </p>

        <div class="bg-white p-4 rounded-xl border border-gray-100 text-left text-sm text-gray-600 shadow-sm inline-block w-full max-w-md mx-auto">
          <p><strong class="text-gray-800">Alasan:</strong> {{ currentRequest.reason }}</p>
          <p class="mt-2"><strong class="text-gray-800">Bukti:</strong> Terlampir ({{ currentRequest.proof_file_path ? 'Valid' : '-' }})</p>
        </div>
      </div>

      <div v-else-if="isReportPublished" class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center max-w-2xl mx-auto shadow-sm">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-amber-800 mb-2">Rapor Sudah Diterbitkan</h3>
        <p class="text-amber-600 font-medium max-w-md mx-auto">
          Semester ini telah selesai. Pengajuan izin atau sakit tidak lagi tersedia karena rapor sudah diterbitkan.
        </p>
      </div>

      <div v-else-if="isTimeLocked" class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center max-w-2xl mx-auto shadow-sm">
        <div class="w-16 h-16 bg-red-100 text-brand-red rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-red-800 mb-2">
          {{ isToday ? 'Waktu Pengajuan Habis' : 'Form Terkunci' }}
        </h3>
        <p class="text-red-600 font-medium max-w-md mx-auto">
          {{ isToday
            ? `Jam pelajaran untuk sesi ini telah berakhir pada pukul ${formattedEndTime}. Anda sudah tidak dapat mengajukan izin atau sakit.`
            : 'Form absensi hanya dapat diakses pada hari H jadwal pelajaran ini.'
          }}
        </p>
        <p class="text-xs text-red-500 mt-4">Hubungi guru yang bersangkutan jika terjadi kesalahan.</p>
      </div>

      <div v-else class="max-w-3xl mx-auto">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex gap-3 text-blue-800 items-start">
          <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <p class="text-sm font-medium">Jika Anda hadir di kelas, Anda <strong>tidak perlu</strong> mengisi form ini. Presensi kehadiran akan dipanggil/diabsen langsung oleh Guru. Form ini khusus untuk pengajuan <strong class="text-blue-900">Izin</strong> atau <strong class="text-blue-900">Sakit</strong>.</p>
        </div>

        <form @submit.prevent="submitRequest" class="bg-white border border-gray-200 shadow-sm p-6 sm:p-8 rounded-2xl space-y-6 relative">
          
          <h3 class="text-lg font-bold text-gray-800 border-b pb-3 flex justify-between items-center">
            Formulir Ketidakhadiran
            <span v-if="!isTimeLocked && isToday" class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">
              Batas: {{ formattedEndTime }}
            </span>
          </h3>
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-3">Tipe Pengajuan <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="form.type" value="sick" class="peer sr-only" required>
                <div class="p-4 border-2 border-gray-200 rounded-xl text-center font-bold text-gray-500 peer-checked:border-brand-red peer-checked:text-brand-red peer-checked:bg-red-50 transition-all">
                  🤒 Sakit
                </div>
              </label>
              <label class="flex-1 cursor-pointer">
                <input type="radio" v-model="form.type" value="permission" class="peer sr-only" required>
                <div class="p-4 border-2 border-gray-200 rounded-xl text-center font-bold text-gray-500 peer-checked:border-brand-orange peer-checked:text-brand-orange peer-checked:bg-orange-50 transition-all">
                  ✈️ Izin
                </div>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Alasan Detail <span class="text-red-500">*</span></label>
            <textarea v-model="form.reason" required rows="3" placeholder="Tuliskan alasan lengkap Anda di sini..." class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-red/20 outline-none text-sm transition-all"></textarea>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Upload Bukti Surat (Maks 2MB) <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-500 mb-2">Format yang didukung: JPG, JPEG, PNG, PDF.</p>
            
            <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition-colors" :class="{'border-brand-red bg-red-50': file}">
              <input type="file" @change="handleFileUpload" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
              
              <div v-if="!file" class="pointer-events-none">
                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                <p class="text-sm text-gray-600 font-medium"><span class="text-brand-red font-bold">Pilih File</span> atau seret ke sini</p>
              </div>
              <div v-else class="pointer-events-none text-brand-red font-bold flex items-center justify-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ file.name }}
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit" :disabled="isSubmitting" class="w-full sm:w-auto px-8 py-3 bg-brand-red hover:bg-brand-orange text-white rounded-xl text-sm font-bold shadow-md transition-colors disabled:opacity-50">
              {{ isSubmitting ? 'Mengirim Data...' : 'Kirim Pengajuan' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { studentScheduleService } from '../../../services/modules/student/scheduleService';
import { useStudentScheduleDetailStore } from '../../../stores/studentScheduleDetail';
import { useToastStore } from '../../../stores/toast';
import { useReportStatus } from '../../../composables/useReportStatus';

const props = defineProps({
  scheduleId: { type: [String, Number], required: true },
  selectedDate: { type: String, required: true } // Tanggal dari URL YYYY-MM-DD
});

const toastStore = useToastStore();
const { isReportPublished } = useReportStatus('student');
// Akses Pinia Store untuk mendapatkan data jadwal aslinya (termasuk start_time dan end_time)
const studentDetailStore = useStudentScheduleDetailStore();

const isLoading = ref(true);
const isSubmitting = ref(false);

const allRequests = ref([]);
const file = ref(null);

const form = ref({
  type: '',
  reason: ''
});

// Mengecek apakah di tanggal dan jadwal ini student sudah punya request
const currentRequest = computed(() => {
  return allRequests.value.find(r => 
    String(r.schedule_id) === String(props.scheduleId) && 
    r.date === props.selectedDate
  );
});

// ================= LOGIKA LOCKING (PENGUNCIAN WAKTU) =================
const getLocalTodayString = () => {
  const today = new Date();
  const y = today.getFullYear();
  const m = String(today.getMonth() + 1).padStart(2, '0');
  const d = String(today.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

const isToday = computed(() => props.selectedDate === getLocalTodayString());

// Ambil jam selesainya kelas dari Pinia Store (format HH:mm:ss atau HH:mm)
const formattedEndTime = computed(() => {
  const endTime = studentDetailStore.scheduleInfo?.end_time;
  return endTime ? endTime.substring(0, 5) : '...';
});

// Mengecek apakah form harus dikunci?
const isTimeLocked = computed(() => {
  // Kunci jika BUKAN hari ini (baik masa lalu maupun masa depan)
  if (!isToday.value) return true;
  
  // Jika hari ini, cek apakah jam pelajaran sudah selesai
  const endTime = formattedEndTime.value;
  if (endTime === '...') return false; // Tunggu data API beres
  
  // Dapatkan jam saat ini dalam format HH:mm
  const now = new Date();
  const currentH = String(now.getHours()).padStart(2, '0');
  const currentM = String(now.getMinutes()).padStart(2, '0');
  const currentTime = `${currentH}:${currentM}`;

  // Terkunci jika waktu saat ini MELEBIHI jam pelajaran selesai
  return currentTime > endTime;
});
// ===================================================================

const handleFileUpload = (e) => {
  const selectedFile = e.target.files[0];
  if (!selectedFile) return;

  if (selectedFile.size > 2 * 1024 * 1024) {
    toastStore.error("Ukuran file maksimal adalah 2MB.");
    e.target.value = ''; // Reset input
    file.value = null;
    return;
  }
  file.value = selectedFile;
};

const fetchRequests = async () => {
  isLoading.value = true;
  try {
    const res = await studentScheduleService.getAttendanceRequests();
    allRequests.value = res.data.data || [];
  } catch (error) {
    console.error("Gagal memuat status pengajuan:", error);
  } finally {
    isLoading.value = false;
  }
};

const submitRequest = async () => {
  if (isTimeLocked.value) {
    toastStore.error("Waktu pengajuan izin untuk sesi ini telah ditutup.");
    return;
  }

  if (!file.value) {
    toastStore.error("Mohon lampirkan surat bukti izin/sakit.");
    return;
  }

  isSubmitting.value = true;

  try {
    const formData = new FormData();
    formData.append('schedule_id', props.scheduleId);
    formData.append('date', props.selectedDate);
    
    // PERBAIKAN DI SINI: Tambahkan .value
    formData.append('type', form.value.type); 
    formData.append('reason', form.value.reason); 
    
    formData.append('proof_file', file.value);

    await studentScheduleService.submitAttendanceRequest(formData);
    
    toastStore.success("Pengajuan ketidakhadiran berhasil dikirim ke Guru.");
    await fetchRequests();
  } catch (error) {
    const errorMsg = error.response?.data?.error || error.response?.data?.message || "Gagal mengirim pengajuan. Coba lagi.";
    toastStore.error(errorMsg);
  } finally {
    isSubmitting.value = false;
  }
};


onMounted(() => {
  fetchRequests();
});
</script>