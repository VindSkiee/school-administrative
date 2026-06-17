<template>
  <section class="min-h-screen bg-white sm:bg-gradient-to-br sm:from-red-50 sm:via-amber-50 sm:to-white p-4 sm:py-10 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[80vh] w-full max-w-xl items-center justify-center">
      
      <div class="w-full bg-white p-0 sm:p-10 rounded-none sm:rounded-3xl border-none sm:border sm:border-red-100 shadow-none sm:shadow-xl">
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
            <label for="new_password" class="mb-1 block text-sm font-semibold text-slate-700">Password Baru</label>
            <div class="relative">
              <input
                id="new_password"
                v-model="form.new_password"
                :type="showNewPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                minlength="8"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-200"
              />
              <button
                type="button"
                @click="showNewPassword = !showNewPassword"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-red-500 transition-colors"
                tabindex="-1"
              >
                <svg v-if="!showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label for="new_password_confirmation" class="mb-1 block text-sm font-semibold text-slate-700">Konfirmasi Password Baru</label>
            <div class="relative">
              <input
                id="new_password_confirmation"
                v-model="form.new_password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                minlength="8"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-200"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-red-500 transition-colors"
                tabindex="-1"
              >
                <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
              </button>
            </div>
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
  new_password: '',
  new_password_confirmation: '',
});

const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const isSubmitting = ref(false);
const formError = ref('');

const submitForm = async () => {
  isSubmitting.value = true;
  formError.value = '';

  try {
    const response = await api.post('/v1/auth/force-change-password', {
      current_password: 'password123',
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