<template>
  <div class="min-h-screen flex flex-col md:flex-row font-sans bg-gray-100">
    <div
      class="hidden md:flex md:w-[60%] bg-gray-50 relative overflow-hidden items-center justify-center p-12"
    >
      <div class="relative z-10 text-center flex flex-col items-center">
        <img 
          src="/logo_fatan.png" 
          alt="Logo EduPlatform" 
          class="h-20 md:h-80 w-auto mb-8 object-contain drop-shadow-sm transition-transform hover:scale-105 duration-300"
        />
        
        <p class="text-lg font-medium text-gray-600 max-w-md mx-auto">
          Sistem Manajemen Akademik & Pembelajaran Terpadu untuk pengalaman
          edukasi yang lebih baik.
        </p>
      </div>
    </div>

    <div
      class="w-full md:w-[40%] bg-white flex items-center justify-center p-8 sm:p-12 shadow-2xl z-20 min-h-screen md:min-h-0"
    >
      <div class="w-full max-w-md">
        
        <div class="md:hidden flex justify-center items-center mb-10 pb-6 border-b border-gray-100">
          <img 
            src="/logo_fatan.png" 
            alt="Logo EduPlatform" 
            class="h-52 w-auto object-contain drop-shadow-sm"
          />
        </div>

        <div class="hidden md:block mb-10">
          <h2 class="text-3xl font-bold text-gray-800">Selamat Datang</h2>
          <p class="text-gray-500 mt-2 text-sm">
            Masukkan kredensial Anda untuk mengakses sistem.
          </p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Email / NIP / NIS / NISN</label
            >
            <input
              v-model="form.email"
              @blur="checkEmailRequirements"
              type="text"
              placeholder="Masukkan email, NIP, NIS, atau NISN"
              required
              class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition-all outline-none text-sm bg-gray-50 focus:bg-white"
            />
            <p
              v-if="isCheckingEmail"
              class="text-xs text-brand-orange mt-1.5 animate-pulse font-medium"
            >
              Memeriksa keamanan akun...
            </p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Kata Sandi</label
            >
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan kata sandi Anda"
                required
                class="w-full px-4 py-3 pr-11 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red transition-all outline-none text-sm bg-gray-50 focus:bg-white"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-brand-red transition-colors"
                tabindex="-1"
              >
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
              </button>
            </div>
            <p class="text-xs text-gray-400 mt-1.5"
              >Minimal 8 karakter</p
            >
          </div>
          
          <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
          >
            <div
              v-if="needsRecaptcha"
              class="flex flex-col items-center mt-4 p-4 bg-orange-50 rounded-2xl border border-orange-100"
            >
              <p
                class="text-xs font-bold text-brand-orange mb-3 text-center uppercase tracking-wider"
              >
                Keamanan Tambahan Admin
              </p>
              <VueRecaptcha
                sitekey="6LdRD9ksAAAAAOtF6b1Ux7EaguzcwhttnfKG2mza"
                @verify="onCaptchaVerified"
                @expire="onCaptchaExpired"
                @error="onCaptchaError"
              ></VueRecaptcha>
            </div>
          </transition>

          <div class="flex items-center gap-2 mt-4">
            <input
              v-model="form.remember"
              type="checkbox"
              id="remember-me"
              class="w-4 h-4 rounded border-gray-300 text-brand-red focus:ring-brand-red/30 cursor-pointer"
            />
            <label for="remember-me" class="text-sm text-gray-600 cursor-pointer select-none">
              Ingat saya
            </label>
          </div>

          <button
            type="submit"
            :disabled="isLoading || isCheckingEmail"
            class="w-full py-3.5 px-4 flex justify-center items-center gap-2 rounded-2xl shadow-md shadow-brand-red/20 text-sm tracking-wide font-bold text-white bg-brand-red hover:bg-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/50 transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed mt-8"
          >
            <svg
              v-if="isLoading"
              class="animate-spin h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
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
            {{ isLoading ? "Mengautentikasi..." : "Masuk ke Sistem" }}
          </button>
          
          <p class="text-center text-xs text-gray-400 mt-6 leading-relaxed">
            Jika Anda mengalami masalah saat login atau lupa kata sandi, silakan hubungi administrator IT sekolah.
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useToastStore } from "../stores/toast";
import VueRecaptcha from "vue3-recaptcha2";

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const form = reactive({
  email: "",
  password: "",
  remember: false,
  "g-recaptcha-response": "",
});

