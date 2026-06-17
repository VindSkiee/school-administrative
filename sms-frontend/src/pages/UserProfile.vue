<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-[23px] lg:text-3xl font-bold text-gray-800 font-serif">
          {{ pageTitle }}
        </h1>
        <p class="text-xs lg:text-sm text-gray-500 mt-1">
          {{ pageDescription }}
        </p>
      </div>
      <button
        @click="goBack"
        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-semibold transition-colors hidden sm:inline-flex"
      >
        Kembali
      </button>
    </div>

    <div
      v-if="isLoading"
      class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 shadow-sm"
    >
      Memuat data profil pengguna...
    </div>

    <div
      v-else-if="!user"
      class="bg-white border border-red-200 rounded-xl p-8 text-center text-brand-red shadow-sm"
    >
      Data pengguna tidak ditemukan.
    </div>

    <template v-else>
      <section
        class="bg-white border border-gray-200 rounded-2xl p-4 sm:p-6 shadow-sm"
      >
        <div
          class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5"
        >
          <div
            class="flex flex-col sm:flex-row items-center sm:items-center gap-4 text-center sm:text-left"
          >
            <!-- Avatar -->
            <div
              class="relative group cursor-pointer inline-block"
              @click="triggerFileInput"
            >
              <img
                v-if="user.avatar_url || previewImageUrl"
                :src="previewImageUrl || user.avatar_url"
                alt="Avatar"
                class="w-20 h-20 sm:w-28 sm:h-28 lg:w-36 lg:h-36 rounded-full object-cover border-4 border-white shadow-sm"
              />

              <div
                v-else
                class="w-20 h-20 sm:w-28 sm:h-28 lg:w-36 lg:h-36 rounded-full bg-brand-orange text-brand-white flex items-center justify-center text-xl sm:text-2xl lg:text-5xl font-bold border-4 border-white shadow-sm"
              >
                {{ avatarInitial }}
              </div>

              <!-- overlay upload -->
              <div
                class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                :class="{ 'opacity-100': isUploading }"
              >
                <!-- svg upload -->
                <svg
                  v-if="isUploading"
                  class="w-8 h-8 text-white animate-spin"
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
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="w-8 h-8 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                  ></path>
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                  ></path>
                </svg>
              </div>

              <!-- edit icon -->
              <div
                class="absolute bottom-0 right-3 bg-white p-1 sm:p-1.5 rounded-full shadow-md border border-gray-100 text-gray-500 group-hover:text-brand-orange group-hover:border-brand-orange transition-colors z-10"
                v-show="!isUploading"
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
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                  ></path>
                </svg>
              </div>

              <input
                type="file"
                ref="fileInput"
                class="hidden"
                accept="image/jpeg, image/png, image/jpg"
                @change="handleFileUpload"
              />
            </div>

            <!-- User Info -->
            <div class="space-y-2">
              <div
                class="flex flex-col sm:flex-row sm:flex-wrap items-center gap-2"
              >
                <h2
                  class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800"
                >
                  {{ user.name }}
                </h2>

                <span
                  :class="roleBadgeClass"
                  class="px-2 py-1 sm:px-3 rounded-full text-[10px] sm:text-xs font-semibold border uppercase tracking-wide"
                >
                  {{ roleLabel }}
                </span>
              </div>

              <p class="text-xs sm:text-sm text-gray-600 break-all">
                {{ user.email }}
              </p>

              <div
                class="flex flex-wrap justify-center sm:justify-start gap-2 text-[10px] sm:text-xs mt-2"
              >
                <span
                  class="px-2 py-1 sm:px-2.5 rounded-md border border-gray-200 bg-gray-50 text-gray-700"
                >
                  {{ identityLabel }}: {{ identityValue }}
                </span>

                <span
                  class="px-2 py-1 sm:px-2.5 rounded-md border border-gray-200 bg-gray-50 text-gray-700"
                >
                  Status Akun:
                  {{ user.is_active ? "Aktif" : "Nonaktif" }}
                </span>

                <span
                  v-if="user.must_change_password"
                  class="px-2 py-1 sm:px-2.5 rounded-md border border-amber-200 bg-amber-50 text-amber-700"
                >
                  Wajib ganti password
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section
        class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"
      >
        <div class="border-b border-gray-200 px-4 py-3 overflow-x-auto">
          <div class="inline-flex bg-gray-100 rounded-lg p-1 gap-1 min-w-max">
            <button
              v-for="tab in visibleTabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              class="px-4 py-2 text-sm font-semibold rounded-md transition-colors"
              :class="
                activeTab === tab.key
                  ? 'bg-white text-brand-red shadow-sm'
                  : 'text-gray-600 hover:text-gray-800'
              "
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <div class="p-5 sm:p-6">
          <div v-if="activeTab === 'general'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Informasi Umum</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Nama Lengkap</p>
                <p class="font-semibold text-gray-800">
                  {{ user.name || "-" }}
                </p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Email</p>
                <p class="font-semibold text-gray-800">
                  {{ user.email || "-" }}
                </p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">Peran</p>
                <p class="font-semibold text-gray-800">{{ roleLabel }}</p>
              </div>

              <div class="border border-gray-200 rounded-xl p-4">
                <p class="text-xs text-gray-500 mb-1">{{ identityLabel }}</p>
                <p class="font-semibold text-gray-800">{{ identityValue }}</p>
              </div>

              <div
                v-if="user.role === 'student'"
                class="border border-gray-200 rounded-xl p-4"
              >
                <p class="text-xs text-gray-500 mb-1">Jenis Kelamin</p>
                <p class="font-semibold text-gray-800">{{ genderLabel }}</p>
              </div>

              <div
                v-if="user.role === 'student'"
                class="border border-gray-200 rounded-xl p-4"
              >
                <p class="text-xs text-gray-500 mb-1">Kelas Saat Ini</p>
                <p class="font-semibold text-gray-800">
                  {{ currentClassLabel }}
                </p>
              </div>
            </div>

            <div
              v-if="user.role === 'student'"
              class="border border-gray-200 rounded-xl p-4"
            >
              <p class="text-sm font-semibold text-gray-700 mb-3">
                Riwayat Kelas
              </p>
              <div
                v-if="classHistory.length === 0"
                class="text-sm text-gray-500"
              >
                Belum ada riwayat kelas.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead
                    class="text-left text-gray-600 border-b border-gray-200"
                  >
                    <tr>
                      <th class="py-2 pr-3">Kelas</th>
                      <th class="py-2 pr-3">Tahun Ajaran</th>
                      <th class="py-2 pr-3">Semester</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in classHistory" :key="item.id">
                      <td class="py-2 pr-3 font-semibold text-gray-800">
                        {{ item.name || "-" }}
                      </td>
                      <td class="py-2 pr-3 text-gray-700">
                        {{ item.academic_year?.name || "-" }}
                      </td>
                      <td class="py-2 pr-3 text-gray-700">
                        {{ formatSemester(item.academic_year?.semester) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'academic'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Pelajaran</h3>
            <p class="text-sm text-gray-500">
              Mata pelajaran yang diikuti siswa pada tahun ajaran aktif saat ini.
            </p>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div
                v-if="studentSchedules.length === 0"
                class="p-6 text-sm text-gray-500 text-center"
              >
                Belum ada jadwal pelajaran untuk tahun ajaran aktif.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead
                    class="bg-gray-50 text-left text-gray-600 border-b border-gray-200"
                  >
                    <tr>
                      <th class="px-4 py-3">Hari</th>
                      <th class="px-4 py-3">Jam</th>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3">Guru Pengajar</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr
                      v-for="schedule in studentSchedules"
                      :key="schedule.id"
                    >
                      <td class="px-4 py-3 font-semibold text-gray-800">
                        {{ formatDay(schedule.day_of_week) }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ formatTime(schedule.start_time) }} -
                        {{ formatTime(schedule.end_time) }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ schedule.subject_name || "-" }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        <router-link
                          v-if="schedule.teacher_id"
                          :to="{ path: `/account/profile/${schedule.teacher_id}` }"
                          class="text-brand-red hover:text-brand-orange font-semibold hover:underline transition-colors"
                        >
                          {{ schedule.teacher_name || "-" }}
                        </router-link>
                        <span v-else>{{ schedule.teacher_name || "-" }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'teaching'" class="space-y-5">
            <h3 class="text-lg font-bold text-gray-800">Jadwal Mengajar</h3>

            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <div
                v-if="teacherSchedules.length === 0"
                class="p-6 text-sm text-gray-500 text-center"
              >
                Belum ada jadwal mengajar untuk pengguna ini.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead
                    class="bg-gray-50 text-left text-gray-600 border-b border-gray-200"
                  >
                    <tr>
                      <th class="px-4 py-3">Hari</th>
                      <th class="px-4 py-3">Jam</th>
                      <th class="px-4 py-3">Kelas</th>
                      <th class="px-4 py-3">Mata Pelajaran</th>
                      <th class="px-4 py-3">Tahun Ajaran</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr v-for="schedule in teacherSchedules" :key="schedule.id">
                      <td class="px-4 py-3 font-semibold text-gray-800">
                        {{ formatDay(schedule.day_of_week) }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ formatTime(schedule.start_time) }} -
                        {{ formatTime(schedule.end_time) }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ schedule.school_class?.name || "-" }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ schedule.subject?.name || "-" }}
                      </td>
                      <td class="px-4 py-3 text-gray-700">
                        {{ schedule.academic_year?.name || "-" }}
                        <span
                          v-if="schedule.academic_year?.semester"
                          class="capitalize"
                          >({{ schedule.academic_year.semester }})</span
                        >
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'security'" class="space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Keamanan Akun</h3>

            <div class="border border-red-300 bg-red-50 rounded-xl p-5">
              <p class="text-sm font-semibold text-brand-red">Danger Zone</p>
              <p class="text-sm text-red-700 mt-1">
                Reset password akan mengembalikan password user ke default
                sistem, dan user wajib mengganti password saat login kembali.
              </p>

              <button
                @click="handleResetPassword"
                :disabled="isResetting"
                class="mt-4 px-4 py-2.5 bg-brand-red hover:bg-brand-orange text-white rounded-lg text-sm font-semibold transition-colors disabled:opacity-70"
              >
                {{
                  isResetting
                    ? "Memproses reset..."
                    : "Reset Password ke Default"
                }}
              </button>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { userService } from "../services/modules/admin/userService";
