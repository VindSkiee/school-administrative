<template>
  <div class="space-y-6">
    <!-- Header & Actions -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Manajemen Pengguna</h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola data akses Guru, Siswa, dan Kepala Sekolah.
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
        Tambah Pengguna
      </button>
    </div>

    <!-- Filter & Search Bar -->
    <div
      class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4"
    >
      <div class="flex-1 relative">
        <svg
          class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          ></path>
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nama atau email..."
          class="w-full pl-10 pr-4 py-2 border border-gray-200 hover:border-gray-400 hover:bg-gray-50 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none text-sm transition-all duration-200"
        />
      </div>
      <!-- REUSABLE COMPONENT: BaseSelect -->
      <div class="w-full sm:w-48">
        <BaseSelect
          v-model="roleFilter"
          :options="roleOptions"
          placeholder="Semua Peran"
        />
      </div>
    </div>

    <!-- REUSABLE COMPONENT: BaseTable -->
    <BaseTable
      :columns="tableColumns"
      :data="users"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchUsers"
      emptyMessage="Tidak ada data pengguna yang ditemukan."
    >
      <!-- DYNAMIC SLOT: Menimpa cara kolom 'name' dirender -->
      <template #cell(name)="{ item }">
        <div class="flex items-center h-full font-medium text-gray-800">
          <router-link
            :to="{ name: 'Detail Pengguna', params: { id: item.id } }"
            class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
          >
            {{ item.name }}
          </router-link>
          <span
            v-if="item.id === authStore.user?.id"
            class="ml-3 px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded uppercase tracking-wider border border-green-200 leading-none"
          >
            Saya
          </span>
        </div>
      </template>

      <!-- DYNAMIC SLOT: Menimpa cara kolom 'role' dirender -->
      <template #cell(role)="{ item }">
        <span
          :class="roleBadgeColor(item.role)"
          class="px-3 py-1 rounded-full text-xs font-semibold capitalize border"
        >
          {{ formatRoleName(item.role) }}
        </span>
      </template>

      <!-- DYNAMIC SLOT: Menimpa cara kolom 'actions' dirender -->
      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-2">
          <!-- Edit -->
          <button
            @click="openModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center"
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
            v-if="item.id !== authStore.user?.id"
            @click="promptDeleteUser(item)"
            class="px-3 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex items-center"
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
    <!-- REUSABLE COMPONENT: BaseModal -->
    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Pengguna' : 'Tambah Pengguna Baru'"
      @close="closeModal"
    >
      <form id="userForm" @submit.prevent="saveUser" class="space-y-5 mt-1">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Nama Lengkap</label
            >
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="John Doe"
              class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Alamat Email</label
            >
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="john@sekolah.edu"
              class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Peran (Role)</label
          >
          <BaseSelect
            v-model="form.role"
            :options="formRoleOptions"
            :disabled="isEditing"
          />
          <p v-if="isEditing" class="text-xs text-brand-orange mt-1">
            Peran tidak dapat diubah setelah akun dibuat.
          </p>
        </div>

        <!-- CONDITIONAL FIELDS (Transisi Smooth Vertikal) -->
        <div
          class="bg-gray-50 p-4 rounded-xl border border-gray-100 overflow-hidden transition-all duration-300"
        >
          <!-- Field Siswa -->
          <div v-if="form.role === 'student'" class="animate-fade-in space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                >Nomor Induk Siswa Nasional (NISN)</label
              >
              <input
                v-model="form.nisn"
                type="text"
                required
                placeholder="10 digit angka"
                class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-white"
              />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                  >Nomor Induk Siswa (NIS)</label
                >
                <input
                  v-model="form.nis"
                  type="text"
                  required
                  placeholder="NIS siswa"
                  class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-white"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                  >Jenis Kelamin</label
                >
                <BaseSelect
                  v-model="form.gender"
                  :options="genderOptions"
                  placeholder="Pilih jenis kelamin"
                />
              </div>
            </div>
          </div>

          <!-- Field Guru -->
          <div v-else-if="form.role === 'teacher'" class="animate-fade-in">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Nomor Induk Pegawai (NIP)</label
            >
            <input
              v-model="form.nip"
              type="text"
              required
              placeholder="18 digit angka (opsional)"
              class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-white"
            />
          </div>

          <!-- Field Admin -->
          <div v-else-if="form.role === 'admin'" class="animate-fade-in space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                >Nomor Induk Pegawai (NIP)</label
              >
              <input
                v-model="form.nip"
                type="text"
                required
                placeholder="18 digit angka"
                class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-white"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                >Admin Secret Key</label
              >
              <input
                v-model="form.admin_secret_key"
                type="password"
                :required="!isEditing"
                placeholder="Kunci otorisasi level sistem"
                class="w-full text-sm px-4 py-2 border border-brand-red/30 focus:border-brand-red rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-red-50"
              />
              <p class="text-xs text-gray-500 mt-1.5">
                Diperlukan untuk membuat akun dengan akses sistem penuh.
              </p>
            </div>
          </div>

          <!-- Field Principal -->
          <div v-else-if="form.role === 'principal'" class="animate-fade-in">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Nomor Induk Pegawai (NIP)</label
            >
            <input
              v-model="form.nip"
              type="text"
              required
              placeholder="18 digit angka"
              class="w-full text-sm px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none bg-white"
            />
          </div>
        </div>

        <div
          v-if="!isEditing"
          class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
          Password akun baru akan otomatis diset ke
          <span class="font-semibold">password123</span>
          dan user wajib mengganti password saat login pertama.
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
            form="userForm"
            :disabled="isSaving"
            class="px-5 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors disabled:opacity-70 flex items-center"
          >
            <svg
              v-if="isSaving"
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            {{ isSaving ? "Menyimpan..." : "Simpan Data" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- REUSABLE COMPONENT: ConfirmModal -->
    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Pengguna?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDeleteUser"
      @cancel="confirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import { useToastStore } from "../../stores/toast";
import { useAuthStore } from "../../stores/auth";
import { userService } from "../../services/modules/admin/userService";

// Mengimport Shared Components
import BaseSelect from "../../components/BaseSelect.vue";
import BaseTable from "../../components/BaseTable.vue";
import BaseModal from "../../components/BaseModal.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";

const toastStore = useToastStore();
const authStore = useAuthStore();

// --- CONFIGURATION ---
const tableColumns = [
  { key: "name", label: "Nama Pengguna" },
  { key: "email", label: "Email" },
  { key: "role", label: "Peran (Role)", align: "center" },
  { key: "actions", label: "Aksi", align: "center" },
];

const formRoleOptions = [
  { value: "admin", label: "Admin" },
  { value: "teacher", label: "Guru" },
  { value: "student", label: "Siswa" },
  { value: "principal", label: "Kepala Sekolah" },
];

const genderOptions = [
  { value: "L", label: "Laki-laki" },
  { value: "P", label: "Perempuan" },
];

const roleOptions = formRoleOptions; // Bisa dipakai ulang untuk filter dropdown

// --- STATE ---
const users = ref([]);
const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 100,
});
const isLoading = ref(true);
const isSaving = ref(false);
const searchQuery = ref("");
const roleFilter = ref("");

