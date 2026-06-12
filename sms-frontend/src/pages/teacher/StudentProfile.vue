<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Detail Siswa</h1>
        <p class="text-sm text-gray-500 mt-1">Informasi akademik dan rekapitulasi nilai siswa.</p>
      </div>
      <button
        @click="goBack"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
      </button>
    </div>

    <div v-if="isLoading" class="bg-white rounded-2xl p-12 text-center text-gray-500 shadow-sm border border-gray-200 flex flex-col items-center">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-orange mb-3"></div>
      <p class="font-medium">Memuat data siswa...</p>
    </div>
    
    <template v-else-if="student">
      <section class="bg-white border border-gray-200 rounded-3xl p-6 md:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row gap-6 items-center md:items-start text-center md:text-left">
          
          <div class="relative w-24 h-24 shrink-0">
            <img 
              v-if="student.avatar_url" 
              :src="student.avatar_url" 
              alt="Foto Siswa" 
              class="w-full h-full object-cover rounded-full border-4 border-orange-50 shadow-sm"
            >
            <div v-else class="w-full h-full rounded-full bg-brand-orange text-white flex items-center justify-center text-4xl font-bold border-4 border-orange-50 shadow-sm">
              {{ avatarInitial }}
            </div>
          </div>

          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-800">{{ student.name }}</h2>
            <p class="text-gray-600 mt-1 font-medium">NIS/NISN: <span class="text-gray-800">{{ student.nis || '-' }} / {{ student.nisn || '-' }}</span></p>
            
            <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-4 text-sm">
              <span class="px-3.5 py-1.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-lg font-bold tracking-wide">
                Kelas: {{ currentClassLabel }}
              </span>
              <span class="px-3.5 py-1.5 bg-gray-50 text-gray-700 border border-gray-200 rounded-lg font-semibold">
                {{ genderLabel }}
              </span>
              <span 
                class="px-3.5 py-1.5 rounded-lg font-bold tracking-wide"
                :class="student.is_active ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-brand-red border border-red-100'"
              >
                {{ student.is_active ? 'Status: Aktif' : 'Status: Non-aktif' }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Catatan Akademik & Nilai</h3>
        <p class="text-sm text-gray-500 text-center py-10 bg-gray-50/50 rounded-2xl border-2 border-dashed border-gray-200">
          Detail rekap absensi dan nilai siswa khusus untuk kelas ini akan ditampilkan di sini pada tahap integrasi berikutnya.
        </p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';

// PERBAIKAN: Gunakan service khusus Guru agar tidak diblokir 403!
import { teacherStudentService } from '../../services/modules/teacher/studentService';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();

const isLoading = ref(true);
const student = ref(null); 

const avatarInitial = computed(() => {
  return student.value?.name ? student.value.name.charAt(0).toUpperCase() : 'S';
});

const genderLabel = computed(() => {
  if (student.value?.student?.gender === "L") return "Laki-laki";
  if (student.value?.student?.gender === "P") return "Perempuan";
  return "-";
});

const classHistory = computed(() => {
  return student.value?.student?.classes || [];
});

const currentClassLabel = computed(() => {
  if (classHistory.value.length === 0) return "-";
  const activeClass = classHistory.value.find(c => c.academic_year?.is_active);
  return activeClass ? activeClass.name : classHistory.value[0]?.name;
});

const fetchStudentData = async () => {
  isLoading.value = true;
  try {
    // PERBAIKAN: Memanggil endpoint Guru
    const { data } = await teacherStudentService.getStudentProfile(route.params.id);
    // Data dari controller kita sudah di-unwrap, jadi bisa langsung dimasukkan
    student.value = data.data || data; 
  } catch (error) {
    console.error("Gagal memuat profil siswa:", error);
    toastStore.error("Gagal memuat data detail siswa.");
  } finally {
    isLoading.value = false;
  }
};

const goBack = () => {
  if (window.history.state && window.history.state.back) {
    router.back();
  } else {
    router.push('/teacher/dashboard'); 
  }
};

onMounted(() => {
  fetchStudentData();
});
</script>