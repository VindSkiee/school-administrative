---
name: vue3-development
description: "Use when building or modifying Vue 3 frontend pages, components, services, or router in EduPlatform. Triggers on creating admin/teacher/student pages, BaseTable/BaseModal/BaseSelect usage, API service patterns, and Vue Composition API patterns."
---

# Vue 3 Frontend Development (EduPlatform)

## Project Conventions

### File Structure

```
sms-frontend/src/
├── pages/{role}/           # One file = one route
├── components/             # Reusable: BaseTable, BaseModal, BaseSelect, ConfirmModal
├── services/api.js         # Axios instance + interceptors
├── services/modules/{role}/ # API service per domain (userService.js, etc.)
├── stores/                 # Pinia: auth.js, toast.js
├── layouts/MainLayout.vue  # App shell + sidebar
└── router/index.js         # Route definitions + guards
```

### Script Setup (Mandatory)

Always use `<script setup>`:

```vue
<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';
import BaseTable from '../../components/BaseTable.vue';

const toastStore = useToastStore();
const router = useRouter();
</script>
```

## Page Pattern (CRUD)

Follow `SubjectManagement.vue` as reference:

```vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">Title</h1>
        <p class="text-gray-500 text-sm mt-1">Description</p>
      </div>
      <button @click="openModal()" class="bg-brand-red hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah [Item]
      </button>
    </div>

    <!-- Table -->
    <BaseTable :columns="tableColumns" :data="items" :isLoading="isLoading" :pagination="paginationMeta" @page-change="fetchData" emptyMessage="Belum ada data.">
      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-2">
          <button @click="openModal(item)" class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center" title="Edit">
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>
          <button @click="promptDelete(item)" class="px-3 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex items-center" title="Hapus">
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <!-- Modal -->
    <BaseModal :isOpen="isModalOpen" :title="isEditing ? 'Edit' : 'Tambah'" @close="isModalOpen = false">
      <form id="itemForm" @submit.prevent="saveItem" class="space-y-4">
        <!-- fields -->
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="isModalOpen = false" class="px-5 py-2.5 bg-white border hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors">Batal</button>
          <button type="submit" form="itemForm" :disabled="isSaving" class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 transition-colors shadow-sm">
            {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- Confirm Delete -->
    <ConfirmModal :isOpen="confirmModal.isOpen" :isLoading="confirmModal.isLoading" title="Hapus?" :message="confirmModal.message" confirmText="Ya, Hapus!" @confirm="executeDelete" @cancel="confirmModal.isOpen = false" />
  </div>
</template>
```

## Script Pattern

```javascript
// --- STATE ---
const items = ref([]);
const paginationMeta = reactive({ total: 0, current_page: 1, last_page: 1, per_page: 100 });
const isLoading = ref(true);
const isSaving = ref(false);
const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref(null);
const form = reactive({ field1: '', field2: '' });
const confirmModal = reactive({ isOpen: false, isLoading: false, message: '', targetId: null });

// --- TABLE COLUMNS ---
const tableColumns = [
  { key: 'field1', label: 'Label' },
  { key: 'actions', label: 'Aksi', align: 'center' },
];

// --- FETCH ---
const fetchData = async (page = 1) => {
  isLoading.value = true;
  try {
    const response = await service.getAll({ page });
    items.value = response.data.data;
    Object.assign(paginationMeta, {
      total: response.data.total,
      current_page: response.data.current_page,
      last_page: response.data.last_page,
    });
  } catch (error) {
    toastStore.error('Gagal memuat data.');
  } finally {
    isLoading.value = false;
  }
};

// --- MODAL ---
const openModal = (item = null) => {
  isEditing.value = !!item;
  if (item) {
    selectedId.value = item.id;
    form.field1 = item.field1;
  } else {
    selectedId.value = null;
    form.field1 = '';
  }
  isModalOpen.value = true;
};

// --- SAVE ---
const saveItem = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      const res = await service.update(selectedId.value, { ...form });
      toastStore.success(res.data.message || 'Berhasil diperbarui.');
    } else {
      const res = await service.create({ ...form });
      toastStore.success(res.data.message || 'Berhasil ditambahkan.');
    }
    isModalOpen.value = false;
    fetchData(paginationMeta.current_page);
  } catch (error) {
    toastStore.error(error.response?.data?.message || error.response?.data?.error || 'Gagal menyimpan.');
  } finally {
    isSaving.value = false;
  }
};

// --- DELETE ---
const promptDelete = (item) => {
  confirmModal.targetId = item.id;
  confirmModal.message = `Hapus "${item.name}"?`;
  confirmModal.isOpen = true;
};

const executeDelete = async () => {
  confirmModal.isLoading = true;
  try {
    await service.delete(confirmModal.targetId);
    toastStore.success('Berhasil dihapus.');
    if (items.value.length === 1 && paginationMeta.current_page > 1) {
      fetchData(paginationMeta.current_page - 1);
    } else {
      fetchData(paginationMeta.current_page);
    }
  } catch (error) {
    toastStore.error(error.response?.data?.error || 'Gagal menghapus.');
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

onMounted(() => fetchData());
```

