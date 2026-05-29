<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        
        <!-- Backdrop (Langsung Muncul Instan) -->
        <div 
          class="absolute inset-0 bg-black/50 backdrop-blur-sm" 
          @click="$emit('cancel')"
        ></div>

        <!-- Modal Card (Scale Bounce) -->
        <div class="modal-card relative bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center">
          
          <!-- Icon (Warning/Danger) -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg class="h-8 w-8 text-brand-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>

          <!-- Text Content -->
          <h3 class="text-xl font-bold text-gray-900 mb-2">{{ title }}</h3>
          <p class="text-sm text-gray-500 mb-6">{{ message }}</p>

          <!-- Actions -->
          <div class="flex gap-3 justify-center">
            <button 
              @click="$emit('cancel')" 
              class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors"
            >
              {{ cancelText }}
            </button>
            <button 
              @click="$emit('confirm')" 
              :disabled="isLoading"
              class="w-full px-4 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex justify-center items-center disabled:opacity-70"
            >
              <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ isLoading ? 'Memproses...' : confirmText }}
            </button>
          </div>
        </div>

      </div>
    </transition>
  </teleport>
</template>

<script setup>
defineProps({
  isOpen: { type: Boolean, required: true },
  isLoading: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin melakukan tindakan ini?' },
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  cancelText: { type: String, default: 'Batal' }
});

defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
/* 
  1. ROOT / BACKDROP ANIMATION 
  Saat masuk: INSTAN (0s) sehingga efek blur hitam langsung tercipta sejak awal.
  Saat keluar: Halus (0.2s)
*/
.modal-fade-enter-active {
  transition: opacity 0s; 
}
.modal-fade-leave-active {
  transition: opacity 0.2s ease-in;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* 
  2. CARD ANIMATION (Terpisah dari Root)
  Saat masuk: Bounce (Cubic-bezier) dan fade-in selama 0.3s
  Saat keluar: Fade out ke bawah selama 0.2s
*/
.modal-fade-enter-active .modal-card {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-fade-leave-active .modal-card {
  transition: all 0.2s ease-in;
}
.modal-fade-enter-from .modal-card,
.modal-fade-leave-to .modal-card {
  opacity: 0;
  transform: scale(0.90) translateY(15px);
}
</style>