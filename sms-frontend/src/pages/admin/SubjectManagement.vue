<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Mata Pelajaran
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola master data mata pelajaran dan kode kurikulum sekolah.
        </p>
      </div>

      <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3">
        
        <button
          @click="openModal()"
          class="bg-brand-red hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center justify-center whitespace-nowrap"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Tambah Mapel
        </button>
      </div>
    </div>

    <BaseTable
      :columns="tableColumns"
      :data="subjects"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchSubjects"
      emptyMessage="Belum ada mata pelajaran yang terdaftar atau ditemukan."
    >
      <template #cell(code)="{ item }">
        <span class="px-3 py-1 bg-gray-100 text-gray-700 font-mono text-sm font-bold rounded border border-gray-200 uppercase tracking-wider">
          {{ item.code }}
        </span>
      </template>

      <template #cell(name)="{ item }">
        <button
          @click="router.push({ name: 'AdminSubjectDetail', params: { id: item.id } })"
          class="font-bold text-brand-red hover:text-brand-orange hover:underline cursor-pointer transition-colors text-left"
        >
          {{ item.name }}
        </button>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-2">
          <button
            @click="openModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center"
            title="Edit Mapel"
          >
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>

          <button
            @click="promptDelete(item)"
            class="px-3 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex items-center"
            title="Hapus Mapel"
          >
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <div v-if="paginationMeta.total > 0" class="flex justify-end mt-2 text-sm text-gray-500">
      Menampilkan {{ subjects.length }} dari {{ paginationMeta.total }} mata pelajaran.
    </div>

    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'"
      @close="isModalOpen = false"
    >
      <form id="subjectForm" @submit.prevent="saveSubject" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode Mapel</label>
          <input
            v-model="form.code"
            type="text"
            required
            placeholder="Contoh: MTK-101"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors font-mono uppercase"
          />
          <p class="text-xs text-gray-500 mt-1">Kode harus unik. Sistem akan otomatis mengubah ke huruf kapital.</p>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Mata Pelajaran</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Contoh: Matematika Tingkat Lanjut"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors"
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isModalOpen = false"
            class="px-5 py-2.5 bg-white border hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            form="subjectForm"
            :disabled="isSaving"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
          >
            {{ isSaving ? "Menyimpan..." : "Simpan Mapel" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Mata Pelajaran?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDelete"
      @cancel="confirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { subjectService } from '../../services/modules/admin/subjectService';

import BaseTable from '../../components/BaseTable.vue';
import BaseModal from '../../components/BaseModal.vue';
import ConfirmModal from '../../components/ConfirmModal.vue';

const toastStore = useToastStore();
const router = useRouter();

// --- KONFIGURASI TABEL ---
const tableColumns = [
  { key: 'code', label: 'Kode Mapel', align: 'center' },
  { key: 'name', label: 'Nama Mata Pelajaran' },
  { key: 'actions', label: 'Aksi', align: 'center' },
];

// --- STATE ---
const subjects = ref([]);
const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 100,
});
const searchQuery = ref('');
const isLoading = ref(true);
const isSaving = ref(false);

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref(null);

const form = reactive({ code: '', name: '' });

const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  message: '',
  targetId: null,
});

// --- FETCH DATA ---
const fetchSubjects = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = {
      page: page,
      search: searchQuery.value || undefined,
      per_page: paginationMeta.per_page,
    };
    const response = await subjectService.getAll(params);
    
    // Sesuaikan dengan format pagination Laravel
    subjects.value = response.data.data;
    paginationMeta.total = response.data.total;
    paginationMeta.current_page = response.data.current_page;
    paginationMeta.last_page = response.data.last_page;
    paginationMeta.per_page = response.data.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error('Gagal memuat data mata pelajaran.');
  } finally {
    isLoading.value = false;
  }
};

// --- PENCARIAN (DEBOUNCE/ENTER) ---
const handleSearch = () => {
  fetchSubjects(1); // Mulai dari halaman 1 saat mencari
};

// (Opsional) Auto-search saat input kosong
watch(searchQuery, (newVal) => {
  if (newVal === '') {
    fetchSubjects(1);
  }
});

// --- CRUD ---
const openModal = (subject = null) => {
  isEditing.value = !!subject;
  if (subject) {
    selectedId.value = subject.id;
    form.code = subject.code;
    form.name = subject.name;
  } else {
    selectedId.value = null;
    form.code = '';
    form.name = '';
  }
  isModalOpen.value = true;
};

const saveSubject = async () => {
  isSaving.value = true;
  try {
    // UpperCase dilakukan di backend, tapi kita lakukan juga di frontend untuk konsistensi
    const payload = {
      code: form.code.toUpperCase(),
      name: form.name
    };

    if (isEditing.value) {
      const response = await subjectService.update(selectedId.value, payload);
      toastStore.success(response.data.message || 'Mata pelajaran diperbarui.');
    } else {
      const response = await subjectService.create(payload);
      toastStore.success(response.data.message || 'Mata pelajaran baru ditambahkan.');
    }
    
    isModalOpen.value = false;
    fetchSubjects(paginationMeta.current_page);
  } catch (error) {
    // Menangkap error validasi dari backend (contoh: Kode sudah ada)
    const errMessage = error.response?.data?.message || error.response?.data?.error || 'Gagal menyimpan mata pelajaran.';
    toastStore.error(errMessage);
  } finally {
    isSaving.value = false;
  }
};

const promptDelete = (subject) => {
  confirmModal.targetId = subject.id;
  confirmModal.message = `Hapus mata pelajaran "${subject.name}" (${subject.code})? Aksi ini akan ditolak jika mapel sedang digunakan dalam Jadwal.`;
  confirmModal.isOpen = true;
};

const executeDelete = async () => {
  confirmModal.isLoading = true;
  try {
    const response = await subjectService.delete(confirmModal.targetId);
    toastStore.success(response.data.message || 'Mata pelajaran berhasil dihapus.');
    
    // Jika data tinggal 1 di halaman ini, pindah ke halaman sebelumnya setelah hapus
    if (subjects.value.length === 1 && paginationMeta.current_page > 1) {
      fetchSubjects(paginationMeta.current_page - 1);
    } else {
      fetchSubjects(paginationMeta.current_page);
    }
  } catch (error) {
    // Menangkap respon status 403 dari backend
    toastStore.error(error.response?.data?.error || 'Gagal menghapus mata pelajaran.');
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

onMounted(() => {
  fetchSubjects();
});
</script>