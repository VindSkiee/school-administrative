<template>
  <div class="space-y-6 pb-12">
    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center py-20"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-gray-100 border-t-brand-red mb-3"
      ></div>
      <p class="text-gray-500 font-medium">Memuat ruang kelas...</p>
    </div>

    <template v-else-if="classData">

      <section
        class="bg-gradient-to-r from-brand-red to-brand-orange p-6 md:p-8 rounded-3xl text-white shadow-md relative overflow-hidden"
      >
        <svg
          class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10 w-64 h-64"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>

        <div class="relative z-10">
          <div class="mb-4">
            <button
              @click="goBack"
              class="inline-flex items-center gap-2 text-white/70 hover:text-white text-xs font-semibold uppercase tracking-wider transition-colors group"
            >
              <svg
                class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Kembali
            </button>
          </div>

          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <span class="bg-white/20 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide backdrop-blur-sm border border-white/20">
                Ruang Kelas Anda
              </span>
              <h1 class="text-3xl md:text-4xl font-bold font-serif mt-3">
                Kelas {{ classData.name }}
              </h1>
              <p class="text-white/90 text-sm mt-1">
                Tahun Ajaran {{ classData.academic_year }}
              </p>
            </div>

            <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/20 text-center min-w-[120px]">
              <p class="text-4xl font-bold">{{ classData.students.length }}</p>
              <p class="text-xs text-white/80 font-medium uppercase tracking-wider mt-1">
                Total Siswa
              </p>
            </div>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1 space-y-6">
          <div
            class="bg-white rounded-3xl p-6 border border-gray-200 shadow-sm text-center"
          >
            <h3
              class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-5"
            >
              Wali Kelas Anda
            </h3>

            <div class="relative w-24 h-24 mx-auto mb-4">
              <img
                v-if="classData.homeroom_teacher.avatar_url"
                :src="classData.homeroom_teacher.avatar_url"
                alt="Wali Kelas"
                class="w-full h-full object-cover rounded-full border-4 border-red-50 shadow-sm"
              />
              <div
                v-else
                class="w-full h-full bg-red-50 text-brand-red flex items-center justify-center rounded-full font-bold text-3xl"
              >
                {{ classData.homeroom_teacher.name.charAt(0) }}
              </div>
            </div>

            <button
              class="w-full text-center text-lg font-bold text-gray-800 mb-1 line-clamp-2 cursor-default"
            >
              {{ classData.homeroom_teacher.name }}
            </button>
            <p class="text-xs text-gray-500 font-medium">
              NIP. {{ classData.homeroom_teacher.nip }}
            </p>
          </div>
        </div>

        <div class="lg:col-span-3">
          <div
            class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden"
          >
            <div
              class="p-6 border-b border-gray-100 flex items-center justify-between"
            >
              <div>
                <h3 class="text-lg font-bold text-gray-800">
                  Daftar Teman Kelas
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                  Daftar seluruh siswa yang terdaftar di kelas ini.
                </p>
              </div>
            </div>

            <div class="p-4">
              <BaseTable
                :columns="tableHeaders"
                :data="classData.students"
                :isLoading="isLoading"
              >
                <template #cell(student)="{ item }">
                  <div class="flex items-center gap-3 py-1">
                    <img
                      v-if="item.avatar_url"
                      :src="item.avatar_url"
                      :class="[
                        'w-10 h-10 rounded-full object-cover border',
                        item.id === currentUserId
                          ? 'border-green-500 border-2 ring-2 ring-green-100'
                          : 'border-gray-200',
                      ]"
                      alt="Avatar"
                    />

                    <div
                      v-else
                      :class="[
                        'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border',
                        item.id === currentUserId
                          ? 'bg-green-50 text-green-600 border-green-500 ring-2 ring-green-100'
                          : 'bg-gray-100 text-gray-500 border-gray-200',
                      ]"
                    >
                      {{ item.name.charAt(0) }}
                    </div>

                    <div>
                      <div class="flex items-center gap-2">
                        <p class="text-sm font-bold text-gray-800">
                          {{ item.name }}
                        </p>

                        <span
                          v-if="item.id === currentUserId"
                          class="px-2 py-0.5 bg-green-50 text-green-600 text-[10px] font-bold rounded uppercase tracking-wider"
                        >
                          Itu Kamu
                        </span>
                      </div>
                    </div>
                  </div>
                </template>

                <template #cell(gender)="{ item }">
                  <span
                    class="px-2.5 py-1 rounded-md text-xs font-bold"
                    :class="
                      item.gender === 'Laki-laki'
                        ? 'bg-blue-50 text-blue-600'
                        : 'bg-pink-50 text-pink-600'
                    "
                  >
                    {{ item.gender }}
                  </span>
                </template>
              </BaseTable>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { studentClassDetailService } from "../../services/modules/student/classDetailService";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";
import BaseTable from "../../components/BaseTable.vue";

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const classData = ref(null);
const isLoading = ref(true);

const currentUserId = authStore.user?.id;

const tableHeaders = [
  { key: "student", label: "Nama Siswa" },
  { key: "gender", label: "Jenis Kelamin", align: "center" },
];

const goBack = () => {
  router.back();
};

const fetchClassDetail = async () => {
  isLoading.value = true;
  try {
    const response = await studentClassDetailService.getClassDetail();
    classData.value = response.data.data;
  } catch (error) {
    toastStore.error("Gagal memuat detail ruang kelas.");
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchClassDetail();
});
</script>
