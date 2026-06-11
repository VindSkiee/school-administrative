<template>
  <div class="flex h-screen bg-gray-50 font-sans overflow-hidden">
    <!-- SIDEBAR (60% Vibe - Brand Red) -->
    <aside
      :class="[
        'w-64 bg-brand-red text-brand-white flex flex-col transition-all duration-300 z-30',
        isMobileMenuOpen ? 'fixed inset-y-0 left-0' : 'hidden md:flex',
      ]"
    >
      <div
  class="flex items-center justify-center px-2 py-2 h-16 border-b border-red-800/50 shadow-sm relative overflow-hidden shrink-0"
>
  <div
    class="bg-white h-full border border-white/30 rounded-xl flex items-center justify-center px-2.5 gap-2.5 relative overflow-hidden shadow-inner"
  >
    <div
      class="text-black border-r-2 flex flex-col items-center px-2 py-1 min-w-[2.75rem] shrink-0"
    >
      <span
        class="text-[9px] uppercase font-bold tracking-wider leading-none mb-0.5"
      >
        {{ currentMonth }}
      </span>
      <span class="text-base font-black leading-none">
        {{ currentDateNum }}
      </span>
    </div>

    <div class="flex flex-col items-center">
      <h2 class="text-xs font-bold text-gray-800 tracking-wide">
        {{ currentDayName }}
      </h2>

      <div class="flex items-center gap-1 mt-0.5">
        <svg
          class="w-3 h-3 text-gray-500 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>

        <span
          class="text-[11px] text-gray-500 font-mono font-bold tracking-widest"
        >
          {{ currentTime }}
        </span>
      </div>
    </div>
  </div>
</div>

      <!-- Navigation Menu -->
      <nav class="flex-1 overflow-y-auto py-6 px-3">
        <button
          v-for="item in currentNavigation"
          :key="item.name"
          @click="navigate(item.path)"
          class="w-full flex items-center px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 text-left outline-none"
          :class="[
            isActive(item.path)
              ? 'bg-brand-orange text-white shadow-md'
              : 'text-red-100 hover:bg-brand-red hover:brightness-110',
          ]"
        >
          <svg
            :class="clickedMenu === item.path ? 'icon-bounce' : ''"
            class="w-5 h-5 mr-3 opacity-90 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              :d="item.icon"
            />
          </svg>

          <span class="truncate flex-1">
            {{ item.name }}
          </span>

          <transition name="slide-arrow">
            <svg
              v-if="isActive(item.path)"
              class="w-4 h-4 ml-2 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </transition>
        </button>
      </nav>

      <button
        v-if="authStore.user?.id"
        @click="goToProfile"
        class="w-full block text-left p-4 border-t border-red-800/50 bg-black/10 hover:bg-black/20 transition-all duration-200 group cursor-pointer outline-none"
        title="Lihat Profil Saya"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center overflow-hidden pr-2">
            <img
              v-if="authStore.user?.avatar_url"
              :src="authStore.user.avatar_url"
              alt="Avatar"
              class="w-10 h-10 rounded-full object-cover shrink-0 group-hover:scale-105 transition-transform duration-300 shadow-sm border border-red-800/30"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-brand-orange flex items-center justify-center text-white font-bold shrink-0 group-hover:scale-105 transition-transform duration-300 shadow-sm"
            >
              {{ userInitial }}
            </div>

            <div class="ml-3 overflow-hidden">
              <p
                class="text-sm font-semibold truncate text-brand-white group-hover:text-white transition-colors"
              >
                {{ authStore.user?.name }}
              </p>

              <div class="flex items-center text-xs text-red-200 truncate">
                <span class="capitalize">{{ authStore.user?.role }}</span>
                <span
                  class="mx-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                  >•</span
                >
                <span
                  class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-semibold text-brand-orange"
                >
                  Lihat Profil
                </span>
              </div>
            </div>
          </div>

          <svg
            class="w-5 h-5 text-red-300 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            ></path>
          </svg>
        </div>
      </button>
    </aside>

    <!-- Overlay untuk Mobile Sidebar -->
    <div
      v-if="isMobileMenuOpen"
      @click="isMobileMenuOpen = false"
      class="fixed inset-0 bg-black/50 z-20 md:hidden"
    ></div>

    <!-- MAIN CONTENT AREA (30% Vibe - Brand White) -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Navbar -->
      <header
        class="h-16 bg-brand-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 border-b border-gray-200"
      >
        <!-- Hamburger Menu (Mobile) -->
        <button
          @click="isMobileMenuOpen = true"
          class="md:hidden p-2 rounded-md text-gray-500 hover:text-brand-orange focus:outline-none"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            ></path>
          </svg>
        </button>

        <div class="hidden md:block">
          <h2 class="text-lg font-semibold text-gray-700 capitalize">
            {{ $route.name?.replace(/([A-Z])/g, " $1").trim() }}
          </h2>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-4">
          <!-- UBAH: Event click sekarang memanggil promptLogout, bukan handleLogout langsung -->
          <button
            @click="promptLogout"
            class="text-sm font-medium text-gray-500 hover:text-brand-red transition-colors flex items-center"
          >
            <svg
              class="w-5 h-5 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              ></path>
            </svg>
            <span class="hidden sm:inline">Logout</span>
          </button>
        </div>
      </header>

      <!-- Router View (Dynamic Content) -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
        <router-view v-slot="{ Component, route }">
          <transition name="fade" mode="out-in">
            <div :key="route.fullPath">
              <component :is="Component" />
            </div>
          </transition>
        </router-view>
      </main>
    </div>

    <!-- TAMBAHAN: Komponen Global Confirm Modal untuk Logout -->
    <ConfirmModal
      :isOpen="isLogoutModalOpen"
      title="Keluar Aplikasi?"
      message="Sesi Anda akan diakhiri. Anda harus login kembali untuk mengakses sistem."
      confirmText="Ya, Keluar"
      @confirm="executeLogout"
      @cancel="isLogoutModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";
