<template>
  <div class="page min-h-screen relative overflow-hidden bg-gradient-to-br from-amber-50 via-white to-rose-50 text-gray-800">
    <div class="absolute -top-32 -left-24 h-64 w-64 rounded-full bg-amber-200/60 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-24 h-72 w-72 rounded-full bg-rose-200/60 blur-3xl"></div>

    <div class="relative z-10 mx-auto flex min-h-screen max-w-4xl flex-col items-start justify-center px-6 py-16">
      <span class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">
        404
      </span>
      <h1 class="heading mt-4 text-4xl sm:text-5xl font-semibold text-gray-900">
        Halaman tidak ditemukan
      </h1>
      <p class="mt-4 max-w-2xl text-base sm:text-lg text-gray-600">
        Jalur yang Anda tuju tidak tersedia atau sudah dipindahkan. Gunakan tombol di bawah
        untuk kembali ke area yang aman.
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <button
          @click="goToDashboard"
          class="px-6 py-3 rounded-xl bg-amber-600 text-white font-semibold shadow-lg shadow-amber-200/60 hover:bg-amber-700 transition-colors"
        >
          Ke Dashboard
        </button>
        <button
          @click="goBack"
          class="px-6 py-3 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold hover:bg-gray-50 transition-colors"
        >
          Kembali
        </button>
      </div>

      <div class="mt-10 flex items-center gap-3 text-xs text-gray-500">
        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
        Cek kembali URL atau gunakan menu navigasi.
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const goToDashboard = () => {
  if (authStore.isAuthenticated && authStore.userRole) {
    router.push(`/${authStore.userRole}/dashboard`);
  } else {
    router.push("/login");
  }
};

const goBack = () => {
  router.back();
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Space+Grotesk:wght@400;600;700&display=swap");

.page {
  font-family: "Space Grotesk", "Segoe UI", sans-serif;
}

.heading {
  font-family: "Fraunces", "Space Grotesk", serif;
}
</style>
