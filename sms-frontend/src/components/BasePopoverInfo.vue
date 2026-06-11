<template>
  <div 
    class="relative inline-flex items-center" 
    @mouseenter="onMouseEnter" 
    @mouseleave="onMouseLeave"
  >
    <button 
      type="button" 
      @click="togglePin" 
      class="text-brand-red/60 hover:text-brand-red focus:outline-none transition-colors p-1 rounded-full hover:bg-red-50"
      :class="{ 'text-brand-red bg-red-50': isPinned }"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </button>

    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-1 scale-95"
    >
      <div 
        v-if="isVisible" 
        class="absolute z-50 top-full mt-2 right-0 sm:right-auto sm:-left-4 w-64 sm:w-72 bg-white border border-gray-200 shadow-xl rounded-xl p-4 cursor-default origin-top-right sm:origin-top-left"
        @click.stop
      >
        <button 
          v-if="isPinned" 
          @click.stop="closeCard" 
          class="absolute top-2 right-2 text-gray-400 hover:text-brand-red bg-gray-50 hover:bg-red-50 p-1.5 rounded-full transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <div class="text-xs text-gray-600 font-medium leading-relaxed" :class="{ 'pr-6': isPinned }">
          <slot></slot>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const isHovered = ref(false);
const isPinned = ref(false);

// Card akan terlihat jika di-hover ATAU jika sedang dipin (diklik)
const isVisible = computed(() => isHovered.value || isPinned.value);

const onMouseEnter = () => {
  if (!isPinned.value) isHovered.value = true;
};

const onMouseLeave = () => {
  if (!isPinned.value) isHovered.value = false;
};

const togglePin = () => {
  isPinned.value = !isPinned.value;
  // Jika unpin, periksa apakah kursor masih di atas elemen atau tidak (biasanya kita anggap false agar langsung tertutup)
  if (!isPinned.value) {
    isHovered.value = false;
  }
};

const closeCard = () => {
  isPinned.value = false;
  isHovered.value = false;
};
</script>