const isLoading = ref(false);
const isCheckingEmail = ref(false);
const needsRecaptcha = ref(false);
const showPassword = ref(false);
const lastCheckedEmail = ref(""); // Mencegah API dipanggil berulang kali untuk email yang sama

// Mengecek apakah credential memerlukan reCAPTCHA (admin/kepala sekolah)
const checkEmailRequirements = async () => {
  // Hanya jalankan jika input tidak kosong dan berbeda dari yang terakhir dicek
  if (
    !form.email ||
    form.email === lastCheckedEmail.value
  )
    return;

  isCheckingEmail.value = true;
  lastCheckedEmail.value = form.email;

  try {
    // Backend akan resolve user dari email/NIP/NIS/NISN lalu cek role
    needsRecaptcha.value = await authStore.checkEmail(form.email);

    // Kosongkan token lama jika credential diganti dan ternyata tidak butuh captcha
    if (!needsRecaptcha.value) {
      form["g-recaptcha-response"] = "";
    }
  } finally {
    isCheckingEmail.value = false;
  }
};

const onCaptchaVerified = (response) => {
  form["g-recaptcha-response"] = response;
};

const onCaptchaExpired = () => {
  form["g-recaptcha-response"] = "";
};

const onCaptchaError = () => {
  form["g-recaptcha-response"] = "";
  toastStore.error(
    "Gagal memuat reCAPTCHA. Periksa koneksi Anda lalu coba lagi.",
  );
};

const handleLogin = async () => {
  isLoading.value = true;

  if (needsRecaptcha.value && !form["g-recaptcha-response"]) {
    toastStore.info(
      "Mohon selesaikan validasi keamanan reCAPTCHA terlebih dahulu.",
    );
    isLoading.value = false;
    return;
  }

  try {
    const payload = { email: form.email, password: form.password, remember: form.remember };
    if (needsRecaptcha.value && form["g-recaptcha-response"]) {
      payload["g-recaptcha-response"] = form["g-recaptcha-response"];
    }

    await authStore.login(payload);
    toastStore.success("Login berhasil. Mengarahkan ke dashboard...");

    if (authStore.mustChangePassword) {
      router.push("/force-change-password");
      return;
    }

    const role = authStore.userRole;
    switch (role) {
      case "admin":
        router.push("/admin/dashboard");
        break;
      case "teacher":
        router.push("/teacher/dashboard");
        break;
      case "student":
        router.push("/student/dashboard");
        break;
      case "principal":
        router.push("/principal/dashboard");
        break;
      default:
        router.push("/");
    }
  } catch (error) {
    const status = error.response?.status;
    const errorData = error.response?.data;
    const recaptchaInvalid = errorData?.errors?.["g-recaptcha-response"];

    if (status === 422 && recaptchaInvalid) {
      needsRecaptcha.value = true;
      toastStore.info("Verifikasi reCAPTCHA diperlukan atau sudah kadaluarsa.");
    } else {
      // Ekstraksi pesan error yang lebih cerdas (menyesuaikan format bawaan Laravel)
      let errorMessage = "Kredensial tidak valid atau server bermasalah.";

      if (errorData?.error) {
        errorMessage = errorData.error; // Format custom auth
      } else if (errorData?.message) {
        errorMessage = errorData.message; // Format validasi Laravel default
      }

      // Sekarang Toast dipastikan muncul tanpa form ter-reset!
      toastStore.error(errorMessage);
    }
  } finally {
    isLoading.value = false;
  }
};
</script>
