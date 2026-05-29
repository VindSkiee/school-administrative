<template>
  <div class="page min-h-screen relative overflow-hidden bg-gradient-to-br from-sky-50 via-white to-amber-50 text-gray-800">
    <div class="absolute -top-28 -right-20 h-64 w-64 rounded-full bg-sky-200/60 blur-3xl"></div>
    <div class="absolute -bottom-32 -left-24 h-72 w-72 rounded-full bg-amber-200/70 blur-3xl"></div>

    <div class="relative z-10 mx-auto flex min-h-screen max-w-4xl flex-col items-start justify-center px-6 py-16">
      <span class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600">
        503
      </span>
      <h1 class="heading mt-4 text-4xl sm:text-5xl font-semibold text-gray-900">
        Server sedang sibuk
      </h1>
      <p class="mt-4 max-w-2xl text-base sm:text-lg text-gray-600">
        Kami tidak bisa terhubung ke server saat ini. Silakan coba beberapa saat lagi atau
        kembali ke dashboard Anda.
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <button
          @click="reloadPage"
          class="px-6 py-3 rounded-xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-200/70 hover:bg-sky-700 transition-colors"
        >
          Coba Lagi
        </button>
        <button
          @click="goToDashboard"
          class="px-6 py-3 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold hover:bg-gray-50 transition-colors"
        >
          Ke Dashboard
        </button>
      </div>

      <div class="mt-10 flex items-center gap-3 text-xs text-gray-500">
        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-500"></span>
        Jika masalah berlanjut, hubungi admin sistem.
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const authStore = useAuthStore();
let healthTimerId = null;

const goToDashboard = () => {
  if (authStore.isAuthenticated && authStore.userRole) {
    router.push(`/${authStore.userRole}/dashboard`);
  } else {
    router.push("/login");
  }
};

const reloadPage = () => {
  window.location.reload();
};

const checkServerHealth = async () => {
  try {
    const response = await fetch("/api/health", { cache: "no-store" });
    if (response.ok) {
      await authStore.logout();
      router.replace("/login");
    }
  } catch (error) {
    // Tetap di halaman ini saat server belum siap.
  }
};

onMounted(() => {
  checkServerHealth();
  healthTimerId = window.setInterval(checkServerHealth, 4000);
});

onBeforeUnmount(() => {
  if (healthTimerId) {
    window.clearInterval(healthTimerId);
  }
});
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
