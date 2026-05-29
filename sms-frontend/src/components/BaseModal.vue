<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6">
        
        <!-- Backdrop (Lebih gelap sedikit dengan blur premium) -->
        <div 
          class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" 
          @click="isPersistent ? null : $emit('close')"
        ></div>

        <!-- Modal Content (Seamless Design tanpa border kasar) -->
        <div class="modal-card relative bg-white w-full rounded-2xl shadow-2xl overflow-hidden flex flex-col" :class="maxWidthClass">
          
          <!-- Header Seamless -->
          <div class="px-6 sm:px-8 pt-6 pb-4 flex justify-between items-start bg-white">
            <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ title }}</h3>
            <button @click="$emit('close')" class="p-1.5 -mr-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <!-- Body (Padding seragam, custom scrollbar) -->
          <div class="px-6 sm:px-8 pb-6 overflow-y-auto max-h-[75vh] custom-scrollbar">
            <slot></slot>
          </div>

          <!-- Footer Seamless (Menyatu dengan body) -->
          <div v-if="$slots.footer" class="px-6 sm:px-8 py-4 bg-gray-50/50">
            <slot name="footer"></slot>
          </div>

        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  title: { type: String, required: true },
  maxWidth: { type: String, default: 'md' }, 
  isPersistent: { type: Boolean, default: false } 
});

defineEmits(['close']);

const maxWidthClass = computed(() => {
  const sizes = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl' };
  return sizes[props.maxWidth] || sizes.md;
});
</script>

<style scoped>
/* Transisi Reusable */
.modal-fade-enter-active { transition: opacity 0s; }
.modal-fade-leave-active { transition: opacity 0.2s ease-in; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .modal-card { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-fade-leave-active .modal-card { transition: all 0.2s ease-in; }
.modal-fade-enter-from .modal-card, .modal-fade-leave-to .modal-card { opacity: 0; transform: scale(0.95) translateY(10px); }

/* Custom Scrollbar Tipis */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
</style>