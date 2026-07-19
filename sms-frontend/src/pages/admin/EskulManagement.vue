<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Ekstrakurikuler
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola data ekstrakurikuler dan penugasan guru penanggung jawab (PIC).
        </p>
      </div>

      <button
        @click="openModal()"
        class="bg-brand-red hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center justify-center whitespace-nowrap"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Tambah Eskul
      </button>
    </div>

    <!-- Deadline Settings Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
          <div class="bg-brand-red/10 p-2 rounded-xl">
            <Icon icon="mdi:calendar-clock-outline" class="w-5 h-5 text-brand-red" />
          </div>
          <div>
            <h3 class="font-bold text-gray-800 text-sm">Deadline Pendaftaran Eskul</h3>
            <p class="text-xs text-gray-500">Tentukan batas waktu siswa mendaftar eskul semester ini.</p>
          </div>
        </div>
        <div class="flex items-center gap-3 sm:ml-auto">
          <input
            v-model="eskulDeadline"
            type="date"
            class="px-3 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors text-sm"
          />
          <button
            @click="saveDeadline"
            :disabled="isSavingDeadline"
            class="px-4 py-2 bg-brand-red hover:bg-brand-orange text-white text-sm font-semibold rounded-lg disabled:opacity-50 transition-colors shadow-sm"
          >
            {{ isSavingDeadline ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>

    <BaseTable
      :columns="tableColumns"
      :data="eskuls"
      :isLoading="isLoading"
      emptyMessage="Belum ada data ekstrakurikuler."
    >
      <template #cell(name)="{ item }">
        <span class="font-bold text-gray-800">{{ item.name }}</span>
      </template>

      <template #cell(teacher_name)="{ item }">
        <span :class="item.teacher_id ? 'text-gray-700' : 'text-gray-400 italic'">
          {{ item.teacher_name }}
        </span>
      </template>

      <template #cell(student_count)="{ item }">
        <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-sm font-semibold rounded-full border border-blue-200">
          {{ item.student_count }} siswa
        </span>
      </template>

      <template #cell(is_active)="{ item }">
        <span
          :class="[
            'px-2.5 py-1 text-xs font-bold rounded-full border',
            item.is_active
              ? 'bg-green-50 text-green-700 border-green-200'
              : 'bg-red-50 text-red-700 border-red-200',
          ]"
        >
          {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-2">
          <button
            @click="openAssignModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-green-50 hover:border-green-200 text-green-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center"
            title="Assign PIC Guru"
          >
            <Icon icon="mdi:account-star-outline" class="w-4 h-4" />
          </button>
          <button
            @click="openModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center"
            title="Edit"
          >
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>
          <button
            @click="promptDelete(item)"
            :disabled="item.student_count > 0"
            :class="[
              'px-3 py-2 font-semibold rounded-lg shadow-md transition-colors flex items-center',
              item.student_count > 0
                ? 'bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed'
                : 'bg-brand-red hover:bg-brand-orange text-white',
            ]"
            :title="item.student_count > 0 ? 'Tidak bisa hapus: masih ada siswa terdaftar' : 'Hapus Eskul'"
          >
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <!-- Modal Create/Edit -->
    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler'"
      @close="isModalOpen = false"
    >
      <form id="eskulForm" @submit.prevent="saveEskul" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Eskul</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Contoh: Pramuka, Basket, dll"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Deskripsi singkat tentang eskul ini..."
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none transition-colors resize-none"
          ></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input
            v-model="form.is_active"
            type="checkbox"
            id="is_active"
            class="w-4 h-4 text-brand-red border-gray-300 rounded focus:ring-brand-red"
          />
          <label for="is_active" class="text-sm font-semibold text-gray-700">Aktif</label>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isModalOpen = false"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            form="eskulForm"
            :disabled="isSaving"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
          >
            {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- Modal Assign PIC Guru -->
    <BaseModal
      :isOpen="isAssignModalOpen"
      title="Assign Guru Penanggung Jawab"
      @close="isAssignModalOpen = false"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Eskul</label>
          <input
            :value="assignTarget?.name"
            type="text"
            disabled
            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Guru PIC</label>
          <BaseSelect
            v-model="assignTeacherId"
            :options="teacherOptions"
            placeholder="Pilih guru PIC (opsional)"
            searchable
          />
          <p class="text-xs text-gray-500 mt-1">Kosongkan jika ingin menghapus PIC saat ini.</p>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isAssignModalOpen = false"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            @click="saveAssignTeacher"
            :disabled="isSaving"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm"
          >
            {{ isSaving ? 'Menyimpan...' : 'Simpan PIC' }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- Confirm Delete -->
    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Ekstrakurikuler?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDelete"
      @cancel="confirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { eskulService } from '../../services/modules/admin/eskulService';
import BaseTable from '../../components/BaseTable.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseSelect from '../../components/BaseSelect.vue';
import ConfirmModal from '../../components/ConfirmModal.vue';

const toastStore = useToastStore();

const eskuls = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref(null);

const isAssignModalOpen = ref(false);
const assignTarget = ref(null);
const assignTeacherId = ref(null);
const teacherOptions = ref([]);

const eskulDeadline = ref('');
const isSavingDeadline = ref(false);
const selectedAcademicYearId = ref(null);

const form = reactive({
  name: '',
  description: '',
  is_active: true,
});

const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  message: '',
  targetId: null,
});

const tableColumns = [
  { key: 'name', label: 'Nama Eskul' },
  { key: 'teacher_name', label: 'PIC Guru' },
  { key: 'student_count', label: 'Siswa', align: 'center' },
  { key: 'is_active', label: 'Status', align: 'center' },
  { key: 'actions', label: 'Aksi', align: 'center' },
];

const fetchData = async () => {
  isLoading.value = true;
  try {
    const response = await eskulService.getAll();
    eskuls.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data eskul.');
  } finally {
    isLoading.value = false;
  }
};

const fetchTeacherOptions = async () => {
  try {
    const response = await eskulService.getTeacherOptions();
    teacherOptions.value = response.data.data;
  } catch {
    toastStore.error('Gagal memuat data guru.');
  }
};

const openModal = (item = null) => {
  isEditing.value = !!item;
  if (item) {
    selectedId.value = item.id;
    form.name = item.name;
    form.description = item.description || '';
    form.is_active = item.is_active;
  } else {
    selectedId.value = null;
    form.name = '';
    form.description = '';
    form.is_active = true;
  }
  isModalOpen.value = true;
};

const saveEskul = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      const res = await eskulService.update(selectedId.value, { ...form });
      toastStore.success(res.data.message || 'Eskul berhasil diperbarui.');
    } else {
      const res = await eskulService.create({ ...form });
      toastStore.success(res.data.message || 'Eskul berhasil ditambahkan.');
    }
    isModalOpen.value = false;
    fetchData();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan data eskul.');
  } finally {
    isSaving.value = false;
  }
};

