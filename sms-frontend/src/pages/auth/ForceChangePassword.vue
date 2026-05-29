<template>
  <section class="min-h-screen bg-gradient-to-br from-red-50 via-amber-50 to-white px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[80vh] w-full max-w-xl items-center justify-center">
      <div class="w-full rounded-3xl border border-red-100 bg-white p-7 shadow-xl sm:p-10">
        <header class="mb-6">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-600">Keamanan Akun</p>
          <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Ganti Password Default</h1>
          <p class="mt-2 text-sm text-slate-600">Selesaikan proses ini sebelum melanjutkan ke sistem.</p>
        </header>

        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-4">
          <p class="text-sm font-medium leading-relaxed text-amber-900">
            Demi keamanan akun, Anda diwajibkan mengganti password default sebelum mengakses sistem.
          </p>
        </div>

        <form class="space-y-4" @submit.prevent="submitForm">
          <div>
            <label for="current_password" class="mb-1 block text-sm font-semibold text-slate-700">Password Saat Ini</label>
            <input
              id="current_password"
              v-model="form.current_password"
              type="password"
              autocomplete="current-password"
              required
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-200"
            />
          </div>

          <div>
            <label for="new_password" class="mb-1 block text-sm font-semibold text-slate-700">Password Baru</label>
            <input
              id="new_password"
              v-model="form.new_password"
              type="password"
              autocomplete="new-password"
              required
              minlength="8"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-200"
            />
          </div>

          <div>
            <label for="new_password_confirmation" class="mb-1 block text-sm font-semibold text-slate-700">Konfirmasi Password Baru</label>
            <input
              id="new_password_confirmation"
              v-model="form.new_password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              minlength="8"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-200"
            />
            <p class="mt-1 text-xs text-slate-500">Password harus minimal 8 karakter.</p>
          </div>

          <p v-if="formError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ formError }}
          </p>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 disabled:cursor-not-allowed disabled:opacity-70"
          >
            <span v-if="isSubmitting">Menyimpan Password...</span>
            <span v-else>Simpan Password Baru</span>
          </button>
          <p class="mt-3 text-center text-sm text-slate-600">
            <span class="text-xs italic">Pastikan Password disimpan/dicatat dengan aman. Jika mengalami kesulitan, silakan hubungi administrator.</span>
          </p>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const isSubmitting = ref(false);
const formError = ref('');

const submitForm = async () => {
  isSubmitting.value = true;
  formError.value = '';

  try {
    const response = await api.post('/v1/auth/force-change-password', {
      current_password: form.current_password,
      new_password: form.new_password,
      new_password_confirmation: form.new_password_confirmation,
    });

    authStore.markPasswordAsChanged();
    toastStore.success(response.data?.message || 'Password berhasil diubah.');
    await router.push('/dashboard');
  } catch (error) {
    const responseStatus = error.response?.status;
    const responseData = error.response?.data;

    if (responseStatus === 422) {
      formError.value = responseData?.message || 'Validasi gagal. Periksa kembali data yang dimasukkan.';
    } else {
      formError.value = responseData?.message || 'Gagal mengubah password. Silakan coba lagi.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>
