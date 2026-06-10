<template>
  <div class="space-y-6">
    <section class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white via-white to-amber-50/60 p-6 shadow-sm">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        
        <div class="max-w-xl">
          <h1 class="text-3xl font-bold font-serif text-gray-800">
            Log Aktivitas Sistem
          </h1>
          <p class="mt-2 text-sm text-gray-500">
            Pantau seluruh rekam jejak aksi pengguna di dalam sistem.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
          <div class="w-full sm:w-64">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
              Cari Aktivitas
            </label>
            <div class="relative">
              <svg class="absolute left-3 top-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input 
                type="text" 
                v-model="searchQuery" 
                placeholder="Cari aktor, aksi, atau ID..." 
                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-sm text-gray-700 shadow-sm outline-none transition-colors focus:border-brand-red focus:ring-1 focus:ring-brand-red"
              />
            </div>
          </div>

          <div class="w-full sm:w-56">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
              Filter Entitas
            </label>
            <BaseSelect
              v-model="selectedModelType"
              :options="modelTypeOptions"
              placeholder="Semua Entitas"
              @update:modelValue="handleFilterChange"
            />
          </div>
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
            {{ item.user.name || "Tanpa Nama" }}
          </div>
          <span
            v-if="resolveUserRole(item.user)"
            class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
            :class="getRoleBadgeClass(resolveUserRole(item.user))"
          >
            {{ formatRoleLabel(resolveUserRole(item.user)) }}
          </span>
        </div>
        <div v-else class="text-sm font-medium text-gray-600">
          Sistem / Auto
        </div>
      </template>

      <template #cell(action_resource)="{ item }">
        <div class="space-y-2 whitespace-normal">
          <span
            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold tracking-wide"
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
        <span class="text-sm font-medium text-gray-700">
          {{ item.ip_address || "-" }}
        </span>
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

        <div v-if="selectedLog.action === 'created' || selectedLog.action === 'deleted'" class="space-y-3">
          <div>
            <p class="text-sm font-semibold text-gray-700">
              {{ selectedLog.action === "created" ? "Data Baru Ditambahkan" : "Data Dihapus" }}
            </p>
            <p class="text-xs text-gray-500">Rincian informasi data.</p>
          </div>

          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 font-semibold text-gray-600 w-1/3">Nama Kolom</th>
                  <th class="px-4 py-2 font-semibold text-gray-600">Nilai / Isi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="(value, key) in filterSystemFields(selectedLog.action === 'created' ? selectedLog.new_values : selectedLog.old_values)" :key="key">
                  <td class="px-4 py-2.5 font-medium text-gray-700 capitalize">{{ formatColumnName(key) }}</td>
                  <td class="px-4 py-2.5 text-gray-600">{{ formatCellValue(value) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div>
            <p class="text-sm font-semibold text-gray-700">Rincian Perubahan Data</p>
            <p class="text-xs text-gray-500">Hanya menampilkan kolom yang mengalami perubahan.</p>
          </div>

          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 font-semibold text-gray-600 w-1/4">Nama Kolom</th>
                  <th class="px-4 py-2 font-semibold text-gray-600 w-3/8">Data Sebelumnya</th>
                  <th class="px-4 py-2 font-semibold text-gray-600 w-3/8">Menjadi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="diff in getChangedFields(selectedLog.old_values, selectedLog.new_values)" :key="diff.key" class="hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-800 capitalize">{{ formatColumnName(diff.key) }}</td>
                  <td class="px-4 py-3 text-red-600 bg-red-50/50">
                    <span class="line-through opacity-70">{{ formatCellValue(diff.old) }}</span>
                  </td>
                  <td class="px-4 py-3 text-green-700 font-semibold bg-green-50/50">
                    {{ formatCellValue(diff.new) }}
                  </td>
                </tr>

                <tr v-if="getChangedFields(selectedLog.old_values, selectedLog.new_values).length === 0">
                  <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                    Tidak ada perubahan data yang signifikan (selain timestamp).
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import BaseModal from "../../components/BaseModal.vue";
import BaseTable from "../../components/BaseTable.vue";
import BaseSelect from "../../components/BaseSelect.vue";
import { activityLogService } from "../../services/modules/admin/activityLogService";
import { useToastStore } from "../../stores/toast";

const toastStore = useToastStore();

const tableColumns = [
  { key: "time", label: "Waktu" },
  { key: "actor", label: "Aktor", align: "center" },
  { key: "action_resource", label: "Aksi & Resource", align: "center" },
  { key: "ip_address", label: "IP Address", align: "center" },
  { key: "detail", label: "Detail", align: "center" },
];

const translateModelName = (modelName) => {
  const dictionary = {
    User: "Pengguna",
    Student: "Siswa",
    Teacher: "Guru",
    SchoolClass: "Kelas",
    Subject: "Mata Pelajaran",
    Schedule: "Jadwal Pelajaran",
    AcademicYear: "Tahun Ajaran",
    Grade: "Nilai Akademik",
  };
  return dictionary[modelName] || modelName;
};

const modelTypeOptions = [
  { value: "User", label: "Pengguna" },
  { value: "Student", label: "Siswa" },
  { value: "Teacher", label: "Guru" },
  { value: "SchoolClass", label: "Kelas" },
  { value: "Subject", label: "Mata Pelajaran" },
  { value: "Schedule", label: "Jadwal Pelajaran" },
  { value: "AcademicYear", label: "Tahun Ajaran" },
];

const emptyMessage = "Belum ada rekam aktivitas yang ditemukan.";

const activityLogs = ref([]);
const isLoading = ref(false);

// State untuk Filter & Search
const selectedModelType = ref("");
const searchQuery = ref("");
let searchTimeout = null;

const selectedLog = ref(null);
const isDetailModalOpen = ref(false);

const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 10,
});