import { useToastStore } from "../stores/toast";
import { useAuthStore } from "../stores/auth";

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const authStore = useAuthStore();

const user = ref(null);
const isLoading = ref(true);
const isResetting = ref(false);
const activeTab = ref("general");
const selectedAcademicYear = ref("all");

const visibleTabs = computed(() => {
  const tabs = [{ key: "general", label: "Informasi Umum" }];

  if (user.value?.role === "student") {
    tabs.push({ key: "academic", label: "Jadwal Pelajaran" });
  }

  if (user.value?.role === "teacher") {
    tabs.push({ key: "teaching", label: "Jadwal Mengajar" });
  }

  // 3. VALIDASI: Hanya tampilkan tab Keamanan jika yang sedang login adalah 'admin'
  if (authStore.user?.role === "admin") {
    tabs.push({ key: "security", label: "Keamanan Akun" });
  }

  return tabs;
});

// Cek apakah profil yang dibuka adalah profil miliknya sendiri
const isOwnProfile = computed(() => {
  return String(authStore.user?.id) === String(route.params.id);
});

// Judul Halaman Dinamis
const pageTitle = computed(() => {
  return isOwnProfile.value ? "Profil Saya" : "Detail Profil Pengguna";
});

// Deskripsi Halaman Dinamis
const pageDescription = computed(() => {
  return isOwnProfile.value
    ? "Kelola informasi pribadi dan riwayat akademik/jadwal."
    : "Ringkasan profil, histori akademik/jadwal, dan keamanan akun milik pengguna ini.";
});