const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  message: "",
  targetId: null,
});
const isModalOpen = ref(false);
const isEditing = ref(false);
const form = reactive({
  id: null,
  name: "",
  email: "",
  role: "student",
  // Tambahan field dinamis
  nisn: "",
  nis: "",
  gender: "",
  nip: "",
  admin_secret_key: "",
});

const formatRoleName = (role) => {
  const map = {
    teacher: "Guru",
    student: "Siswa",
    principal: "Kepala Sekolah",
    admin: "Admin",
  };
  return map[role] || role;
};

const roleBadgeColor = (role) => {
  const colors = {
    admin: "bg-purple-50 text-purple-700 border-purple-200",
    teacher: "bg-blue-50 text-blue-700 border-blue-200",
    student: "bg-green-50 text-green-700 border-green-200",
    principal: "bg-brand-orange/10 text-brand-orange border-brand-orange/30",
  };
  return colors[role] || "bg-gray-50 text-gray-700 border-gray-200";
};

// --- ACTIONS / API ---
const fetchUsers = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = {
      page: page,
      search: searchQuery.value || undefined,
      role: roleFilter.value || undefined,
      per_page: paginationMeta.per_page,
    };
    const response = await userService.getAll(params);
    const payload = response.data;
    users.value = payload.data || payload;
    paginationMeta.total = payload.total ?? users.value.length;
    paginationMeta.current_page = payload.current_page ?? 1;
    paginationMeta.last_page = payload.last_page ?? 1;
    paginationMeta.per_page = payload.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error("Gagal mengambil data.");
  } finally {
    isLoading.value = false;
  }
};

