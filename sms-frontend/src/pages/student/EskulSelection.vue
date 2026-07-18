<template>
  <div class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <!-- Header Card -->
      <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-brand-red to-brand-orange p-6 text-white">
          <div class="flex items-center gap-4">
            <div class="bg-white/20 p-3 rounded-xl">
              <Icon icon="mdi:trophy-outline" class="w-8 h-8" />
            </div>
            <div>
              <h1 class="text-2xl font-bold font-serif">Pilih Ekstrakurikuler</h1>
              <p class="text-white/80 text-sm mt-1">
                Pilih kegiatan ekstrakurikuler yang ingin kamu ikuti semester ini.
              </p>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Loading State -->
          <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-brand-red border-t-transparent"></div>
            <p class="mt-4 text-gray-500">Memuat data eskul...</p>
          </div>

          <!-- Deadline Passed -->
          <div v-else-if="deadlineInfo?.is_passed" class="text-center py-8">
            <Icon icon="mdi:clock-outline" class="w-16 h-16 mx-auto text-yellow-400 mb-4" />
            <h3 class="text-lg font-bold text-gray-800 mb-2">Batas Waktu Pendaftaran Habis</h3>
            <p class="text-gray-500 mb-1">Batas waktu pendaftaran eskul telah habis.</p>
            <p class="text-sm text-gray-400">Anda tidak terdaftar di eskul semester ini.</p>
          </div>

          <!-- Eskul Options -->
          <div v-else>
            <p class="text-sm text-gray-600 mb-4">
              Kamu bisa memilih lebih dari satu eskul, atau kosongkan jika tidak ingin ikut eskul.
            </p>

            <div v-if="eskulOptions.length === 0" class="text-center py-8 text-gray-400">
              <Icon icon="mdi:alert-circle-outline" class="w-12 h-12 mx-auto mb-2" />
              <p>Belum ada eskul yang tersedia.</p>
            </div>

            <div v-else class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
              <label
                v-for="eskul in eskulOptions"
                :key="eskul.id"
                :class="[
                  'flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all',
                  selectedIds.includes(eskul.id)
                    ? 'border-brand-red bg-red-50'
                    : 'border-gray-200 hover:border-gray-300 bg-white',
                ]"
              >
                <input
                  type="checkbox"
                  :value="eskul.id"
                  v-model="selectedIds"
                  class="mt-1 w-5 h-5 text-brand-red border-gray-300 rounded focus:ring-brand-red"
                />
                <div class="flex-1">
                  <div class="font-bold text-gray-800">{{ eskul.name }}</div>
                  <div v-if="eskul.description" class="text-sm text-gray-500 mt-1">
                    {{ eskul.description }}
                  </div>
                </div>
                <Icon
                  v-if="selectedIds.includes(eskul.id)"
                  icon="mdi:check-circle"
                  class="w-6 h-6 text-brand-red shrink-0"
                />
              </label>
            </div>

            <!-- Selected Summary -->
            <div v-if="selectedIds.length > 0" class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
              <p class="text-sm text-blue-700 font-medium">
                <Icon icon="mdi:information-outline" class="w-4 h-4 inline mr-1" />
                Kamu memilih {{ selectedIds.length }} eskul.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 pb-6">
          <div class="flex justify-end gap-3">
            <button
              @click="skipSelection"
              class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
            >
              Lewati
            </button>
            <button
              @click="submitSelection"
              :disabled="isSaving"
              class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
            >
              {{ isSaving ? 'Menyimpan...' : 'Simpan Pilihan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { useAuthStore } from '../../stores/auth';
import { eskulService } from '../../services/modules/student/eskulService';

const router = useRouter();
const toastStore = useToastStore();
const authStore = useAuthStore();

const eskulOptions = ref([]);
const selectedIds = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const deadlineInfo = ref(null);

const fetchOptions = async () => {
  isLoading.value = true;
  try {
    const response = await eskulService.getOptions();
    eskulOptions.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data ekstrakurikuler.');
  } finally {
    isLoading.value = false;
  }
};

const fetchDeadline = async () => {
  try {
    const response = await eskulService.getDeadline();
    deadlineInfo.value = response.data.data;
  } catch {
    // deadline is optional
  }
};

const submitSelection = async () => {
  isSaving.value = true;
  try {
    await eskulService.submitSelection(selectedIds.value);
    toastStore.success('Pilihan eskul berhasil disimpan.');
    authStore.markEskulSelectionCompleted();
    router.push(`/${authStore.userRole}/dashboard`);
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan pilihan eskul.');
  } finally {
    isSaving.value = false;
  }
};

const skipSelection = async () => {
  try {
    await eskulService.skip();
  } catch {
    // silently ignore — local flag will still be set
  }
  authStore.markEskulSelectionCompleted();
  router.push(`/${authStore.userRole}/dashboard`);
};

onMounted(async () => {
  isLoading.value = true;
  await Promise.all([fetchOptions(), fetchDeadline()]);
  isLoading.value = false;
});
</script>
