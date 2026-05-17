<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center font-sans px-4">
    
    <!-- Animasi Masuk -->
    <transition appear enter-active-class="transition ease-out duration-500" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
      <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-xl max-w-lg w-full text-center border-t-4 border-brand-red">
        
        <!-- Icon Area (Shield Lock) -->
        <div class="flex justify-center mb-6">
          <div class="p-4 bg-red-50 rounded-full">
            <svg class="w-20 h-20 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
          </div>
        </div>

        <!-- Text Area -->
        <h1 class="text-3xl font-bold text-gray-800 mb-3">403 | Akses Ditolak</h1>
        
        <p class="text-gray-500 mb-8 leading-relaxed">
          Maaf, Anda tidak memiliki tingkat otorisasi yang diperlukan untuk melihat halaman ini. Tindakan ini telah dicatat oleh sistem keamanan jaringan.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-center gap-3">
          <button
            @click="goBackToDashboard"
            class="w-full sm:w-auto px-6 py-3 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-brand-orange/50"
          >
            Kembali ke Dashboard
          </button>
          
          <button
            @click="handleLogout"
            class="w-full sm:w-auto px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg border border-gray-300 shadow-sm transition-colors duration-200 focus:outline-none"
          >
            Logout
          </button>
        </div>

      </div>
    </transition>
    
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Logika Pemulihan Cerdas
const goBackToDashboard = () => {
  const role = authStore.userRole;
  
  if (role) {
    // Arahkan ke dashboard yang sesuai dengan rolenya
    router.push(`/${role}/dashboard`);
  } else {
    // Jika state role entah kenapa hilang (edge case), lempar ke login
    router.push('/login');
  }
};

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>