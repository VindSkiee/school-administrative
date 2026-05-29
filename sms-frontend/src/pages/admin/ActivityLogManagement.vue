<template>
  <div class="space-y-6">
    <section class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white via-white to-amber-50/60 p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
          <h1 class="text-3xl font-bold font-serif text-gray-800">Log Aktivitas Sistem</h1>
          <p class="mt-2 text-sm text-gray-500">Pantau seluruh rekam jejak aksi pengguna di dalam sistem.</p>
        </div>

        <div class="w-full lg:w-72">
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Filter Entitas</label>
          <select
            v-model="selectedModelType"
            @change="handleFilterChange"
            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm outline-none transition-colors focus:border-brand-red focus:ring-1 focus:ring-brand-red"
          >
            <option v-for="option in modelTypeOptions" :key="option.value || 'all'" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>
    </section>

    <BaseTable
      :columns="tableColumns"
      :data="activityLogs"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      :emptyMessage="emptyMessage"
      @page-change="fetchActivityLogs"
    >
      <template #cell(time)="{ item }">
        <div class="whitespace-normal text-sm text-gray-700">
          {{ formatWibDateTime(item.created_at) }}
        </div>
      </template>

      <template #cell(actor)="{ item }">
        <div v-if="item.user" class="space-y-1">
          <div class="font-semibold text-gray-800">
            {{ item.user.name || 'Tanpa Nama' }}
          </div>
          <span
            v-if="resolveUserRole(item.user)"
            class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
            :class="getRoleBadgeClass(resolveUserRole(item.user))"
          >
            {{ formatRoleLabel(resolveUserRole(item.user)) }}
          </span>
        </div>
        <div v-else class="text-sm font-medium text-gray-600">Sistem / Auto</div>
      </template>

      <template #cell(action_resource)="{ item }">
        <div class="space-y-2 whitespace-normal">
          <span
            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide"
            :class="getActionBadgeClass(item.action)"
          >
            {{ formatActionLabel(item.action) }}
          </span>
          <div class="text-sm text-gray-600">
            {{ formatResourceLabel(item) }}
          </div>
        </div>
      </template>

      <template #cell(ip_address)="{ item }">
        <span class="text-sm font-medium text-gray-700">{{ item.ip_address || '-' }}</span>
      </template>

      <template #cell(detail)="{ item }">
        <div class="flex justify-center">
          <button
            type="button"
            @click="openDetailModal(item)"
            class="inline-flex items-center justify-center rounded-full border border-gray-200 bg-white p-2 text-gray-600 shadow-sm transition-colors hover:border-brand-red hover:text-brand-red"
            title="Lihat detail"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </template>
    </BaseTable>

    <BaseModal
      :isOpen="isDetailModalOpen"
      :title="detailModalTitle"
      maxWidth="2xl"
      @close="closeDetailModal"
    >
      <div v-if="selectedLog" class="space-y-5">
        <div class="flex flex-wrap gap-2 text-xs">
          <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-semibold text-gray-700">
            {{ formatActionLabel(selectedLog.action) }}
          </span>
          <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-semibold text-gray-700">
            {{ formatResourceLabel(selectedLog) }}
          </span>
          <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-semibold text-gray-700">
            {{ formatWibDateTime(selectedLog.created_at) }}
          </span>
          <span v-if="selectedLog.ip_address" class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-semibold text-gray-700">
            IP {{ selectedLog.ip_address }}
          </span>
        </div>

        <div v-if="selectedLog.action === 'created'" class="space-y-3">
          <div>
            <p class="text-sm font-semibold text-gray-700">Data Baru</p>
            <p class="text-xs text-gray-500">Menampilkan payload setelah entitas dibuat.</p>
          </div>
          <pre class="overflow-x-auto rounded-lg bg-gray-800 p-3 text-xs text-green-400">{{ formatJsonPayload(selectedLog.new_values) }}</pre>
        </div>

        <div v-else-if="selectedLog.action === 'deleted'" class="space-y-3">
          <div>
            <p class="text-sm font-semibold text-gray-700">Data Lama</p>
            <p class="text-xs text-gray-500">Menampilkan payload sebelum entitas dihapus.</p>
          </div>
          <pre class="overflow-x-auto rounded-lg bg-gray-800 p-3 text-xs text-green-400">{{ formatJsonPayload(selectedLog.old_values) }}</pre>
        </div>

        <div v-else class="space-y-3">
          <div>
            <p class="text-sm font-semibold text-gray-700">Perbandingan Data</p>
            <p class="text-xs text-gray-500">Kiri data lama, kanan data baru.</p>
          </div>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="space-y-2">
              <p class="text-sm font-semibold text-gray-700">Data Lama (Sebelum)</p>
              <pre class="overflow-x-auto rounded-lg bg-gray-800 p-3 text-xs text-green-400">{{ formatJsonPayload(selectedLog.old_values) }}</pre>
            </div>

            <div class="space-y-2">
              <p class="text-sm font-semibold text-gray-700">Data Baru (Sesudah)</p>
              <pre class="overflow-x-auto rounded-lg bg-gray-800 p-3 text-xs text-green-400">{{ formatJsonPayload(selectedLog.new_values) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseTable from '../../components/BaseTable.vue';
import { activityLogService } from '../../services/modules/admin/activityLogService';
import { useToastStore } from '../../stores/toast';

const toastStore = useToastStore();

const tableColumns = [
  { key: 'time', label: 'Waktu' },
  { key: 'actor', label: 'Aktor' },
  { key: 'action_resource', label: 'Aksi & Resource' },
  { key: 'ip_address', label: 'IP Address', align: 'center' },
  { key: 'detail', label: 'Detail', align: 'center' },
];

const modelTypeOptions = [
  { value: '', label: 'Semua Entitas' },
  { value: 'User', label: 'User' },
  { value: 'Student', label: 'Student' },
  { value: 'Teacher', label: 'Teacher' },
  { value: 'SchoolClass', label: 'SchoolClass' },
  { value: 'Subject', label: 'Subject' },
  { value: 'Schedule', label: 'Schedule' },
];

const emptyMessage = 'Belum ada rekam aktivitas yang ditemukan.';

const activityLogs = ref([]);
const isLoading = ref(false);
const selectedModelType = ref('');
const selectedLog = ref(null);
const isDetailModalOpen = ref(false);

const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 10,
});