watch([searchQuery, roleFilter], () => {
  fetchUsers(1);
});

const saveUser = async () => {
  isSaving.value = true;
  try {
    // Bangun Payload secara dinamis sesuai Role
    const payload = {
      name: form.name,
      email: form.email,
      role: form.role,
    };

    // Inject conditional field ke payload
    if (form.role === "student") {
      payload.nisn = form.nisn;
      payload.nis = form.nis;
      payload.gender = form.gender;
    }
    if (["teacher", "admin", "principal"].includes(form.role)) {
      payload.nip = form.nip;
    }
    if (form.role === "admin" && !isEditing.value)
      payload.admin_secret_key = form.admin_secret_key;

    if (isEditing.value) {
      await userService.update(form.id, payload);
    } else {
      await userService.create(payload);
    }

    toastStore.success(
      isEditing.value
        ? "Pengguna berhasil diperbarui!"
        : "Pengguna baru ditambahkan!",
    );
    closeModal();
    fetchUsers(paginationMeta.current_page);
  } catch (error) {
    // Menangani pesan error validasi array dari Laravel secara spesifik
    const errors = error.response?.data?.errors;
    if (errors) {
      const firstError = Object.values(errors)[0][0]; // Ambil pesan pertama dari array
      toastStore.error(firstError);
    } else {
      toastStore.error(
        error.response?.data?.message || "Gagal menyimpan data.",
      );
    }
  } finally {
    isSaving.value = false;
  }
};

const openModal = (user = null) => {
  isEditing.value = !!user;
  form.id = user?.id || null;
  form.name = user?.name || "";
  form.email = user?.email || "";
  form.role = user?.role || "student";

  // Relasi profile sudah ditarik dari backend untuk kebutuhan edit.
  form.nisn = user?.student?.nisn || "";
  form.nis = user?.student?.nis || "";
  form.gender = user?.student?.gender || "";
  if (form.role === "teacher") {
    form.nip = user?.teacher?.nip || "";
  } else if (form.role === "admin") {
    form.nip = user?.admin?.nip || "";
  } else if (form.role === "principal") {
    form.nip = user?.principal?.nip || "";
  } else {
    form.nip = "";
  }
  form.admin_secret_key = "";

  isModalOpen.value = true;
};

const promptDeleteUser = (user) => {
  confirmModal.targetId = user.id;
  confirmModal.message = `Yakin menghapus "${user.name}"? Data tidak dapat dikembalikan.`;
  confirmModal.isOpen = true;
};

const executeDeleteUser = async () => {
  confirmModal.isLoading = true;
  try {
    await userService.delete(confirmModal.targetId);
    toastStore.success("Dihapus.");
    if (users.value.length === 1 && paginationMeta.current_page > 1) {
      fetchUsers(paginationMeta.current_page - 1);
    } else {
      fetchUsers(paginationMeta.current_page);
    }
  } catch (error) {
    toastStore.error("Gagal menghapus.");
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

const closeModal = () => (isModalOpen.value = false);

onMounted(fetchUsers);
</script>