const avatarInitial = computed(() => {
  if (!user.value?.name) {
    return "?";
  }

  return user.value.name.charAt(0).toUpperCase();
});

const roleLabel = computed(() => {
  const roleMap = {
    student: "Siswa",
    teacher: "Guru",
    admin: "Admin",
    principal: "Kepala Sekolah",
  };

  return roleMap[user.value?.role] || user.value?.role || "-";
});

const roleBadgeClass = computed(() => {
  const roleColorMap = {
    student: "bg-green-50 text-green-700 border-green-200",
    teacher: "bg-blue-50 text-blue-700 border-blue-200",
    admin: "bg-purple-50 text-purple-700 border-purple-200",
    principal: "bg-orange-50 text-orange-700 border-orange-200",
  };

  return (
    roleColorMap[user.value?.role] || "bg-gray-50 text-gray-700 border-gray-200"
  );
});

const identityLabel = computed(() => {
  if (user.value?.role === "student") {
    return "NIS / NISN";
  }

  return "NIP";
});

const identityValue = computed(() => {
  if (!user.value) {
    return "-";
  }

  // Cek apakah data ada di dalam relasi (user.teacher.nip) ATAU langsung di objek user (user.nip)
  if (user.value.role === "student") {
    const nis = user.value.student?.nis || user.value.nis || "-";
    const nisn = user.value.student?.nisn || user.value.nisn || "-";
    return `${nis} / ${nisn}`;
  }

  if (user.value.role === "teacher") {
    return user.value.teacher?.nip || user.value.nip || "-";
  }

  if (user.value.role === "admin") {
    return user.value.admin?.nip || user.value.nip || "-";
  }

  if (user.value.role === "principal") {
    return user.value.principal?.nip || user.value.nip || "-";
  }

  return "-";
});

