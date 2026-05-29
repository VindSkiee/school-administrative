<template>
  <div class="relative w-full" ref="selectContainer">
    
    <!-- Trigger Button -->
    <button
      ref="triggerBtn"
      type="button"
      @click="toggleDropdown"
      :disabled="disabled"
      class="w-full flex items-center justify-between px-4 py-2.5 bg-white border rounded-lg outline-none text-sm transition-all duration-200"
      :class="[
        isOpen 
          ? 'border-brand-red ring-2 ring-brand-red/20 shadow-sm' 
          : 'border-gray-200 hover:border-gray-400 hover:bg-gray-50',
        disabled 
          ? 'bg-gray-50 cursor-not-allowed text-gray-400' 
          : 'cursor-pointer'
      ]"
    >
      <span class="truncate pr-4" :class="(!modelValue && placeholder) ? 'text-gray-400' : 'text-gray-800 font-medium'">
        {{ selectedLabel }}
      </span>
      <svg class="w-4 h-4 text-gray-400 transition-transform duration-300 ease-in-out shrink-0" :class="{ 'rotate-180 text-brand-red': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <!-- Dropdown Panel (DI-TELEPORT KE BODY) -->
    <teleport to="body">
      <transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="isOpen"
          ref="dropdownPanel"
          :style="dropdownStyle"
          class="fixed z-[100] bg-white border border-gray-100 rounded-xl shadow-2xl overflow-hidden mt-1"
        >
          <div
            v-if="isSearchEnabled"
            class="px-3 pt-3 pb-2 border-b border-gray-100"
          >
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none"
            />
          </div>
          <ul class="max-h-60 overflow-y-auto py-1.5 custom-scrollbar">
            <li
              v-if="placeholder"
              @click="selectOption('')"
              class="px-4 py-2.5 text-sm cursor-pointer transition-colors flex items-center justify-between"
              :class="modelValue === '' ? 'bg-gray-50 text-gray-800 font-semibold' : 'text-gray-500 hover:bg-gray-50'"
            >
              <span>{{ placeholder }}</span>
            </li>
            <li
              v-for="option in filteredOptions"
              :key="option.value"
              @click="selectOption(option.value)"
              class="px-4 py-2.5 text-sm cursor-pointer transition-all duration-150 flex items-center justify-between group"
              :class="modelValue === option.value ? 'bg-brand-red/5 text-brand-red font-semibold' : 'text-gray-700 hover:bg-brand-red/5 hover:text-brand-red'"
            >
              <span class="truncate pr-4" :title="option.label">{{ option.label }}</span>
              <svg v-if="modelValue === option.value" class="w-4 h-4 text-brand-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </li>
            <li
              v-if="isSearchEnabled && filteredOptions.length === 0"
              class="px-4 py-3 text-sm text-gray-500"
            >
              {{ emptyMessage }}
            </li>
          </ul>
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true },
  placeholder: { type: String, default: 'Pilih opsi...' },
  disabled: { type: Boolean, default: false },
  searchable: { type: Boolean, default: false },
  searchPlaceholder: { type: String, default: 'Cari...' },
  emptyMessage: { type: String, default: 'Tidak ada data.' },
  searchThreshold: { type: Number, default: 20 },
  autoWidth: { type: Boolean, default: true },
  dropdownMargin: { type: Number, default: 12 }
});

const emit = defineEmits(['update:modelValue']);

// State
const isOpen = ref(false);
const triggerBtn = ref(null);
const dropdownPanel = ref(null);
const searchInput = ref(null);
const searchQuery = ref('');
const dropdownStyle = ref({});
const measuredWidth = ref(null);

// Computed
const selectedLabel = computed(() => {
  if (props.modelValue === '') return props.placeholder;
  const match = props.options.find(opt => opt.value === props.modelValue);
  return match ? match.label : props.placeholder;
});

const isSearchEnabled = computed(() => {
  return props.searchable || props.options.length >= props.searchThreshold;
});

