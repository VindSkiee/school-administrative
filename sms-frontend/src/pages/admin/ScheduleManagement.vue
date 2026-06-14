<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Jadwal Pelajaran
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola alokasi waktu mengajar guru dan mata pelajaran untuk setiap kelas.
        </p>
      </div>

      <div class="flex flex-col items-end sm:flex-row w-full sm:w-auto gap-3">
        <div class="w-full sm:w-56">
          <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">
            Pilih Tahun Ajaran
          </label>
          <BaseSelect
            v-model="selectedAcademicYearId"
            :options="academicYearOptions"
            placeholder="Pilih Tahun Ajaran"
          />
        </div>
        <div class="w-full sm:w-56">
          <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">
            Pilih Kelas
          </label>
          <BaseSelect
            v-model="selectedClassFilter"
            :options="classOptions"
            placeholder="Semua Kelas"
            :searchable="true"
          />
        </div>

        <button
          @click="openModal()"
          class="bg-brand-red h-10 w-full sm:w-auto hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center justify-center whitespace-nowrap"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Buat Jadwal
        </button>
      </div>
    </div>

    <BaseTable
      :columns="tableColumns"
      :data="schedules"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchSchedules"
      emptyMessage="Belum ada jadwal yang terdaftar untuk filter ini."
    >
      <template #cell(day_time)="{ item }">
        <div class="flex flex-col">
          <span class="font-bold text-gray-800 uppercase tracking-wide text-xs">
            {{ translateDay(item.day_of_week) }}
          </span>
          <span class="text-sm text-gray-500 flex items-center mt-0.5">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}
          </span>
        </div>
      </template>

      <template #cell(class_subject)="{ item }">
        <div class="flex flex-col">
          <span class="font-bold text-brand-red">{{ item.subject?.name }}</span>
          <span class="text-xs font-semibold text-gray-500 mt-0.5 bg-gray-100 px-2 py-0.5 rounded w-max">
            Kelas {{ item.school_class?.name }}
          </span>
        </div>
      </template>

      <template #cell(teacher)="{ item }">
        <div class="flex items-center">
          <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-2">
            {{ item.teacher?.user?.name.charAt(0) }}
          </div>
          <router-link
            v-if="item.teacher?.user?.id"
            :to="{ name: 'Detail Pengguna', params: { id: item.teacher.user.id } }"
            class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
          >
            {{ item.teacher?.user?.name }}
          </router-link>
          <span v-else class="text-sm font-medium text-gray-700">{{ item.teacher?.user?.name || '-' }}</span>
        </div>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-2">
          <button @click="openModal(item)" class="p-2 bg-white border border-gray-200 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors shadow-sm" title="Edit Jadwal">
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>
          <button @click="promptDelete(item)" class="p-2 bg-brand-red hover:bg-brand-orange text-white rounded-lg shadow-md transition-colors" title="Hapus Jadwal">
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran'"
      @close="isModalOpen = false"
    >
      <form id="scheduleForm" @submit.prevent="saveSchedule" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kelas <span class="text-red-500">*</span></label>
            <BaseSelect
              v-model="form.class_id"
              :options="classOptions"
              placeholder="Pilih Kelas"
              :searchable="true"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mata Pelajaran <span class="text-red-500">*</span></label>
            <BaseSelect
              v-model="form.subject_id"
              :options="subjectOptions"
              placeholder="Pilih Mapel"
              :searchable="true"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Guru Pengajar <span class="text-red-500">*</span></label>
          <BaseSelect
            v-model="form.teacher_id"
            :options="teacherOptions"
            placeholder="Pilih Guru"
            :searchable="true"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Hari <span class="text-red-500">*</span></label>
            <BaseSelect
              v-model="form.day_of_week"
              :options="dayOptions"
              placeholder="Pilih"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jam Mulai <span class="text-red-500">*</span></label>
            <input v-model="form.start_time" type="time" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none"/>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jam Selesai <span class="text-red-500">*</span></label>
            <input v-model="form.end_time" type="time" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none"/>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="isModalOpen = false" class="px-5 py-2.5 bg-white border hover:bg-gray-50 text-gray-700 font-semibold rounded-lg">Batal</button>
          <button type="submit" form="scheduleForm" :disabled="isSaving" class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 shadow-sm">
            {{ isSaving ? "Menyimpan..." : "Simpan Jadwal" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Jadwal?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDelete"
      @cancel="confirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { useToastStore } from '../../stores/toast';
import { scheduleService } from '../../services/modules/admin/scheduleService';
import api from '../../services/api.js'; // Untuk fetch dropdown data pendukung

import BaseTable from '../../components/BaseTable.vue';
import BaseSelect from '../../components/BaseSelect.vue';
import BaseModal from '../../components/BaseModal.vue';
import ConfirmModal from '../../components/ConfirmModal.vue';

const toastStore = useToastStore();

const tableColumns = [
  { key: 'day_time', label: 'Hari & Waktu' },
  { key: 'class_subject', label: 'Pelajaran' },
  { key: 'teacher', label: 'Guru Pengajar' },
  { key: 'actions', label: 'Aksi', align: 'center' },
];

const schedules = ref([]);
const paginationMeta = reactive({ total: 0, current_page: 1, last_page: 1, per_page: 100 });
const isLoading = ref(true);
const isSaving = ref(false);

const selectedClassFilter = ref(''); // Filter untuk List Table
const selectedAcademicYearId = ref(''); // Tahun Ajaran terpilih (default: aktif)

// Data Master untuk Dropdown
const classes = ref([]);
const subjects = ref([]);
const teachers = ref([]);
const academicYears = ref([]);

const academicYearOptions = computed(() =>
  academicYears.value.map((ay) => ({
    value: ay.id,
    label: `${ay.name} — ${ay.semester === 'odd' ? 'Ganjil' : 'Genap'}${ay.is_active ? ' (Aktif)' : ''}`,
  })),
);

const classOptions = computed(() =>
  classes.value.map((cls) => ({ value: cls.id, label: `Kelas ${cls.name}` })),
);

const subjectOptions = computed(() =>
  subjects.value.map((sub) => ({ value: sub.id, label: sub.name })),
);

const teacherOptions = computed(() =>
  teachers.value.map((teacher) => ({
    value: teacher.id,
    label: teacher.name,
  })),
);

const dayOptions = [
  { value: 'monday', label: 'Senin' },
  { value: 'tuesday', label: 'Selasa' },
  { value: 'wednesday', label: 'Rabu' },
  { value: 'thursday', label: 'Kamis' },
  { value: 'friday', label: 'Jumat' },
  { value: 'saturday', label: 'Sabtu' },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref(null);

const form = reactive({ class_id: '', subject_id: '', teacher_id: '', day_of_week: '', start_time: '', end_time: '' });

const confirmModal = reactive({ isOpen: false, isLoading: false, message: '', targetId: null });

// --- UTILITIES ---
const translateDay = (day) => {
  const days = { monday: 'Senin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Kamis', friday: 'Jumat', saturday: 'Sabtu' };
  return days[day] || day;
};

const formatTime = (time) => {
  if (!time) return '';
  return time.substring(0, 5); // Mengubah '08:00:00' menjadi '08:00'
};

// --- API CALLS ---
const fetchMasterData = async () => {
  try {
    // Kita panggil endpoint master secara paralel agar lebih cepat
    const [resClass, resSubject, resTeacher, resYears] = await Promise.all([
      api.get('/v1/admin/classes?per_page=100'), // Asumsi kita melonggarkan per_page untuk dropdown
      api.get('/v1/admin/subjects?per_page=100'),
      api.get('/v1/admin/users?role=teacher&per_page=all'),
      api.get('/v1/admin/academic-years'),
    ]);
    
    classes.value = resClass.data.data || resClass.data;
    subjects.value = resSubject.data.data || resSubject.data;
    const teacherData = resTeacher.data.data || resTeacher.data;
    teachers.value = teacherData.map((teacher) => ({
      id: teacher.id,
      name: teacher.name,
    }));

    const yearsData = resYears.data.data || resYears.data;
    academicYears.value = yearsData;
    // Default ke tahun ajaran aktif
    const activeYear = yearsData.find((y) => y.is_active);
    if (activeYear) {
      selectedAcademicYearId.value = activeYear.id;
    }
  } catch (error) {
    toastStore.error('Gagal memuat data pendukung (Kelas/Mapel/Guru/Tahun Ajaran).');
  }
};

const fetchSchedules = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = { page: page, per_page: paginationMeta.per_page };
    if (selectedClassFilter.value) {
      params.class_id = selectedClassFilter.value;
    }
    if (selectedAcademicYearId.value) {
      params.academic_year_id = selectedAcademicYearId.value;
    }

    const response = await scheduleService.getAll(params);
    schedules.value = response.data.data;
    paginationMeta.total = response.data.total;
    paginationMeta.current_page = response.data.current_page;
    paginationMeta.last_page = response.data.last_page;
    paginationMeta.per_page = response.data.per_page;
  } catch (error) {
    toastStore.error('Gagal memuat jadwal pelajaran.');
  } finally {
    isLoading.value = false;
  }
};

const openModal = (schedule = null) => {
  isEditing.value = !!schedule;
  if (schedule) {
    selectedId.value = schedule.id;
    form.class_id = schedule.class_id;
    form.subject_id = schedule.subject_id;
    form.teacher_id = schedule.teacher_id;
    form.day_of_week = schedule.day_of_week;
    form.start_time = formatTime(schedule.start_time);
    form.end_time = formatTime(schedule.end_time);
  } else {
    selectedId.value = null;
    form.class_id = selectedClassFilter.value || ''; // Otomatis terisi jika filter aktif
    form.subject_id = '';
    form.teacher_id = '';
    form.day_of_week = '';
    form.start_time = '';
    form.end_time = '';
  }
  isModalOpen.value = true;
};

const saveSchedule = async () => {
  // Validasi Jam di Frontend
  if (!form.class_id || !form.subject_id || !form.teacher_id || !form.day_of_week) {
    toastStore.error('Lengkapi semua field wajib terlebih dahulu.');
    return;
  }
  if (form.start_time >= form.end_time) {
    toastStore.error('Jam mulai harus lebih awal dari jam selesai!');
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await scheduleService.update(selectedId.value, form);
      toastStore.success('Jadwal diperbarui.');
    } else {
      const payload = { ...form, academic_year_id: selectedAcademicYearId.value };
      await scheduleService.create(payload);
      toastStore.success('Jadwal baru ditambahkan.');
    }
    isModalOpen.value = false;
    fetchSchedules(paginationMeta.current_page);
  } catch (error) {
    // Menampilkan error validasi clash dari backend
    toastStore.error(error.response?.data?.error || 'Gagal menyimpan jadwal.');
  } finally {
    isSaving.value = false;
  }
};

const promptDelete = (schedule) => {
  confirmModal.targetId = schedule.id;
  confirmModal.message = `Hapus jadwal ${schedule.subject?.name} untuk kelas ${schedule.school_class?.name}?`;
  confirmModal.isOpen = true;
};

const executeDelete = async () => {
  confirmModal.isLoading = true;
  try {
    await scheduleService.delete(confirmModal.targetId);
    toastStore.success('Jadwal berhasil dihapus.');
    fetchSchedules(paginationMeta.current_page);
  } catch (error) {
    toastStore.error(error.response?.data?.error || 'Gagal menghapus jadwal.');
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

onMounted(() => {
  fetchMasterData();
  fetchSchedules();
});

watch(selectedClassFilter, () => {
  fetchSchedules(1);
});

watch(selectedAcademicYearId, () => {
  fetchSchedules(1);
});
</script>