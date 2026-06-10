<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Detail Siswa</h1>
        <p class="text-sm text-gray-500 mt-1">Informasi akademik dan rekapitulasi nilai siswa.</p>
      </div>
      <button
        @click="goBack"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors"
      >
        Kembali
      </button>
    </div>

    <div v-if="isLoading" class="bg-white rounded-xl p-8 text-center text-gray-500 shadow-sm border border-gray-200">
      Memuat data siswa...
    </div>
    
    <template v-else-if="student">
      <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
          <div class="w-24 h-24 rounded-full bg-brand-orange text-white flex items-center justify-center text-4xl font-bold shrink-0">
            {{ student.user?.name?.charAt(0).toUpperCase() || 'S' }}
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ student.user?.name }}</h2>
            <p class="text-gray-600 mt-1">NIS/NISN: {{ student.nis || '-' }} / {{ student.nisn || '-' }}</p>
            <div class="flex gap-2 mt-3 text-sm">
              <span class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-semibold">
                Kelas: {{ student.school_class?.name || '-' }}
              </span>
              <span class="px-3 py-1 bg-gray-50 text-gray-700 border border-gray-200 rounded-lg">
                {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Catatan Akademik</h3>
        <p class="text-sm text-gray-500 text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
          Detail absensi dan nilai siswa ini dapat diintegrasikan di sini nantinya.
        </p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// Nanti kita buatkan service khusus untuk guru menarik data siswa
// import { teacherStudentService } from '../../services/modules/teacher/studentService';

const route = useRoute();
const router = useRouter();
const isLoading = ref(true);
const student = ref(null);

const fetchStudentData = async () => {
  isLoading.value = true;
  try {
    // TODO: Panggil API Backend
    // const { data } = await teacherStudentService.getStudentProfile(route.params.id);
    // student.value = data;

    // --- MOCK DATA SEMENTARA ---
    setTimeout(() => {
      student.value = {
        id: route.params.id,
        nis: '2023001',
        nisn: '0051234567',
        gender: 'L',
        user: { name: 'Ahmad Faisal' },
        school_class: { name: 'XII IPA 1' }
      };
      isLoading.value = false;
    }, 500);

  } catch (error) {
    console.error("Gagal memuat profil siswa");
    isLoading.value = false;
  }
};

const goBack = () => {
  router.back();
};

onMounted(() => {
  fetchStudentData();
});
</script>