## Service Pattern

File: `services/modules/{role}/{name}Service.js`

```javascript
import api from '../../api';

export const nameService = {
  getAll(params = {}) {
    return api.get('/v1/{role}/{endpoint}', { params });
  },
  getById(id) {
    return api.get(`/v1/{role}/{endpoint}/${id}`);
  },
  create(payload) {
    return api.post('/v1/{role}/{endpoint}', payload);
  },
  update(id, payload) {
    return api.put(`/v1/{role}/{endpoint}/${id}`, payload);
  },
  delete(id) {
    return api.delete(`/v1/{role}/${endpoint}/${id}`);
  },
};
```

## Component Props Reference

### BaseTable

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | Array | required | `[{ key, label, align? }]` |
| `data` | Array | required | Row data |
| `isLoading` | Boolean | false | Show loading spinner |
| `pagination` | Object | null | `{ total, current_page, last_page }` |
| `emptyMessage` | String | 'No data' | Empty state text |

Slot: `#cell({key})="{ item }"` for custom cell rendering.

### BaseModal

| Prop | Type | Default |
|------|------|---------|
| `isOpen` | Boolean | required |
| `title` | String | required |
| `maxWidth` | String | 'md' |
| `isPersistent` | Boolean | false |

Events: `@close`. Slots: default (body), `#footer`.

### BaseSelect

| Prop | Type | Default |
|------|------|---------|
| `modelValue` | any | required |
| `options` | Array | required |
| `placeholder` | String | 'Select...' |
| `disabled` | Boolean | false |
| `searchable` | Boolean | false |

Events: `@update:modelValue`.

### ConfirmModal

| Prop | Type | Default |
|------|------|---------|
| `isOpen` | Boolean | required |
| `title` | String | required |
| `message` | String | '' |
| `confirmText` | String | 'Confirm' |
| `isLoading` | Boolean | false |

Events: `@confirm`, `@cancel`.

## Router Pattern

```javascript
{
  path: '/admin',
  component: MainLayout,
  meta: { requiresAuth: true, role: 'admin' },
  children: [
    {
      path: 'holidays',
      name: 'AdminHolidays',
      component: () => import('../pages/admin/HolidayManagement.vue'),
    },
  ],
}
```

## Sidebar Nav Pattern

In `MainLayout.vue` `adminNav` array:

```javascript
{
  name: 'Hari Libur',
  path: '/admin/holidays',
  icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
}
```

## Styling Rules

- Brand colors: `brand-red` (#E02E2B), `brand-orange` (#C66716)
- Cards: `bg-white rounded-2xl shadow-sm border border-gray-100 p-6`
- Table wrapper: `rounded-xl shadow-sm border border-gray-200`
- Form inputs: `px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-brand-red focus:border-brand-red`
- Buttons primary: `bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg`
- Font: `font-serif` for headings, `font-sans` for body
- Icons: `@iconify/vue` with `Icon` component

## Error Handling

```javascript
// Always use this pattern
try {
  const res = await service.create(payload);
  toastStore.success(res.data.message || 'Berhasil.');
} catch (error) {
  const msg = error.response?.data?.message || error.response?.data?.error || 'Gagal menyimpan.';
  toastStore.error(msg);
}
```

## Context7 Integration

For Vue 3, Tailwind CSS v4, or library-specific docs, use Context7 MCP tools:
- `resolve-library-id` → find library
- `get-library-docs` → fetch docs for specific version

Do NOT manually write library documentation in this skill. Context7 handles that.