const detailModalTitle = computed(() => {
  if (!selectedLog.value) return "Detail Perubahan Data";
  return `Detail Perubahan Data (Target: ${getResourceType(selectedLog.value.loggable_type) || "Entitas"} #${selectedLog.value.loggable_id ?? "-"})`;
});

const ignoredFields = ["id", "created_at", "updated_at", "deleted_at", "remember_token", "password", "email_verified_at"];

const filterSystemFields = (dataObj) => {
  if (!dataObj) return {};
  const filtered = {};
  for (const key in dataObj) {
    if (!ignoredFields.includes(key)) filtered[key] = dataObj[key];
  }
  return filtered;
};

const getChangedFields = (oldVals, newVals) => {
  if (!oldVals || !newVals) return [];
  const changes = [];
  for (const key in newVals) {
    if (ignoredFields.includes(key)) continue;
    if (String(oldVals[key]) !== String(newVals[key])) {
      changes.push({ key: key, old: oldVals[key], new: newVals[key] });
    }
  }
  return changes;
};

const formatColumnName = (key) => key ? key.replace(/_/g, " ") : "";

const formatCellValue = (value) => {
  if (value === null || value === undefined || value === "") return "- (Kosong) -";
  if (typeof value === "object") return JSON.stringify(value);
  return value;
};

const fetchActivityLogs = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = {
      page,
      model_type: selectedModelType.value || undefined,
      search: searchQuery.value || undefined, // Mengirimkan parameter pencarian
    };

    const response = await activityLogService.getAll(params);
    const rawPayload = response.data ?? {};
    const payload = Array.isArray(rawPayload.data) ? rawPayload : (rawPayload.data ?? rawPayload);
    const items = Array.isArray(payload.data) ? payload.data : Array.isArray(payload) ? payload : [];

    activityLogs.value = items;
    paginationMeta.total = payload.total ?? items.length;
    paginationMeta.current_page = payload.current_page ?? page;
    paginationMeta.last_page = payload.last_page ?? 1;
    paginationMeta.per_page = payload.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal mengambil rekam aktivitas sistem.");
  } finally {
    isLoading.value = false;
  }
};

const handleFilterChange = () => {
  fetchActivityLogs(1);
};

// Logika Debounce menggunakan Watch
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchActivityLogs(1);
  }, 500); // Tunggu 500ms setelah user berhenti mengetik
});

const openDetailModal = (item) => {
  selectedLog.value = item;
  isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
  isDetailModalOpen.value = false;
  selectedLog.value = null;
};

const getResourceType = (loggableType) => {
  if (!loggableType) return "";
  const rawModel = String(loggableType).split(/[\\/]/).filter(Boolean).pop() || "";
  return translateModelName(rawModel);
};

const formatResourceLabel = (item) => {
  const resourceType = getResourceType(item.loggable_type) || "Entitas";
  const resourceId = item.loggable_id ?? "-";
  return `Target: ${resourceType} #${resourceId}`;
};

const formatActionLabel = (action) => {
  const normalized = String(action || "").toLowerCase();
  const map = { created: "Membuat", updated: "Memperbarui", deleted: "Menghapus" };
  return map[normalized] || action;
};

const getActionBadgeClass = (action) => {
  const normalized = String(action || "").toLowerCase();
  const map = {
    created: "border-emerald-200 bg-emerald-50 text-emerald-700",
    updated: "border-amber-200 bg-amber-50 text-amber-700",
    deleted: "border-rose-200 bg-rose-50 text-rose-700",
  };
  return map[normalized] || "border-gray-200 bg-gray-50 text-gray-700";
};

const formatRoleLabel = (role) => {
  if (!role) return "-";
  return String(role).replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
};

const getRoleBadgeClass = (role) => {
  const normalized = String(role || "").toLowerCase();
  const map = {
    admin: "border-rose-200 bg-rose-50 text-rose-700",
    teacher: "border-sky-200 bg-sky-50 text-sky-700",
    student: "border-emerald-200 bg-emerald-50 text-emerald-700",
    principal: "border-amber-200 bg-amber-50 text-amber-700",
  };
  return map[normalized] || "border-gray-200 bg-gray-50 text-gray-700";
};

const resolveUserRole = (user) => user?.role || user?.roles?.[0]?.name || "";

const formatWibDateTime = (value) => {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "-";

  const datePart = new Intl.DateTimeFormat("id-ID", {
    day: "numeric", month: "long", year: "numeric", timeZone: "Asia/Jakarta",
  }).format(date);

  const timeParts = new Intl.DateTimeFormat("id-ID", {
    hour: "2-digit", minute: "2-digit", hour12: false, timeZone: "Asia/Jakarta",
  }).formatToParts(date);

  const hours = timeParts.find((part) => part.type === "hour")?.value || "00";
  const minutes = timeParts.find((part) => part.type === "minute")?.value || "00";

  return `${datePart}, ${hours}:${minutes} WIB`;
};

onMounted(() => {
  fetchActivityLogs();
});
</script>