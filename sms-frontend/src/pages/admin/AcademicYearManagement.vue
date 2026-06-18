<template>
  <div class="space-y-6">
    <!-- Header & Actions -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Tahun Ajaran</h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola periode akademik aktif. Hanya 1 tahun ajaran yang bisa aktif
          bersamaan.
        </p>
      </div>
      <button
        @click="openModal()"
        class="bg-brand-red hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center"
      >
        <svg
          class="w-5 h-5 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          ></path>
        </svg>
        Tambah Periode
      </button>
    </div>

    <!-- Peringatan Global -->
    <div
      v-if="!hasActiveYear && !isLoading"
      class="bg-red-50 border-l-4 border-brand-red p-4 rounded-r-lg"
    >
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-brand-red"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700 font-medium">
            Sistem Terkunci: Tidak ada Tahun Ajaran yang aktif.
          </p>
          <p class="text-xs text-red-600 mt-1">
            Siswa dan Guru tidak akan bisa mengakses materi atau mengisi nilai
            sebelum Anda mengaktifkan salah satu periode.
          </p>
        </div>
      </div>
    </div>

    <!-- REUSABLE COMPONENT: BaseTable -->
    <BaseTable
      :columns="tableColumns"
      :data="academicYears"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchAcademicYears"
      emptyMessage="Belum ada data tahun ajaran yang terdaftar."
    >
      <template #cell(name)="{ item }">
        <span class="font-bold text-gray-800">{{ item.name }}</span>
      </template>

      <template #cell(semester)="{ item }">
        <span
          class="px-3 py-1 rounded-full text-xs font-semibold capitalize border"
          :class="
            item.semester === 'odd'
              ? 'bg-blue-50 text-blue-700 border-blue-200'
              : 'bg-green-50 text-green-700 border-green-200'
          "
        >
          {{ item.semester === "odd" ? "Ganjil" : "Genap" }}
        </span>
      </template>

      <template #cell(status)="{ item }">
        <span
          v-if="item.is_active"
          class="inline-flex items-center text-center px-2.5 py-1 rounded-full text-xs font-bold bg-brand-red text-white"
        >
          <span
            class="w-1.5 h-1.5 rounded-full bg-white mr-1.5 animate-pulse"
          ></span>
          Aktif Saat Ini
        </span>

        <span
          v-else
          class="inline-flex items-center text-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
        >
          Selesai / Arsip
        </span>
      </template>

      <template #cell(actions)="{ item }">
        <div class="inline-flex items-center gap-2">
          <!-- Set Active -->
          <button
            v-if="!item.is_active"
            @click="promptSetActive(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-orange-50 hover:border-orange-200 text-brand-orange font-semibold rounded-lg shadow-sm transition-colors flex items-center"
            title="Aktifkan Semester Ini"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"
              />
            </svg>
          </button>

          <!-- Edit -->
          <button
            @click="openModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg shadow-sm transition-colors flex items-center"
            title="Edit"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>

          <!-- Hapus -->
          <button
            @click="promptDelete(item)"
            :disabled="item.is_active"
            class="px-3 py-2 font-semibold rounded-lg shadow-sm transition-colors flex items-center"
            :class="
              item.is_active
                ? 'bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed'
                : 'bg-brand-red hover:bg-brand-orange text-white'
            "
            title="Hapus"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </template>
    </BaseTable>

    <!-- MODAL: ADD/EDIT -->
    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'"
      @close="closeModal"
    >
      <form
        id="academicYearForm"
        @submit.prevent="saveAcademicYear"
        class="space-y-4"
      >
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Nama Periode</label
          >
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Contoh: 2025/2026"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Semester</label
          >
          <BaseSelect
            v-model="form.semester"
            :options="[
              { value: 'odd', label: 'Ganjil (Odd)' },
              { value: 'even', label: 'Genap (Even)' },
            ]"
            placeholder="Pilih Semester"
            required
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="closeModal"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            form="academicYearForm"
            :disabled="isSaving"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors disabled:opacity-70 flex items-center"
          >
            {{ isSaving ? "Menyimpan..." : "Simpan Data" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- CONFIRM MODAL (REUSABLE) -->
    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      :title="confirmModal.title"
      :message="confirmModal.message"
      :confirmText="confirmModal.confirmText"
      @confirm="executeConfirmAction"
      @cancel="confirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useToastStore } from "../../stores/toast";
import { useGlobalDropdownsStore } from "../../stores/globalDropdowns";
import { academicYearService } from "../../services/modules/admin/academicYearService";

import BaseSelect from "../../components/BaseSelect.vue";
import BaseTable from "../../components/BaseTable.vue";
import BaseModal from "../../components/BaseModal.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";

const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();

const tableColumns = [
  { key: "name", label: "Tahun Ajaran" },
  { key: "semester", label: "Semester", align: "center" },
  { key: "status", label: "Status", align: "center" },
  { key: "actions", label: "Aksi", align: "center" },
];

const academicYears = ref([]);
const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 100,
});
const isLoading = ref(true);
const isSaving = ref(false);

