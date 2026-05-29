<template>
  <div class="fixed top-4 right-4 z-100 w-[92vw] max-w-sm pointer-events-none">
    <transition-group
      name="toast-slide"
      tag="div"
      class="flex flex-col gap-3"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto"
      >
        <!-- Kartu toast dengan glassmorphism -->
        <div
          :class="[
            'relative flex items-start gap-3 rounded-2xl border-2 backdrop-blur-md shadow-xl p-4',
            'transition-all duration-300 hover:shadow-2xl hover:scale-[1.02]',
            wrapperBorder(toast.type),
          ]"
        >
          <!-- Lingkaran ikon di kiri -->
          <div :class="['flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center', iconBg(toast.type)]">
            <!-- Ikon sukses -->
            <svg
              v-if="toast.type === 'success'"
              class="w-4 h-4 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <!-- Ikon error -->
            <svg
              v-else-if="toast.type === 'error'"
              class="w-4 h-4 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            <!-- Ikon info -->
            <svg
              v-else
              class="w-4 h-4 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>

          <!-- Konten teks -->
          <div class="flex-1 min-w-0">
            <p :class="['text-sm font-bold', titleColor(toast.type)]">
              {{ titleFor(toast.type) }}
            </p>
            <p class="text-sm text-gray-600 mt-0.5 line-clamp-2">
              {{ toast.message }}
            </p>
          </div>

          <!-- Tombol tutup -->
          <button
            type="button"
            class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-black/5"
            @click="remove(toast.id)"
            aria-label="Tutup"
          >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()
const toasts = computed(() => toastStore.toasts)

const remove = (id) => {
  toastStore.remove(id)
}

const titleFor = (type) => {
  switch (type) {
    case 'success':
      return 'Berhasil'
    case 'error':
      return 'Gagal'
    default:
      return 'Info'
  }
}

// Warna border luar + background transparan
const wrapperBorder = (type) => {
  switch (type) {
    case 'success':
      return 'border-emerald-200/60 bg-white/80'
    case 'error':
      return 'border-rose-200/60 bg-white/80'
    default:
      return 'border-sky-200/60 bg-white/80'
  }
}

// Latar lingkaran ikon
const iconBg = (type) => {
  switch (type) {
    case 'success':
      return 'bg-emerald-500 shadow-emerald-200 shadow-sm'
    case 'error':
      return 'bg-rose-500 shadow-rose-200 shadow-sm'
    default:
      return 'bg-sky-500 shadow-sky-200 shadow-sm'
  }
}

// Warna judul
const titleColor = (type) => {
  switch (type) {
    case 'success':
      return 'text-emerald-700'
    case 'error':
      return 'text-rose-700'
    default:
      return 'text-sky-700'
  }
}
</script>

<style scoped>
/* Animasi masuk dari kanan dan keluar ke kanan */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(20px) scale(0.95);
}

.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(60px) scale(0.9);
}
</style>