const genderLabel = computed(() => {
  if (user.value?.student?.gender === "L") {
    return "Laki-laki";
  }

  if (user.value?.student?.gender === "P") {
    return "Perempuan";
  }

  return "-";
});

const classHistory = computed(() => {
  const classes = user.value?.student?.classes || [];

  return [...classes].sort((a, b) => {
    const aYear = Number(a.pivot?.academic_year_id || 0);
    const bYear = Number(b.pivot?.academic_year_id || 0);
    return bYear - aYear;
  });
});

const currentClassLabel = computed(() => {
  if (classHistory.value.length === 0) {
    return "-";
  }

  const activeByYear = classHistory.value.find(
    (item) => item.academic_year?.is_active,
  );

  if (activeByYear) {
    return activeByYear.name;
  }

  return classHistory.value[0]?.name || "-";
});

const gradeHistory = computed(() => user.value?.grade_history || []);

const academicYearOptions = computed(() => {
  const optionMap = new Map();

  gradeHistory.value.forEach((grade) => {
    if (!grade.academic_year_id) {
      return;
    }

    const semesterLabel = grade.semester ? ` (${grade.semester})` : "";
    optionMap.set(
      String(grade.academic_year_id),
      `${grade.academic_year_name || "Tahun Ajaran"}${semesterLabel}`,
    );
  });

  classHistory.value.forEach((cls) => {
    const id = cls.pivot?.academic_year_id;
    if (!id || optionMap.has(String(id))) {
      return;
    }

    const semesterLabel = cls.academic_year?.semester
      ? ` (${cls.academic_year.semester})`
      : "";
    optionMap.set(
      String(id),
      `${cls.academic_year?.name || "Tahun Ajaran"}${semesterLabel}`,
    );
  });

  return Array.from(optionMap.entries()).map(([value, label]) => ({
    value,
    label,
  }));
});

const filteredGradeHistory = computed(() => {
  if (selectedAcademicYear.value === "all") {
    return gradeHistory.value;
  }

  return gradeHistory.value.filter(
    (grade) =>
      String(grade.academic_year_id) === String(selectedAcademicYear.value),
  );
});

const teacherSchedules = computed(() => user.value?.teacher?.schedules || []);

const studentSchedules = computed(() => user.value?.student_schedules || []);

const formatTime = (time) => {
  if (!time) {
    return "-";
  }

  return String(time).slice(0, 5);
};

const formatScore = (score) => {
  if (score === null || score === undefined) {
    return "-";
  }

  return Number(score).toFixed(2);
};

const formatDay = (day) => {
  const dayMap = {
    monday: "Senin",
    tuesday: "Selasa",
    wednesday: "Rabu",
    thursday: "Kamis",
    friday: "Jumat",
    saturday: "Sabtu",
  };

  return dayMap[day] || day || "-";
};

const formatSemester = (semester) => {
  if (semester === "odd") return "Ganjil";
  if (semester === "even") return "Genap";
  return semester || "-";
};

const normalizeActiveTab = () => {
  const available = visibleTabs.value.map((tab) => tab.key);

  if (!available.includes(activeTab.value)) {
    activeTab.value = "general";
  }
};