const isModalOpen = ref(false);
const isEditing = ref(false);
const form = reactive({ id: null, name: "", semester: "odd" });

// Reusable Confirm Modal State for both Delete and Set Active
const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  actionType: "", // 'delete' or 'set_active'
  targetId: null,
  title: "",
  message: "",
  confirmText: "",
});

const hasActiveYear = computed(() =>
  academicYears.value.some((ay) => ay.is_active),
);

const fetchAcademicYears = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = { page: page, per_page: paginationMeta.per_page };
    const response = await academicYearService.getAll(params);
    const payload = response.data;
    academicYears.value = payload.data || payload;
    paginationMeta.total = payload.total ?? academicYears.value.length;
    paginationMeta.current_page = payload.current_page ?? 1;
    paginationMeta.last_page = payload.last_page ?? 1;
    paginationMeta.per_page = payload.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error("Gagal mengambil data tahun ajaran.");
  } finally {
    isLoading.value = false;
  }
};

const saveAcademicYear = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      await academicYearService.update(form.id, form);
      toastStore.success("Tahun ajaran diperbarui.");
    } else {
      await academicYearService.create(form);
      toastStore.success("Tahun ajaran berhasil dibuat.");
    }
    closeModal();
    fetchAcademicYears(paginationMeta.current_page);
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal menyimpan data.");
  } finally {
    isSaving.value = false;
  }
};

// --- MULTI-PURPOSE CONFIRMATION LOGIC ---
const promptSetActive = (item) => {
  confirmModal.actionType = "set_active";
  confirmModal.targetId = item.id;
  confirmModal.title = "Aktifkan Periode Ini?";
  confirmModal.message = `Anda akan mengaktifkan ${item.name} Semester ${item.semester === "odd" ? "Ganjil" : "Genap"}. Tahun ajaran yang saat ini aktif akan dinonaktifkan secara otomatis.`;
  confirmModal.confirmText = "Ya, Aktifkan";
  confirmModal.isOpen = true;
};

const promptDelete = (item) => {
  confirmModal.actionType = "delete";
  confirmModal.targetId = item.id;
  confirmModal.title = "Hapus Tahun Ajaran?";
  confirmModal.message = `Apakah Anda yakin ingin menghapus ${item.name}? Data yang terhubung mungkin akan ikut terpengaruh.`;
  confirmModal.confirmText = "Ya, Hapus";
  confirmModal.isOpen = true;
};

const executeConfirmAction = async () => {
  confirmModal.isLoading = true;
  try {
    if (confirmModal.actionType === "set_active") {
      await academicYearService.setActive(confirmModal.targetId);
      toastStore.success("Tahun ajaran berhasil diaktifkan.");
      dropdowns.invalidateAcademicYears();
    } else if (confirmModal.actionType === "delete") {
      await academicYearService.delete(confirmModal.targetId);
      toastStore.success("Tahun ajaran berhasil dihapus.");
      dropdowns.invalidateAcademicYears();
    }
    fetchAcademicYears(paginationMeta.current_page);
  } catch (error) {
    toastStore.error(
      error.response?.data?.error ||
        error.response?.data?.message ||
        "Operasi gagal dilakukan.",
    );
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

const openModal = (item = null) => {
  isEditing.value = !!item;
  form.id = item?.id || null;
  form.name = item?.name || "";
  form.semester = item?.semester || "odd";
  isModalOpen.value = true;
};

const closeModal = () => (isModalOpen.value = false);

onMounted(fetchAcademicYears);
</script>
