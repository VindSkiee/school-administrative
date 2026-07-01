<template>
  <teleport to="body">
    <transition name="modal-fade">
      <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"
          @click="$emit('cancel')"
        ></div>

        <!-- Modal Card -->
        <div class="modal-card relative bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          <!-- Header -->
          <div class="px-6 pt-6 pb-4 border-b border-gray-100">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-xl font-bold text-gray-900">Penggantian Guru</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Pilih pengganti untuk <span class="font-semibold text-brand-red">{{ teacherName }}</span>
                </p>
              </div>
              <button
                @click="$emit('cancel')"
                class="p-1.5 -mr-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="px-6 py-5 overflow-y-auto flex-1 space-y-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
              <svg class="animate-spin h-8 w-8 text-brand-red mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-sm text-gray-500">Memuat data jadwal...</span>
            </div>

            <!-- Empty State -->
            <div v-else-if="!schedules.length && !homeroomClass" class="text-center py-12">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-3">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <p class="text-sm text-gray-500">Guru ini tidak memiliki jadwal atau kelas perwalian aktif.</p>
            </div>

            <template v-else>
              <!-- Section: Jadwal Mengajar -->
              <div v-if="schedules.length > 0">
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                  </div>
                  <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Jadwal Mengajar</h4>
                </div>

                <div class="space-y-3">
                  <div
                    v-for="schedule in schedules"
                    :key="schedule.id"
                    class="bg-gray-50 rounded-xl p-4 border border-gray-100"
                  >
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                      <!-- Info Jadwal -->
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                          <span class="px-2 py-0.5 bg-white border border-gray-200 rounded-md text-xs font-semibold text-gray-700">
                            {{ schedule.class_name }}
                          </span>
                          <span class="text-sm font-semibold text-gray-800">{{ schedule.subject_name }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                          {{ formatDay(schedule.day_of_week) }} &middot; {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                        </p>
                      </div>

                      <!-- Dropdown Guru Pengganti -->
                      <div class="w-full sm:w-56">
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                          Guru Pengganti
                        </label>
                        <BaseSelect
                          :modelValue="scheduleReplacements[schedule.id] || ''"
                          @update:modelValue="(val) => scheduleReplacements[schedule.id] = val"
                          :options="teacherOptions"
                          placeholder="Pilih guru..."
                          searchable
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section: Wali Kelas -->
              <div v-if="homeroomClass">
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-8 h-8 rounded-lg bg-brand-orange/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-brand-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Wali Kelas</h4>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                  <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                      <span class="px-2 py-0.5 bg-white border border-gray-200 rounded-md text-xs font-semibold text-gray-700">
                        {{ homeroomClass.name }}
                      </span>
                      <p class="text-xs text-gray-500 mt-1">Kelas perwalian</p>
                    </div>
                    <div class="w-full sm:w-56">
                      <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                        Wali Kelas Baru
                      </label>
                      <BaseSelect
                        v-model="homeroomReplacement"
                        :options="homeroomOptions"
                        placeholder="Pilih guru..."
                        searchable
                      />
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-between gap-3">
            <p v-if="validationError" class="text-xs text-brand-red flex items-center gap-1.5 flex-1">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              {{ validationError }}
            </p>
            <div class="flex justify-end gap-3">
              <button
                @click="$emit('cancel')"
                class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors text-sm"
              >
                Batal
              </button>
              <button
                @click="handleConfirm"
                :disabled="!canConfirm || isLoadingConfirm"
                class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center"
              >
                <svg v-if="isLoadingConfirm" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isLoadingConfirm ? 'Memproses...' : 'Ya, Nonaktifkan' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import BaseSelect from './BaseSelect.vue';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  isLoading: { type: Boolean, default: false },
  isLoadingConfirm: { type: Boolean, default: false },
  teacherName: { type: String, default: '' },
  schedules: { type: Array, default: () => [] },
  homeroomClass: { type: Object, default: null },
  teacherOptions: { type: Array, default: () => [] },
  homeroomOptions: { type: Array, default: () => [] },
  validationError: { type: String, default: '' },
});

const emit = defineEmits(['cancel', 'confirm']);

const scheduleReplacements = reactive({});
const homeroomReplacement = ref('');

// Reset selections when modal opens
watch(() => props.isOpen, (open) => {
  if (open) {
    props.schedules.forEach((s) => {
      scheduleReplacements[s.id] = '';
    });
    homeroomReplacement.value = '';
  }
});

// Cek apakah semua jadwal sudah punya guru pengganti
const allSchedulesAssigned = computed(() => {
  if (!props.schedules.length) return true;
  return props.schedules.every((s) => scheduleReplacements[s.id]);
});

// Cek apakah homeroom sudah punya pengganti (jika ada)
const homeroomAssigned = computed(() => {
  if (!props.homeroomClass) return true;
  return !!homeroomReplacement.value;
});

const canConfirm = computed(() => {
  return allSchedulesAssigned.value && homeroomAssigned.value;
});

const formatDay = (day) => {
  const map = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
  };
  return map[day] || day;
};

const formatTime = (time) => {
  if (!time) return '';
  return time.substring(0, 5);
};

const handleConfirm = () => {
  if (!canConfirm.value) return;

  const payload = {
    schedule_replacements: props.schedules.map((s) => ({
      schedule_id: s.id,
      new_teacher_id: scheduleReplacements[s.id],
    })),
    homeroom_replacement: homeroomReplacement.value || null,
  };

  emit('confirm', payload);
};
</script>

<style scoped>
.modal-fade-enter-active { transition: opacity 0s; }
.modal-fade-leave-active { transition: opacity 0.2s ease-in; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .modal-card { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-fade-leave-active .modal-card { transition: all 0.2s ease-in; }
.modal-fade-enter-from .modal-card, .modal-fade-leave-to .modal-card { opacity: 0; transform: scale(0.95) translateY(10px); }
</style>