const fetchUser = async () => {
  isLoading.value = true;

  try {
    // CEK LOGIKA: Apakah URL memiliki parameter ID?
    if (route.params.id) {
      // 1. Dijalankan saat Admin melihat detail pengguna lain (/admin/users/:id)
      const { data } = await userService.getById(route.params.id);
      user.value = data;
    } else {
      // 2. Dijalankan saat User mengklik "Profil Saya" (/account/profile)
      // Kita langsung mengambil data dari sesi yang sedang login di Auth Store
      user.value = authStore.user;
    }

    // Eksekusi fungsi tab setelah data user berhasil didapat
    normalizeActiveTab();

    // Validasi filter tahun ajaran
    if (selectedAcademicYear.value !== "all") {
      const stillExists = academicYearOptions.value.some(
        (option) => option.value === selectedAcademicYear.value,
      );

      if (!stillExists) {
        selectedAcademicYear.value = "all";
      }
    }
  } catch (error) {
    user.value = null;
    console.error("Fetch User Error:", error);
    toastStore.error("Gagal memuat detail pengguna.");
  } finally {
    isLoading.value = false;
  }
};

const handleResetPassword = async () => {
  const confirmed = window.confirm(
    "Yakin mereset password user ini? Password akan dikembalikan ke default dan user wajib mengganti password saat login kembali.",
  );

  if (!confirmed) {
    return;
  }

  isResetting.value = true;

  try {
    const { data } = await userService.resetPassword(route.params.id);
    toastStore.success(
      data?.message || "Password berhasil direset ke default.",
    );
    await fetchUser();
  } catch {
    toastStore.error("Reset password gagal diproses.");
  } finally {
    isResetting.value = false;
  }
};

// Tambahkan state ini di bawah deklarasi state yang lain (user, isLoading, dll)
const fileInput = ref(null);
const isUploading = ref(false);
const previewImageUrl = ref(null);

// Fungsi untuk men-trigger klik pada input file yang disembunyikan
const triggerFileInput = () => {
  if (isUploading.value) return; // Cegah klik ganda saat loading
  fileInput.value.click();
};

// Fungsi memproses file saat dipilih
const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // 1. Validasi Ukuran (Maks 2MB)
  if (file.size > 2 * 1024 * 1024) {
    toastStore.error("Ukuran foto maksimal 2MB.");
    event.target.value = "";
    return;
  }

  previewImageUrl.value = URL.createObjectURL(file);

  const formData = new FormData();
  formData.append("avatar", file);

  isUploading.value = true;

  try {
    // 🌟 PERBAIKAN DI SINI: Tentukan ID target secara dinamis
    // Jika ada route.params.id (Admin), pakai itu. Jika tidak, pakai ID user yang sedang login.
    const targetUserId = route.params.id || authStore.user?.id;

    // Masukkan targetUserId ke dalam parameter
    const response = await userService.uploadAvatar(targetUserId, formData);

    user.value.avatar_url = response.data.avatar_url;
    toastStore.success("Foto profil berhasil diperbarui.");

    // SINKRONISASI KE SIDEBAR (Pinia Store)
    if (authStore.user?.id === user.value.id) {
      authStore.updateUserAvatar(response.data.avatar_url); // Panggil fungsi store
    }
  } catch (error) {
    previewImageUrl.value = null;
    toastStore.error(
      error.response?.data?.message || "Gagal mengunggah foto profil.",
    );
  } finally {
    isUploading.value = false;
    event.target.value = "";
  }
};

const goBack = () => {
  // Cek apakah browser memiliki riwayat halaman sebelumnya
  if (window.history.state && window.history.state.back) {
    router.back();
  } else {
    // Fallback: Jika dibuka di tab baru, kembalikan ke dashboard sesuai role
    const role = authStore.user?.role || "admin";
    router.push(`/${role}/dashboard`);
  }
};

// Ubah watcher di UserProfile.vue (bagian paling bawah script-mu)
watch(
  () => route.params.id,
  () => {
    // Jalankan HANYA jika sedang berada di halaman Detail Pengguna
    if (route.name === 'Detail Pengguna') {
      fetchUser();
    }
  },
);

onMounted(fetchUser);
</script>
