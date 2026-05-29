<template>
  <div class="min-h-screen flex flex-col md:flex-row font-sans">
    <!-- SISI KIRI: Branding Area (Dominan 60% Red) -->
    <!-- Tersembunyi di layar kecil (mobile), muncul di ukuran md ke atas -->
    <div
      class="hidden md:flex md:w-[60%] bg-brand-red relative overflow-hidden items-center justify-center p-12"
    >
      <!-- Ornamen Dekoratif (10% Accent Orange) -->
      <div
        class="absolute top-0 left-0 w-64 h-64 bg-brand-orange rounded-br-full opacity-90"
      ></div>
      <div
        class="absolute bottom-[-10%] right-[-5%] w-96 h-96 bg-brand-orange rounded-full opacity-20 blur-3xl"
      ></div>

      <!-- Teks Branding (White) -->
      <div class="relative z-10 text-brand-white text-center">
        <h1 class="text-5xl font-bold tracking-tight mb-4">EduPlatform</h1>
        <p class="text-lg font-medium text-white/80 max-w-md mx-auto">
          Sistem Manajemen Akademik & Pembelajaran Terpadu untuk pengalaman
          edukasi yang lebih baik.
        </p>
      </div>
    </div>

    <!-- SISI KANAN: Form Area (30% White Space) -->
    <div
      class="w-full md:w-[40%] bg-brand-white flex items-center justify-center p-8 sm:p-12 shadow-2xl z-20"
    >
      <div class="w-full max-w-md">
        <!-- Mobile Header (Hanya muncul jika branding kiri hilang) -->
        <div class="md:hidden text-center mb-8">
          <h1 class="text-3xl font-bold text-brand-red mb-2">EduPlatform</h1>
          <p class="text-sm text-gray-500">Silakan login untuk melanjutkan</p>
        </div>

        <div class="hidden md:block mb-10">
          <h2 class="text-3xl font-bold text-gray-800">Selamat Datang</h2>
          <p class="text-gray-500 mt-2">
            Masukkan kredensial Anda untuk mengakses sistem.
          </p>
        </div>

        <!-- FORM LOGIN -->
        <!-- FORM LOGIN -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Email -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Alamat Email</label
            >
            <input
              v-model="form.email"
              @blur="checkEmailRequirements"
              type="email"
              placeholder="Masukkan email Anda"
              required
              class="w-full px-4 py-3 rounded-3xl border border-gray-300 focus:ring-1 focus:ring-brand-red focus:border-brand-red transition-colors outline-none text-sm"
            />
            <!-- Indikator Loading saat mengecek email -->
            <p
              v-if="isCheckingEmail"
              class="text-xs text-gray-400 mt-1 animate-pulse"
            >
              Memeriksa keamanan akun...
            </p>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1"
              >Kata Sandi</label
            >
            <input
              v-model="form.password"
              type="password"
              placeholder="Masukkan kata sandi Anda"
              required
              class="w-full px-4 py-3 rounded-3xl border border-gray-300 focus:ring-1 focus:ring-brand-red focus:border-brand-red transition-colors outline-none text-sm"
            />
            <p class="text-xs text-gray-400 mt-1"
              >Kata Sandi Minimal 8 karakter</p
            >
          </div>
          

          <!-- RECAPTCHA WIDGET (Conditional) -->
          <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
          >
            <div
              v-if="needsRecaptcha"
              class="flex flex-col items-center mt-4 p-4 bg-brand-orange/10 rounded-lg border border-brand-orange/30"
            >
              <p
                class="text-xs font-semibold text-brand-orange mb-3 text-center"
              >
                Keamanan Tambahan Diperlukan (Sesi Admin)
              </p>
              <!-- Ganti SITEKEY dengan Site Key Google reCAPTCHA Anda -->
              <VueRecaptcha
                sitekey="6LdRD9ksAAAAAOtF6b1Ux7EaguzcwhttnfKG2mza"
                @verify="onCaptchaVerified"
                @expire="onCaptchaExpired"
                @error="onCaptchaError"
              ></VueRecaptcha>
            </div>
          </transition>

          <!-- Tombol Submit -->
          <button
            type="submit"
            :disabled="isLoading || isCheckingEmail"
            class="w-full py-3 px-4 flex justify-center rounded-3xl shadow-md text-2sm tracking-wider font-bold text-brand-white bg-brand-red hover:bg-brand-orange focus:outline-none focus:ring-2 focus:ring-brand-orange/50 transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed mt-6"
          >
            <svg
              v-if="isLoading"
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
            {{ isLoading ? "Memverifikasi..." : "Login" }}
          </button>
          <p class="text-center text-xs text-gray-500 mt-4">
            Note: Jika Anda mengalami masalah saat login, pastikan email dan kata sandi benar. Jika lupa kata sandi, silakan hubungi administrator.
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
  "g-recaptcha-response": "",
});

const isLoading = ref(false);
const isCheckingEmail = ref(false);
const needsRecaptcha = ref(false);
const lastCheckedEmail = ref(""); // Mencegah API dipanggil berulang kali untuk email yang sama

// FUNGSI BARU: Mengecek email saat kursor pindah (blur)
const checkEmailRequirements = async () => {
  // Hanya jalankan jika email valid dan berbeda dari yang terakhir dicek
  if (
    !form.email ||
    !form.email.includes("@") ||
    form.email === lastCheckedEmail.value
  )
    return;

  isCheckingEmail.value = true;
  lastCheckedEmail.value = form.email;

  try {
    // Panggil lewat Store, BUKAN API langsung. UI menjadi sangat bersih!
    needsRecaptcha.value = await authStore.checkEmail(form.email);

    // Kosongkan token lama jika email diganti dan ternyata tidak butuh captcha
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
    const payload = { email: form.email, password: form.password };
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