import ConfirmModal from "../components/ConfirmModal.vue"; // IMPORT MODAL

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const isMobileMenuOpen = ref(false);

// State untuk Modal Logout
const isLogoutModalOpen = ref(false);

// ==========================================
// LOGIKA MODERN REAL-TIME CALENDAR
// ==========================================
const timeNow = ref(new Date());
let clockTimer = null;

// Mengambil nama hari (contoh: "Senin", "Selasa")
const currentDayName = computed(() => {
  return new Intl.DateTimeFormat("id-ID", { weekday: "long" }).format(
    timeNow.value,
  );
});

// Mengambil 3 huruf bulan (contoh: "JAN", "FEB")
const currentMonth = computed(() => {
  return new Intl.DateTimeFormat("id-ID", { month: "short" })
    .format(timeNow.value)
    .substring(0, 3);
});

// Mengambil angka tanggal (contoh: "15")
const currentDateNum = computed(() => {
  return timeNow.value.getDate();
});

// Mengambil jam:menit:detik (contoh: "14:30:45")
const currentTime = computed(() => {
  return new Intl.DateTimeFormat("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  })
    .format(timeNow.value)
    .replace(/\./g, ":"); // Pastikan formatnya titik dua
});

onMounted(() => {
  // Jalankan detak jam setiap 1000 milidetik (1 detik)
  clockTimer = setInterval(() => {
    timeNow.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  // Bersihkan timer saat pengguna pindah halaman agar memori tidak bocor
  if (clockTimer) clearInterval(clockTimer);
});
// ==========================================

const userInitial = computed(() => {
  return authStore.user?.name
    ? authStore.user.name.charAt(0).toUpperCase()
    : "U";
});

// FUNGSI BARU 3: Navigasi ke Profil
const goToProfile = () => {
  // Karena rutenya kita beri nama 'MyProfile', kita tinggal panggil namanya
  router.push({ name: "MyProfile" });
};

const isActive = (path) => {
  return route.path.includes(path);
};

// FUNGSI BARU: Membuka Modal
const promptLogout = () => {
  isLogoutModalOpen.value = true;
};

// FUNGSI BARU: Mengeksekusi Logout
const executeLogout = async () => {
  // 1. Tampilkan status loading di tombol konfirmasi modal (opsional, tapi bagus untuk UX)
  isLogoutModalOpen.value = false;

  // 2. Tunggu proses penghapusan sesi selesai
  await authStore.logout();

  // 3. HARD FLUSH: Gunakan window.location.href alih-alih router.push.
  // Ini akan menghancurkan memori Vue sepenuhnya dan mencegah halaman blank/error.
  window.location.href = "/login";
};

// DYNAMIC NAVIGATION CONFIGURATION
const adminNav = [
  {
    name: "Dashboard",
    path: "/admin/dashboard",
    icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  },
  {
    name: "Manajemen Pengguna",
    path: "/admin/users",
    icon: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
  },
  {
    name: "Tahun Ajaran",
    path: "/admin/academic-years",
    icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
  },
  {
    name: "Laporan Akademik",
    path: "/admin/reports",
    icon: "M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
  {
    name: "Data Kelas",
    path: "/admin/classes",
    icon: "M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10",
  },
  {
    name: "Manajemen Mapel",
    path: "/admin/subjects",
    icon: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253",
  },
  {
    name: "Manajemen Jadwal",
    path: "/admin/schedules",
    icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
  },
  {
    name: "Log Aktivitas",
    path: "/admin/activity-logs",
    icon: "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2z",
  },
];

const teacherNav = [
  {
    name: "Dashboard",
    path: "/teacher/dashboard",
    icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  },
  {
    name: "Jadwal & Absensi",
    path: "/teacher/schedules/today",
    icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
  },
  {
    name: "Tugas & Penilaian",
    path: "/teacher/assignments",
    icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
];

const studentNav = [
  {
    name: "Dashboard",
    path: "/student/dashboard",
    icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  },
  {
    name: "Jadwal Kelas",
    path: "/student/schedules",
    icon: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253",
  },
  {
    name: "Tugas Saya",
    path: "/student/assignmentsList",
    icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
  {
    name: "Nilai & Rapor",
    path: "/student/report",
    icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a２ ２ ０ ０１-２ -２z",
  },
];

const principalNav = [
  {
    name: "Dashboard Analitik",
    path: "/principal/dashboard",
    icon: "M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z",
  },
  {
    name: "Laporan Rapor",
    path: "/principal/reports",
    icon: "M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  },
];

const currentNavigation = computed(() => {
  switch (authStore.userRole) {
    case "admin":
      return adminNav;
    case "teacher":
      return teacherNav;
    case "student":
      return studentNav;
    case "principal":
      return principalNav;
    default:
      return [];
  }
});

const clickedMenu = ref(null);

const navigate = (path) => {
  clickedMenu.value = path;

  setTimeout(() => {
    clickedMenu.value = null;
  }, 300);

  router.push(path);
  isMobileMenuOpen.value = false;
};
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.icon-bounce {
  animation: iconBounce 0.35s ease;
}

@keyframes iconBounce {
  0% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.3);
  }

  100% {
    transform: scale(1);
  }
}

.slide-arrow-enter-active,
.slide-arrow-leave-active {
  transition: all 0.2s ease;
}

.slide-arrow-enter-from,
.slide-arrow-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