const filteredOptions = computed(() => {
  if (!isSearchEnabled.value || !searchQuery.value) return props.options;
  const query = searchQuery.value.toLowerCase();
  return props.options.filter(option =>
    String(option.label || '').toLowerCase().includes(query)
  );
});

// Calculate Position
const measureContentWidth = () => {
  if (!triggerBtn.value || !dropdownPanel.value) return null;

  const rect = triggerBtn.value.getBoundingClientRect();
  const computedStyle = window.getComputedStyle(dropdownPanel.value);
  const font = computedStyle.font || `${computedStyle.fontWeight} ${computedStyle.fontSize} ${computedStyle.fontFamily}`;

  const canvas = document.createElement('canvas');
  const context = canvas.getContext('2d');
  if (!context) return rect.width;
  context.font = font;

  let maxLabelWidth = 0;
  for (const option of props.options) {
    const label = String(option.label ?? '');
    const width = context.measureText(label).width;
    if (width > maxLabelWidth) maxLabelWidth = width;
  }

  const padding = 64; // kiri/kanan + ruang icon centang
  return Math.max(rect.width, Math.ceil(maxLabelWidth + padding));
};

const calculatePosition = () => {
  if (!triggerBtn.value) return;

  const rect = triggerBtn.value.getBoundingClientRect();
  const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
  const margin = Math.max(0, props.dropdownMargin);

  let width = measuredWidth.value ?? rect.width;
  const maxWidth = Math.max(160, viewportWidth - margin * 2);
  width = Math.min(width, maxWidth);

  let left = rect.left;
  if (left + width > viewportWidth - margin) {
    left = Math.max(margin, viewportWidth - margin - width);
  }
  if (left < margin) left = margin;

  dropdownStyle.value = {
    top: `${rect.bottom}px`,
    left: `${left}px`,
    width: `${width}px`
  };
};

const toggleDropdown = async () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  
  if (isOpen.value) {
    // Tunggu DOM update, lalu kalkulasi posisi
    await nextTick();
    if (props.autoWidth) {
      measuredWidth.value = measureContentWidth();
    }
    calculatePosition();
    if (isSearchEnabled.value && searchInput.value) {
      searchInput.value.focus();
    }
  } else {
    measuredWidth.value = null;
  }
};

const selectOption = (value) => {
  emit('update:modelValue', value);
  closeDropdown();
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = '';
  measuredWidth.value = null;
};

// Global Event Listeners
const handleClickOutside = (event) => {
  // Jika klik bukan pada tombol pemicu DAN dropdown sedang terbuka, tutup
  if (isOpen.value && triggerBtn.value && !triggerBtn.value.contains(event.target)) {
    // Pastikan juga klik tidak terjadi di DALAM dropdown panel itu sendiri
    if (dropdownPanel.value && dropdownPanel.value.contains(event.target)) return;
    
    closeDropdown();
  }
};

// Jika user melakukan scroll (baik di modal atau body), tutup dropdown agar tidak "tertinggal"
let repositionFrame = null;

const scheduleReposition = () => {
  if (!isOpen.value) return;
  if (repositionFrame) return;
  repositionFrame = window.requestAnimationFrame(() => {
    repositionFrame = null;
    calculatePosition();
  });
};

const handleScroll = () => {
  scheduleReposition();
};

const handleResize = () => {
  scheduleReposition();
};

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  // Tangkap scroll di level window
  window.addEventListener('scroll', handleScroll, true); // true = tangkap scroll pada child elements (seperti modal)
  window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('scroll', handleScroll, true);
  window.removeEventListener('resize', handleResize);
  if (repositionFrame) {
    window.cancelAnimationFrame(repositionFrame);
    repositionFrame = null;
  }
});

watch(
  () => props.options.length,
  () => {
    if (!isOpen.value) return;
    measuredWidth.value = props.autoWidth ? measureContentWidth() : null;
    calculatePosition();
  }
);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
</style>