<template>
  <div class="relative w-full" ref="containerRef">
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
      <span class="truncate pr-4" :class="modelValue ? 'text-gray-800 font-medium' : 'text-gray-400'">
        {{ modelValue || placeholder }}
      </span>
      <svg
        class="w-4 h-4 text-gray-400 transition-transform duration-300 ease-in-out shrink-0"
        :class="{ 'rotate-180 text-brand-red': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <!-- Dropdown Panel -->
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
          <div class="flex min-w-[200px]">
            <!-- Hours Column -->
            <div class="flex-1 border-r border-gray-100">
              <div class="px-3 py-2 text-xs font-semibold text-gray-500 text-center border-b border-gray-200 bg-white">
                Jam
              </div>
              <ul class="h-46 overflow-y-auto custom-scrollbar py-1" ref="hoursList">
                <li
                  v-for="h in hours"
                  :key="h"
                  @click="isHourDisabled(h) ? null : selectHour(h)"
                  class="px-4 py-2.5 text-sm transition-all duration-150 text-center"
                  :class="hourClass(h)"
                >
                  {{ String(h).padStart(2, '0') }}
                </li>
              </ul>
            </div>

            <!-- Minutes Column -->
            <div class="flex-1">
              <div class="px-3 py-2 text-xs font-semibold text-gray-500 text-center border-b border-gray-200 bg-white">
                Menit
              </div>
              <ul class="h-46 overflow-y-auto custom-scrollbar py-1" ref="minutesList">
                <li
                  v-for="m in minutes"
                  :key="m"
                  @click="isMinuteDisabled(m) ? null : selectMinute(m)"
                  class="px-4 py-2.5 text-sm transition-all duration-150 text-center"
                  :class="minuteClass(m)"
                >
                  {{ String(m).padStart(2, '0') }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between px-6 py-3 border-t border-gray-100 bg-gray-50">
            <button
              type="button"
              @click="clearTime"
              class="text-sm text-gray-500 hover:text-brand-red transition-colors font-medium"
            >
              Hapus
            </button>
            <button
              type="button"
              @click="confirmSelection"
              :disabled="!isSelectionValid"
              :class="[
                'text-sm px-4 py-2 rounded-lg font-semibold transition-colors',
                isSelectionValid
                  ? 'bg-brand-red hover:bg-brand-orange text-white'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              Pilih
            </button>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih waktu' },
  disabled: { type: Boolean, default: false },
  minuteInterval: { type: Number, default: 5 },
  dropdownMargin: { type: Number, default: 12 },
  min: { type: String, default: '' },
  max: { type: String, default: '' }
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
const triggerBtn = ref(null);
const dropdownPanel = ref(null);
const hoursList = ref(null);
const minutesList = ref(null);
const isOpen = ref(false);
const dropdownStyle = ref({});

const selectedHour = ref(null);
const selectedMinute = ref(null);

const hours = computed(() => Array.from({ length: 24 }, (_, i) => i));

const minutes = computed(() => {
  const step = props.minuteInterval || 5;
  const result = [];
  for (let i = 0; i < 60; i += step) {
    result.push(i);
  }
  return result;
});

// Parse min/max "HH:mm" to { hour, minute }
const parseTimeLimit = (val) => {
  if (!val || typeof val !== 'string') return null;
  const parts = val.split(':');
  if (parts.length !== 2) return null;
  return { hour: parseInt(parts[0], 10), minute: parseInt(parts[1], 10) };
};

const minLimit = computed(() => parseTimeLimit(props.min));
const maxLimit = computed(() => parseTimeLimit(props.max));

// Check if hour is disabled
const isHourDisabled = (h) => {
  if (minLimit.value && h < minLimit.value.hour) return true;
  if (maxLimit.value && h > maxLimit.value.hour) return true;
  // If min hour and min has non-zero minute, hour is enabled (minutes handled separately)
  // If max hour and max has non-zero minute, hour is enabled (minutes handled separately)
  return false;
};

// Check if minute is disabled based on selected hour and limits
const isMinuteDisabled = (m) => {
  if (selectedHour.value === null) return false;

  // Min constraint: if selected hour == min hour, disable minutes before min minute
  if (minLimit.value && selectedHour.value === minLimit.value.hour) {
    if (m < minLimit.value.minute) return true;
  }

  // Max constraint: if selected hour == max hour, disable minutes after max minute
  if (maxLimit.value && selectedHour.value === maxLimit.value.hour) {
    if (m > maxLimit.value.minute) return true;
  }

  return false;
};

// Check if current selection is valid (within min/max)
const isSelectionValid = computed(() => {
  if (selectedHour.value === null || selectedMinute.value === null) return false;

  const timeStr = `${String(selectedHour.value).padStart(2, '0')}:${String(selectedMinute.value).padStart(2, '0')}`;

  if (minLimit.value && timeStr < props.min) return false;
  if (maxLimit.value && timeStr > props.max) return false;

  return true;
});

// CSS classes for hour items
const hourClass = (h) => {
  if (isHourDisabled(h)) {
    return 'text-gray-300 cursor-not-allowed select-none';
  }
  if (selectedHour.value === h) {
    return 'bg-brand-red/10 text-brand-red font-semibold cursor-pointer';
  }
  return 'text-gray-700 hover:bg-brand-red/5 hover:text-brand-red cursor-pointer';
};

// CSS classes for minute items
const minuteClass = (m) => {
  if (isMinuteDisabled(m)) {
    return 'text-gray-300 cursor-not-allowed select-none';
  }
  if (selectedMinute.value === m) {
    return 'bg-brand-red/10 text-brand-red font-semibold cursor-pointer';
  }
  return 'text-gray-700 hover:bg-brand-red/5 hover:text-brand-red cursor-pointer';
};

// Parse modelValue "HH:mm"
const parseValue = (val) => {
  if (!val || typeof val !== 'string') {
    selectedHour.value = null;
    selectedMinute.value = null;
    return;
  }
  const parts = val.split(':');
  if (parts.length === 2) {
    selectedHour.value = parseInt(parts[0], 10);
    selectedMinute.value = parseInt(parts[1], 10);
  }
};

const toggleDropdown = async () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    parseValue(props.modelValue);
    await nextTick();
    calculatePosition();
    scrollToSelected();
  } else {
    // Validate on close: if selection invalid, clear it
    if (selectedHour.value !== null && selectedMinute.value !== null) {
      if (!isSelectionValid.value) {
        selectedHour.value = null;
        selectedMinute.value = null;
        emit('update:modelValue', '');
      }
    }
  }
};