const promptDelete = (item) => {
  confirmModal.targetId = item.id;
  confirmModal.message = `Hapus eskul "${item.name}"? Tindakan ini tidak dapat dibatalkan.`;
  confirmModal.isOpen = true;
};

const executeDelete = async () => {
  confirmModal.isLoading = true;
  try {
    await eskulService.delete(confirmModal.targetId);
    toastStore.success('Eskul berhasil dihapus.');
    confirmModal.isOpen = false;
    fetchData();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menghapus eskul.');
  } finally {
    confirmModal.isLoading = false;
  }
};

const openAssignModal = (item) => {
  assignTarget.value = item;
  assignTeacherId.value = item.teacher_id;
  isAssignModalOpen.value = true;
};

const saveAssignTeacher = async () => {
  isSaving.value = true;
  try {
    const res = await eskulService.assignTeacher(assignTarget.value.id, assignTeacherId.value);
    toastStore.success(res.data.message || 'PIC Guru berhasil diperbarui.');
    isAssignModalOpen.value = false;
    fetchData();
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal memperbarui PIC Guru.');
  } finally {
    isSaving.value = false;
  }
};

const fetchActiveYearAndDeadline = async () => {
  try {
    const yearRes = await import('../../services/api').then(m => m.default.get('/v1/admin/academic-years'));
    const years = yearRes.data?.data || yearRes.data || [];
    const activeYear = Array.isArray(years) ? years.find(y => y.is_active) : null;
    if (activeYear) {
      selectedAcademicYearId.value = activeYear.id;
      const deadlineRes = await eskulService.getDeadline(activeYear.id);
      eskulDeadline.value = deadlineRes.data?.data?.deadline || '';
    }
  } catch {
    // ignore
  }
};

const saveDeadline = async () => {
  if (!selectedAcademicYearId.value) {
    toastStore.error('Tidak ada tahun ajaran aktif.');
    return;
  }
  isSavingDeadline.value = true;
  try {
    await eskulService.updateDeadline(selectedAcademicYearId.value, eskulDeadline.value || null);
    toastStore.success('Deadline pendaftaran eskul berhasil disimpan.');
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal menyimpan deadline.');
  } finally {
    isSavingDeadline.value = false;
  }
};

onMounted(() => {
  fetchData();
  fetchTeacherOptions();
  fetchActiveYearAndDeadline();
});
</script>
