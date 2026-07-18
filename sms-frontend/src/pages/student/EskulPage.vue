<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
        Ekstrakurikuler Saya
      </h1>
      <p class="text-gray-500 text-sm mt-1">
        Kelola keikutsertaan ekstrakurikuler Anda.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-brand-red border-t-transparent"></div>
      <p class="mt-4 text-gray-500">Memuat data eskul...</p>
    </div>

    <template v-else>
      <!-- Pending Change Request Warning -->
      <div
        v-if="eskulData.has_pending_change_request"
        class="bg-yellow-50 border border-yellow-200 rounded-xl p-4"
      >
        <div class="flex items-start gap-3">
          <Icon icon="mdi:alert-circle-outline" class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5" />
          <div class="flex-1">
            <p class="text-sm font-semibold text-yellow-800">
              Pengajuan Pergantian Eskul Menunggu Persetujuan
            </p>
            <p class="text-sm text-yellow-700 mt-1">
              Anda mengajukan pergantian dari
              <strong>{{ eskulData.pending_change_request.current_eskul_name }}</strong> ke
              <strong>{{ eskulData.pending_change_request.requested_eskul_name }}</strong>.
              Pergantian akan aktif pada semester berikutnya.
            </p>
            <button
              @click="cancelChangeRequest"
              :disabled="isCancelling"
              class="mt-3 px-4 py-1.5 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
            >
              {{ isCancelling ? 'Membatalkan...' : 'Batalkan Pengajuan' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Condition 1: Has current eskul -->
      <div v-if="eskulData.has_current_eskul" class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="bg-brand-red/10 p-2 rounded-xl">
              <Icon icon="mdi:trophy-outline" class="w-6 h-6 text-brand-red" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-800">Ekstrakurikuler Aktif</h2>
              <p class="text-sm text-gray-500">Status keikutsertaan eskul semester ini.</p>
            </div>
          </div>

          <div class="space-y-3">
            <div
              v-for="eskul in eskulData.current_eskuls"
              :key="eskul.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-xl"
            >
              <div>
                <p class="font-bold text-gray-800">{{ eskul.eskul_name }}</p>
                <p v-if="eskul.eskul_description && eskul.eskul_description !== '-'" class="text-sm text-gray-500 mt-1">
                  {{ eskul.eskul_description }}
                </p>
                <p v-if="eskul.score !== null" class="text-sm text-gray-600 mt-1">
                  Nilai: <strong>{{ eskul.score }}</strong>
                  <span v-if="eskul.description"> — {{ eskul.description }}</span>
                </p>
              </div>
              <span
                :class="[
                  'px-3 py-1 text-xs font-bold rounded-full',
                  eskul.score !== null ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700',
                ]"
              >
                {{ eskul.score !== null ? 'Dinilai' : 'Aktif' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Change Eskul Button (only if no pending request) -->
        <div v-if="!eskulData.has_pending_change_request">
          <button
            @click="showChangeModal = true"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors shadow-sm flex items-center gap-2"
          >
            <Icon icon="mdi:swap-horizontal" class="w-5 h-5" />
            Ajukan Pergantian Eskul
          </button>
        </div>
      </div>

      <!-- Condition 2 & 3: No eskul -->
      <div v-else>
        <!-- Before deadline: show registration form -->
        <div v-if="!deadlineInfo?.is_passed" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="bg-brand-red/10 p-2 rounded-xl">
              <Icon icon="mdi:plus-circle-outline" class="w-6 h-6 text-brand-red" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-800">Daftar Ekstrakurikuler</h2>
              <p class="text-sm text-gray-500">Pilih kegiatan ekstrakurikuler yang ingin Anda ikuti.</p>
            </div>
          </div>

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

          <div v-if="selectedIds.length > 0" class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-700 font-medium">
              <Icon icon="mdi:information-outline" class="w-4 h-4 inline mr-1" />
              Anda memilih {{ selectedIds.length }} eskul.
            </p>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button
              @click="submitSelection"
              :disabled="isSaving || selectedIds.length === 0"
              class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
            >
              {{ isSaving ? 'Menyimpan...' : 'Simpan Pilihan' }}
            </button>
          </div>
        </div>

        <!-- After deadline: closed -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
          <Icon icon="mdi:clock-outline" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <h3 class="text-lg font-bold text-gray-700 mb-2">Pendaftaran Ditutup</h3>
          <p class="text-gray-500">
            Batas waktu pendaftaran eskul telah habis. Anda tidak terdaftar di eskul semester ini.
          </p>
          <p v-if="deadlineInfo?.deadline" class="text-sm text-gray-400 mt-2">
            Deadline: {{ formatDeadline(deadlineInfo.deadline) }}
          </p>
        </div>
      </div>
    </template>

    <!-- Change Eskul Modal -->
    <BaseModal
      :isOpen="showChangeModal"
      title="Ajukan Pergantian Eskul"
      @close="showChangeModal = false"
    >
      <div class="space-y-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <Icon icon="mdi:alert-circle-outline" class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-yellow-800">Perhatian!</p>
              <p class="text-sm text-yellow-700 mt-1">
                Perubahan eskul hanya akan berlaku pada semester berikutnya. Anda tetap mengikuti eskul saat ini hingga semester berakhir.
              </p>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Eskul Saat Ini</label>
          <input
            :value="eskulData.current_eskuls?.map(e => e.eskul_name).join(', ')"
            type="text"
            disabled
            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pindah Ke Eskul</label>
          <select
            v-model="changeEskulId"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors text-sm"
          >
            <option :value="null" disabled>Pilih eskul baru</option>
            <option
              v-for="eskul in availableForChange"
              :key="eskul.id"
              :value="eskul.id"
            >
              {{ eskul.name }}
            </option>
          </select>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="showChangeModal = false"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            @click="confirmChangeRequest"
            :disabled="isSaving || !changeEskulId"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
          >
            {{ isSaving ? 'Mengirim...' : 'Kirim Pengajuan' }}
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { eskulService } from '../../services/modules/student/eskulService';
import BaseModal from '../../components/BaseModal.vue';

const toastStore = useToastStore();

const eskulData = ref({
  current_eskuls: [],
  has_current_eskul: false,
  has_pending_change_request: false,
  pending_change_request: null,
});
const eskulOptions = ref([]);
const selectedIds = ref([]);
const deadlineInfo = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isCancelling = ref(false);
const showChangeModal = ref(false);
const changeEskulId = ref(null);

const availableForChange = computed(() => {
  if (!eskulData.value.has_current_eskul) return eskulOptions.value;
  const currentIds = eskulData.value.current_eskuls.map(e => e.eskul_id);
  return eskulOptions.value.filter(e => !currentIds.includes(e.id));
});

const formatDeadline = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const fetchMyEskuls = async () => {
  try {
    const response = await eskulService.getMyEskuls();
    eskulData.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data eskul.');
  }
};

const fetchOptions = async () => {
  try {
    const response = await eskulService.getOptions();
    eskulOptions.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data eskul.');
  }
};

const fetchDeadline = async () => {
  try {
    const response = await eskulService.getDeadline();
    deadlineInfo.value = response.data.data;
  } catch {
    // deadline is optional, silently ignore
  }
};

const submitSelection = async () => {
  isSaving.value = true;
  try {
    await eskulService.submitSelection(selectedIds.value);
    toastStore.success('Pilihan eskul berhasil disimpan.');
    await fetchMyEskuls();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan pilihan eskul.');
  } finally {
    isSaving.value = false;
  }
};

const cancelChangeRequest = async () => {
  isCancelling.value = true;
  try {
    await eskulService.cancelChangeRequest();
    toastStore.success('Pengajuan pergantian eskul berhasil dibatalkan.');
    await fetchMyEskuls();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal membatalkan pengajuan.');
  } finally {
    isCancelling.value = false;
  }
};

const confirmChangeRequest = async () => {
  isSaving.value = true;
  try {
    await eskulService.submitChangeRequest(changeEskulId.value);
    toastStore.success('Pengajuan pergantian eskul berhasil dikirim. Pergantian akan aktif pada semester berikutnya.');
    showChangeModal.value = false;
    changeEskulId.value = null;
    await fetchMyEskuls();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal mengirim pengajuan.');
  } finally {
    isSaving.value = false;
  }
};

onMounted(async () => {
  isLoading.value = true;
  await Promise.all([fetchMyEskuls(), fetchOptions(), fetchDeadline()]);
  isLoading.value = false;
});
</script>