const selectHour = (h) => {
  if (isHourDisabled(h)) return;
  selectedHour.value = h;
  // If current minute is disabled by new hour constraint, reset
  if (selectedMinute.value !== null && isMinuteDisabled(selectedMinute.value)) {
    selectedMinute.value = null;
  }
  if (selectedMinute.value === null) selectedMinute.value = 0;
};

const selectMinute = (m) => {
  if (isMinuteDisabled(m)) return;
  selectedMinute.value = m;
  if (selectedHour.value === null) selectedHour.value = 0;
};

const confirmSelection = () => {
  if (!isSelectionValid.value) return;
  if (selectedHour.value !== null && selectedMinute.value !== null) {
    const val = `${String(selectedHour.value).padStart(2, '0')}:${String(selectedMinute.value).padStart(2, '0')}`;
    emit('update:modelValue', val);
  }
  isOpen.value = false;
};

const clearTime = () => {
  emit('update:modelValue', '');
  selectedHour.value = null;
  selectedMinute.value = null;
  isOpen.value = false;
};

const scrollToSelected = () => {
  nextTick(() => {
    if (selectedHour.value !== null && hoursList.value) {
      const el = hoursList.value.querySelector(`li:nth-child(${selectedHour.value + 1})`);
      if (el) el.scrollIntoView({ block: 'center' });
    }
    if (selectedMinute.value !== null && minutesList.value) {
      const idx = minutes.value.indexOf(selectedMinute.value);
      if (idx >= 0) {
        const el = minutesList.value.querySelector(`li:nth-child(${idx + 1})`);
        if (el) el.scrollIntoView({ block: 'center' });
      }
    }
  });
};

const calculatePosition = () => {
  if (!triggerBtn.value || !dropdownPanel.value) return;
  const rect = triggerBtn.value.getBoundingClientRect();
  const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  const margin = Math.max(0, props.dropdownMargin);

  let width = Math.max(rect.width, 240);
  const maxWidth = Math.max(240, viewportWidth - margin * 2);
  width = Math.min(width, maxWidth);

  // Horizontal: left/right constraint
  let left = rect.left;
  if (left + width > viewportWidth - margin) {
    left = Math.max(margin, viewportWidth - margin - width);
  }
  if (left < margin) left = margin;

  // Vertical: measure panel height, flip upward if no space below
  const panelHeight = dropdownPanel.value.offsetHeight || 470;
  const spaceBelow = viewportHeight - rect.bottom - margin;
  const spaceAbove = rect.top - margin;

  let top;
  if (spaceBelow < panelHeight && spaceAbove > spaceBelow) {
    top = Math.max(margin, rect.top - panelHeight);
  } else {
    top = rect.bottom;
  }

  dropdownStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    width: `${width}px`
  };
};

const closeDropdown = () => {
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  if (isOpen.value && triggerBtn.value && !triggerBtn.value.contains(event.target)) {
    if (dropdownPanel.value && dropdownPanel.value.contains(event.target)) return;
    closeDropdown();
  }
};

let repositionFrame = null;
const scheduleReposition = () => {
  if (!isOpen.value) return;
  if (repositionFrame) return;
  repositionFrame = window.requestAnimationFrame(() => {
    repositionFrame = null;
    calculatePosition();
  });
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('scroll', scheduleReposition, true);
  window.addEventListener('resize', scheduleReposition);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('scroll', scheduleReposition, true);
  window.removeEventListener('resize', scheduleReposition);
  if (repositionFrame) {
    window.cancelAnimationFrame(repositionFrame);
    repositionFrame = null;
  }
});

watch(() => props.modelValue, (val) => {
  if (!isOpen.value) parseValue(val);
}, { immediate: true });
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
</style>
