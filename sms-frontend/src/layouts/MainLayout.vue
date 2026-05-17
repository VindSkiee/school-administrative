<template>
  <div class="flex h-screen bg-gray-50 font-sans overflow-hidden">
    
    <!-- SIDEBAR (60% Vibe - Brand Red) -->
    <!-- Di mobile disembunyikan menggunakan state isMobileMenuOpen, di desktop fixed -->
    <aside 
      :class="[
        'w-64 bg-brand-red text-brand-white flex flex-col transition-all duration-300 z-30',
        isMobileMenuOpen ? 'fixed inset-y-0 left-0' : 'hidden md:flex'
      ]"
    >
      <!-- Brand Logo -->
      <div class="h-16 flex items-center justify-center border-b border-red-800/50 shadow-sm relative overflow-hidden">
        <div class="absolute inset-0 bg-brand-orange opacity-10"></div> <!-- 10% Aksen -->
        <h1 class="text-xl font-bold tracking-wider relative z-10">EduPlatform</h1>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
        <router-link 
          v-for="item in currentNavigation" 
          :key="item.name" 
          :to="item.path"
          class="flex items-center px-4 py-3 rounded-lg text-sm font-medium transition-colors"
          active-class="bg-brand-orange text-white shadow-md"
          exact-active-class="bg-brand-orange text-white shadow-md"
          :class="[$route.path.includes(item.path) ? 'bg-brand-orange text-white' : 'text-red-100 hover:bg-brand-red hover:brightness-110']"
        >
          <!-- Icon placeholder -->
          <svg class="w-5 h-5 mr-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
          </svg>
          {{ item.name }}
        </router-link>
      </nav>

      <!-- User Profile Box -->
      <div class="p-4 border-t border-red-800/50 bg-black/10">
        <div class="flex items-center">
          <div class="w-10 h-10 rounded-full bg-brand-orange flex items-center justify-center text-white font-bold">
            {{ userInitial }}
          </div>
          <div class="ml-3 overflow-hidden">
            <p class="text-sm font-semibold truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-red-200 capitalize truncate">{{ authStore.user?.role }}</p>
          </div>
        </div>
      </div>
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
      <header class="h-16 bg-brand-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 border-b border-gray-200">
        <!-- Hamburger Menu (Mobile) -->
        <button @click="isMobileMenuOpen = true" class="md:hidden p-2 rounded-md text-gray-500 hover:text-brand-orange focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>

        <div class="hidden md:block">
          <h2 class="text-lg font-semibold text-gray-700 capitalize">{{ $route.name?.replace(/([A-Z])/g, ' $1').trim() }}</h2>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-4">
          <button @click="handleLogout" class="text-sm font-medium text-gray-500 hover:text-brand-red transition-colors flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span class="hidden sm:inline">Logout</span>
          </button>
        </div>
      </header>

      <!-- Router View (Dynamic Content) -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
        <!-- Fade Transition untuk transisi halaman yang mulus -->
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const isMobileMenuOpen = ref(false);

const userInitial = computed(() => {
  return authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U';
});

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};

// DYNAMIC NAVIGATION CONFIGURATION
const adminNav = [
  { name: 'Dashboard', path: '/admin/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Manajemen Pengguna', path: '/admin/users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: 'Data Kelas', path: '/admin/classes', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
];

const teacherNav = [
  { name: 'Dashboard', path: '/teacher/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Jadwal & Absensi', path: '/teacher/attendance', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { name: 'Tugas & Penilaian', path: '/teacher/assignments', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

const studentNav = [
  { name: 'Dashboard', path: '/student/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Materi Belajar', path: '/student/materials', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
  { name: 'Tugas Saya', path: '/student/assignments', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { name: 'Nilai & Rapor', path: '/student/grades', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
];

const principalNav = [
  { name: 'Dashboard Analitik', path: '/principal/dashboard', icon: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z' },
  { name: 'Laporan Rapor', path: '/principal/reports', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

const currentNavigation = computed(() => {
  switch (authStore.userRole) {
    case 'admin': return adminNav;
    case 'teacher': return teacherNav;
    case 'student': return studentNav;
    case 'principal': return principalNav;
    default: return [];
  }
});
</script>

<style>
/* Animasi transisi antar halaman agar tidak kaku */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>