const detailModalTitle = computed(() => {
  if (!selectedLog.value) {
    return 'Detail Perubahan Data';
  }

  return `Detail Perubahan Data (Target: ${getResourceType(selectedLog.value.loggable_type) || 'Entitas'} #${selectedLog.value.loggable_id ?? '-'})`;
});

const fetchActivityLogs = async (page = 1) => {
  isLoading.value = true;

  try {
    const params = {
      page,
      model_type: selectedModelType.value || undefined,
    };

    const response = await activityLogService.getAll(params);
    const rawPayload = response.data ?? {};
    const payload = Array.isArray(rawPayload.data) ? rawPayload : (rawPayload.data ?? rawPayload);
    const items = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);

    activityLogs.value = items;
    paginationMeta.total = payload.total ?? items.length;
    paginationMeta.current_page = payload.current_page ?? page;
    paginationMeta.last_page = payload.last_page ?? 1;
    paginationMeta.per_page = payload.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal mengambil rekam aktivitas sistem.');
  } finally {
    isLoading.value = false;
  }
};

const handleFilterChange = () => {
  fetchActivityLogs(1);
};

const openDetailModal = (item) => {
  selectedLog.value = item;
  isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
  isDetailModalOpen.value = false;
  selectedLog.value = null;
};

const parseJsonValue = (value) => {
  if (value === null || value === undefined || value === '') {
    return {};
  }

  if (typeof value === 'object') {
    return value;
  }

  if (typeof value === 'string') {
    try {
      return JSON.parse(value);
    } catch {
      return value;
    }
  }

  return value;
};

const formatJsonPayload = (value) => {
  const parsed = parseJsonValue(value);

  if (typeof parsed === 'string') {
    return parsed;
  }

  try {
    return JSON.stringify(parsed ?? {}, null, 2);
  } catch {
    return String(parsed ?? '');
  }
};

const getResourceType = (loggableType) => {
  if (!loggableType) {
    return '';
  }

  return String(loggableType).split(/[\\/]/).filter(Boolean).pop() || '';
};

const formatResourceLabel = (item) => {
  const resourceType = getResourceType(item.loggable_type) || 'Entitas';
  const resourceId = item.loggable_id ?? '-';
  return `Target: ${resourceType} #${resourceId}`;
};

const formatActionLabel = (action) => {
  const normalized = String(action || '').toLowerCase();
  const map = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
  };

  return map[normalized] || '-';
};

const getActionBadgeClass = (action) => {
  const normalized = String(action || '').toLowerCase();

  const map = {
    created: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    updated: 'border-amber-200 bg-amber-50 text-amber-700',
    deleted: 'border-rose-200 bg-rose-50 text-rose-700',
  };

  return map[normalized] || 'border-gray-200 bg-gray-50 text-gray-700';
};

const formatRoleLabel = (role) => {
  if (!role) {
    return '-';
  }

  return String(role)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

const getRoleBadgeClass = (role) => {
  const normalized = String(role || '').toLowerCase();

  const map = {
    admin: 'border-rose-200 bg-rose-50 text-rose-700',
    teacher: 'border-sky-200 bg-sky-50 text-sky-700',
    student: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    principal: 'border-amber-200 bg-amber-50 text-amber-700',
  };

  return map[normalized] || 'border-gray-200 bg-gray-50 text-gray-700';
};

const resolveUserRole = (user) => user?.role || user?.roles?.[0]?.name || '';

const formatWibDateTime = (value) => {
  if (!value) {
    return '-';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  const datePart = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'Asia/Jakarta',
  }).format(date);

  const timeParts = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
  }).formatToParts(date);

  const hours = timeParts.find((part) => part.type === 'hour')?.value || '00';
  const minutes = timeParts.find((part) => part.type === 'minute')?.value || '00';

  return `${datePart}, ${hours}:${minutes} WIB`;
};

onMounted(() => {
  fetchActivityLogs();
});
</script>