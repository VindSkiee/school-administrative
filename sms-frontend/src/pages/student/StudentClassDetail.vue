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
        class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6"
      >
        <svg
          class="absolute top-0 right-0 translate-x-1/4 -translate-y-1/4 opacity-10 w-64 h-64"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path d="M12 14l9-5-9-5-9 5 9 5z" />
          <path
            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"
          />
        </svg>

        <div class="relative z-10 flex items-center gap-5 w-full">
            <button
      @click="$router.push('/student/dashboard')"
      class="w-12 h-12 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 flex items-center justify-center transition-all shrink-0"
    >
      <svg
        class="w-6 h-6 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 19l-7-7 7-7"
        />
      </svg>
    </button>
          <div
            class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30 shrink-0"
          >
            <svg
              class="w-8 h-8 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              ></path>
            </svg>
          </div>
          <div>
            <span
              class="inline-block px-2.5 py-1 bg-white/20 rounded-lg text-xs font-bold tracking-wide uppercase mb-1"
              >Rincian Ruang Kelas</span
            >
            <h2 class="text-2xl md:text-3xl font-bold font-serif">
              Kelas {{ classData.name }}
            </h2>
            <p class="mt-1 text-red-100 text-sm font-medium">
              Tahun Ajaran: {{ classData.academic_year }}
            </p>
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
              @click="goToTeacherProfile(classData.homeroom_teacher.id)"
              class="w-full text-center text-lg font-bold text-gray-800 hover:text-brand-red transition-colors mb-1 line-clamp-2"
            >
              {{ classData.homeroom_teacher.name }}
            </button>
            <p class="text-xs text-gray-500 font-medium">
              NIP. {{ classData.homeroom_teacher.nip }}
            </p>

            <button
              @click="goToTeacherProfile(classData.homeroom_teacher.id)"
              class="mt-5 w-full py-2 bg-gray-50 hover:bg-red-50 text-brand-red border border-gray-200 hover:border-red-200 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
            >
              Lihat Profil Guru
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
                  d="M14 5l7 7m0 0l-7 7m7-7H3"
                ></path>
              </svg>
            </button>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-2">
              <svg
                class="w-5 h-5 text-blue-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
              <h4 class="font-bold text-blue-900">Total Siswa</h4>
            </div>
            <p class="text-3xl font-black text-blue-800">
              {{ classData.students.length }}
              <span class="text-sm font-medium text-blue-600">Siswa Aktif</span>
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

const goToTeacherProfile = (teacherUserId) => {
  if (!teacherUserId) return;
  toastStore.info("Navigasi ke profil guru belum diimplementasikan.");
};

onMounted(() => {
  fetchClassDetail();
});
</script>
