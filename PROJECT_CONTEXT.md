# PROJECT_CONTEXT.md

Dokumentasi onboarding untuk project **School Administrative** (branding UI: **EduPlatform**). Dokumen ini menjelaskan arsitektur, konvensi, dan cara kerja sistem agar developer baru atau AI agent dapat mulai mengembangkan fitur tanpa membaca seluruh codebase.

---

## 1. Project Overview

| Aspek | Detail |
|-------|--------|
| **Nama project** | `school-administrative` (repo), frontend package: `sms-frontend`, branding UI: **EduPlatform** |
| **Tujuan** | Sistem Manajemen Sekolah (SMS) terpadu untuk administrasi akademik dan pembelajaran digital |
| **Masalah yang diselesaikan** | Digitalisasi operasional sekolah: manajemen pengguna, kelas, jadwal, absensi, materi, tugas, penilaian, laporan akademik, dan monitoring aktivitas |
| **Target pengguna** | Admin sekolah, guru (termasuk wali kelas), siswa, kepala sekolah (principal) |

### Ruang lingkup fitur utama

- **Admin**: CRUD pengguna, kelas, tahun ajaran, mapel, jadwal; laporan akademik & absensi; publikasi rapor; log aktivitas
- **Guru**: Dashboard, absensi harian, materi & tugas per jadwal, penilaian submission, kelola kelas perwalian
- **Siswa**: Dashboard (UI placeholder), API backend untuk materi, tugas, nilai, rapor (frontend belum lengkap)
- **Principal**: Dashboard analitik (UI placeholder), API backend untuk overview & trends

---

## 2. Tech Stack

| Kategori | Teknologi |
|----------|-----------|
| **Frontend framework** | Vue 3 (Composition API, `<script setup>`) |
| **Backend framework** | Laravel 13 |
| **Bahasa** | JavaScript (frontend), PHP 8.3 (backend) |
| **State management** | Pinia 3 |
| **UI library** | Tidak ada component library pihak ketiga; custom components + `@iconify/vue` untuk ikon |
| **CSS framework** | Tailwind CSS v4 (`@tailwindcss/vite`) |
| **Database** | MySQL (production/dev via Laragon); default config Laravel mendukung SQLite |
| **Authentication** | JWT via `tymon/jwt-auth` (guard `api`, driver `jwt`) |
| **HTTP client** | Axios |
| **Build tools** | Vite 8 (frontend & backend asset bundling) |
| **Package manager** | npm (frontend & backend JS), Composer (PHP) |
| **Testing** | Pest 4 (backend) |
| **PDF** | `barryvdh/laravel-dompdf` (rapor semester) |
| **Captcha** | Google reCAPTCHA v2 (`vue3-recaptcha2` di frontend, custom rule di backend) |

### Environment & development

- Frontend dev server: `http://localhost:5173`
- Backend API: `http://localhost:8000`
- Vite proxy: `/api` → `http://localhost:8000`
- Base URL API frontend: `import.meta.env.VITE_API_BASE_URL` atau fallback `/api`
- Setup lengkap: lihat [`setup.md`](setup.md)

---

## 3. Folder Structure

### Root monorepo

```
school-administrative/
├── backend/          # Laravel API
├── sms-frontend/     # Vue SPA
├── setup.md          # Panduan setup lokal
└── PROJECT_CONTEXT.md
```

### Backend (`backend/`)

| Folder | Fungsi | Kapan digunakan | Hubungan |
|--------|--------|-----------------|----------|
| `app/Http/Controllers/API/` | HTTP handlers per role (`Admin/`, `Teacher/`, `Student/`, `Principal/`) | Menerima request API, validasi via Form Request, delegasi ke Service | Memanggil `app/Services/`, mengembalikan JSON |
| `app/Http/Requests/` | Validasi input per domain (`Admin/`, `Teacher/`, `Student/`) | Sebelum operasi create/update | Dipakai oleh Controller |
| `app/Http/Middleware/` | `RoleMiddleware`, `EnsurePasswordIsChanged` | Proteksi route API per role & password | Didaftarkan di `bootstrap/app.php` |
| `app/Models/` | Eloquent models & relasi | Akses data DB | Dipakai Services & Controllers |
| `app/Services/` | Business logic layer | Operasi kompleks (CRUD, agregasi, PDF, upload) | Dipanggil oleh Controllers |
| `app/Notifications/` | Notifikasi database (graded, attendance reviewed) | Event bisnis | Via Laravel Notification |
| `app/Rules/` | Custom validation (e.g. `RecaptchaV2`) | Login admin | AuthController |
| `app/Traits/` | `RecordsActivity` — auto-log aktivitas | Model yang di-audit | Models |
| `app/Console/Commands/` | Artisan commands (e.g. `CleanOrphanedFiles`) | Maintenance | Standalone |
| `routes/api.php` | Route global (`health`, avatar upload) | Endpoint lintas role | — |
| `routes/api/v1/` | Route per role (`auth`, `admin`, `teacher`, `student`, `principal`, `general`) | Registrasi di `bootstrap/app.php` | Controller mapping |
| `database/migrations/` | Schema DB | `php artisan migrate` | Models |
| `database/seeders/` | Data awal (admin, guru, siswa, kelas, mapel) | `migrate --seed` | Development |
| `database/data/` | CSV siswa untuk seeder | Import bulk siswa | StudentSeeder |
| `resources/views/reports/` | Blade template PDF rapor | Generate PDF | ReportPdfService |
| `config/` | Konfigurasi Laravel, JWT, auth, database | Runtime | — |
| `skills/` | Agent skills (Laravel best practices, Pest, Tailwind) | AI-assisted development | — |
| `tests/` | Pest feature/unit tests | CI & regression | — |

### Frontend (`sms-frontend/src/`)

| Folder | Fungsi | Kapan digunakan | Hubungan |
|--------|--------|-----------------|----------|
| `pages/` | Halaman/route view, dikelompokkan per role (`admin/`, `teacher/`, `student/`, `principal/`, `auth/`) | Satu file ≈ satu route | Memanggil `services/`, `stores/`, `components/` |
| `pages/teacher/schedulePanel/` | Sub-panel untuk ScheduleDetail (Attendance, Material, Assignment) | Tab di halaman detail jadwal | Service teacher |
| `layouts/` | `MainLayout.vue` — sidebar, navbar, router-view | Halaman authenticated | Router children |
| `components/` | Komponen reusable UI | Dipakai di pages | Props/events |
| `router/` | `index.js` — definisi route + navigation guard | Routing SPA | Auth store |
| `stores/` | Pinia stores (`auth`, `toast`) | State global | API, localStorage |
| `services/` | Axios instance + modul API per role | Semua komunikasi backend | `api.js` base |
| `services/modules/admin/` | Service admin (users, classes, schedules, dll.) | Halaman admin | `api.js` |
| `services/modules/teacher/` | Service guru | Halaman teacher | `api.js` |
| `style.css` | Tailwind import + custom theme (brand colors, fonts) | Global styling | `main.js` |
| `App.vue` | Root: ToastBar + router-view | Entry layout | — |
| `main.js` | Bootstrap Vue + Pinia + Router | App startup | — |

---

## 4. Architecture

### Pola arsitektur

| Layer | Pola |
|-------|------|
| **Backend** | **Layered Architecture** + **MVC**: Route → Middleware → Controller → Service → Model |
| **Backend API** | **Role-based modular routing** — prefix `api/v1/{role}` |
| **Frontend** | **Feature-based pages** + **Service layer** — tidak ada folder `features/` formal, tetapi pages dikelompokkan per role |
| **Frontend state** | Minimal global state (auth + toast); data halaman di `ref`/`reactive` lokal |

### Alur data (umum)

```
User Action (Vue Page)
    → Service Module (axios)
    → API Interceptor (JWT header)
    → Laravel Route + Middleware (auth, role, password.changed)
    → Controller
    → Service (business logic)
    → Eloquent Model
    → MySQL
    → JSON Response
    → Page update UI + Toast feedback
```

### Alur request HTTP

1. **Frontend** memanggil `api.get/post/...` dari service module
2. **Request interceptor** menyuntikkan `Authorization: Bearer {token}` dari `localStorage`
3. **Vite proxy** (dev) atau reverse proxy (prod) mengarahkan `/api` ke Laravel
4. **Laravel** memproses middleware chain:
   - `api` — rate limiting
   - `auth:api` — JWT validation
   - `password.changed` — blok jika `must_change_password = true`
   - `role:{admin|teacher|student|principal}` — RBAC
5. **Controller** memvalidasi via Form Request (jika ada), memanggil Service
6. **Response** JSON; error ditangani interceptor frontend

### Hubungan frontend ↔ backend

- **SPA terpisah**: frontend Vue tidak di-embed Laravel Blade (kecuali PDF rapor di backend)
- **Komunikasi**: REST JSON over HTTP
- **Auth**: JWT stateless; tidak ada session cookie di frontend
- **File upload**: `multipart/form-data` untuk avatar, materi, tugas, submission siswa
- **File download**: blob response untuk PDF rapor

---

## 5. Routing System

### Lokasi konfigurasi

| File | Tanggung jawab |
|------|----------------|
| `sms-frontend/src/router/index.js` | Semua route SPA + `beforeEach` guard |
| `backend/bootstrap/app.php` | Registrasi route API v1 per role |
| `backend/routes/api/v1/*.php` | Route detail per domain |

### Struktur route frontend

Route dikelompokkan per role dengan `MainLayout` sebagai parent:

```
/login                          → Login (guest only)
/force-change-password          → Wajib ganti password
/dashboard, /                   → Redirect ke /{role}/dashboard
/account/profile                → UserProfile (semua role)
/admin/*                        → Admin pages (role: admin)
/teacher/*                      → Teacher pages (role: teacher)
/student/*                      → Student pages (role: student)
/principal/*                    → Principal pages (role: principal)
/unauthorized, /server-down     → Error pages
/:pathMatch(.*)*                → 404
```

### Route guard (`router.beforeEach`)

| Kondisi | Aksi |
|---------|------|
| `requiresAuth` && tidak login | Redirect `/login` |
| `requiresGuest` && sudah login | Redirect `/{role}/dashboard` |
| `mustChangePassword` && bukan `/force-change-password` | Redirect `/force-change-password` |
| Sudah ganti password && akses `/force-change-password` | Redirect dashboard |
| `meta.role` !== `userRole` | Redirect `/unauthorized` |

### Permission system

- **Frontend**: RBAC sederhana via `meta.role` di route definition
- **Backend**: `RoleMiddleware` memvalidasi `user.role` terhadap role di route group
- Tidak ada permission granular (e.g. `can:edit-user`); akses = role level

### Dynamic routes (contoh penting)

| Route | Parameter | Halaman |
|-------|-----------|---------|
| `/admin/users/:id` | `id` | UserProfile (admin view) |
| `/admin/classes/:id` | `id` | ClassDetail |
| `/teacher/classes/:id` | `id` | Teacher ClassDetail (perwalian) |
| `/teacher/students/:id` | `id` | StudentProfile |
| `/teacher/classes/:schedule_id/detail` | `schedule_id` | ScheduleDetail |
| `/teacher/assignments/:id` | `id` | TeacherAssignmentDetail |

### Contoh route penting

```javascript
// Admin dashboard
{ path: '/admin/dashboard', name: 'AdminDashboard', meta: { requiresAuth: true, role: 'admin' } }

// Teacher jadwal hari ini
{ path: '/teacher/schedules/today', name: 'TeacherAttendance' }

// Redirect pintar berdasarkan role
{ path: '/dashboard', redirect: () => `/${authStore.userRole}/dashboard` }
```

---

## 6. State Management

### Library: Pinia 3

| Store | File | State | Getters | Actions | Kapan digunakan |
|-------|------|-------|---------|---------|-----------------|
| `auth` | `stores/auth.js` | `user`, `token` | `isAuthenticated`, `userRole`, `mustChangePassword` | `login`, `logout`, `checkEmail`, `markPasswordAsChanged`, `updateUserAvatar` | Login, guard, profil, avatar |
| `toast` | `stores/toast.js` | `toasts[]` | — | `push`, `success`, `error`, `info`, `remove`, `clear` | Feedback operasi CRUD |

### Persistensi

- `access_token` dan `user_data` disimpan di **`localStorage`**
- Auth store membaca localStorage saat inisialisasi
- Logout menghapus kedua key

### Pola penggunaan

- **Tidak ada store per feature** — data list/detail di-hold di component (`ref`/`reactive`)
- Store hanya untuk auth session dan notifikasi toast global
- `useToastStore()` dipanggil di hampir semua halaman CRUD

---

## 7. API Layer

### Cara API dipanggil

```javascript
// Base instance
import api from '../services/api';

// Modul per domain
import { userService } from '../services/modules/admin/userService';
const response = await userService.getAll({ page: 1 });
```

### Library

- **Axios** dengan instance terpusat di `services/api.js`

### Lokasi service API

```
sms-frontend/src/services/
├── api.js                          # Axios instance + interceptors
└── modules/
    ├── admin/
    │   ├── academicYearService.js
    │   ├── activityLogService.js
    │   ├── classService.js
    │   ├── dashboardService.js
    │   ├── reportService.js
    │   ├── scheduleService.js
    │   ├── subjectService.js
    │   └── userService.js
    └── teacher/
        ├── assignmentService.js
        ├── attendanceService.js
        ├── dashboardService.js
        ├── homeroomService.js
        └── materialService.js
```

**Catatan**: Belum ada service module untuk `student` dan `principal` di frontend.

### Interceptor

**Request** (`api.js`):
- Menyuntikkan `Authorization: Bearer {token}` dari localStorage

**Response** (`api.js`):
| Status / Kondisi | Aksi |
|------------------|------|
| No response (network error) | Redirect `/server-down` |
| 403 + `PASSWORD_CHANGE_REQUIRED` | Redirect `/force-change-password` |
| 401 | Clear localStorage, redirect `/login` (kecuali sudah di login) |
| 403 (umum) | Redirect `/unauthorized` |
| 502, 503, 504 | Redirect `/server-down` |

### Error handling di halaman

- `try/catch` di component, pesan error dari `error.response?.data?.message` atau `error.response?.data?.error`
- `useToastStore().error(message)` untuk feedback user
- Beberapa halaman menampilkan inline error state

### Authentication flow

```
1. User input email → checkEmail() → POST /v1/auth/check-requirements
   → Admin: tampilkan reCAPTCHA
2. Submit login → POST /v1/auth/login
   → Simpan access_token + user ke localStorage & Pinia
3. Router guard cek must_change_password
4. Setiap request: Bearer token via interceptor
5. Logout → POST /v1/auth/logout → clear localStorage
```

### Daftar endpoint API

#### Global (`routes/api.php`)

| Method | Endpoint | Controller |
|--------|----------|------------|
| GET | `/api/health` | Closure (health check) |
| POST | `/api/users/{id}/avatar` | UserController@uploadAvatar |
| GET | `/api/schedules/{schedule_id}/attendances` | AttendanceController@getAttendances |

#### Auth (`/api/v1/auth`)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/login` | Public | Login, return JWT |
| POST | `/check-requirements` | Public | Cek butuh captcha (admin) |
| POST | `/logout` | JWT | Invalidate token |
| POST | `/force-change-password` | JWT | Ganti password wajib |
| POST | `/refresh` | JWT + password changed | Refresh token |
| GET | `/me` | JWT + password changed | Data user saat ini |

#### Admin (`/api/v1/admin`) — semua butuh role `admin`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET/POST | `/users` | List / create user |
| GET/PUT/DELETE | `/users/{user}` | Show / update / delete |
| PATCH | `/users/{id}/reset-password` | Reset password default |
| GET/POST | `/academic-years` | CRUD tahun ajaran |
| PUT/DELETE | `/academic-years/{academic_year}` | Update / delete |
| PATCH | `/academic-years/{id}/set-active` | Aktifkan tahun ajaran |
| PATCH | `/academic-years/{id}/publish-reports` | Publikasi rapor |
| GET/POST | `/classes` | CRUD kelas |
| GET/PUT/DELETE | `/classes/{class}` | Show / update / delete |
| POST | `/classes/{id}/assign-students` | Assign siswa ke kelas |
| POST | `/classes/{id}/assign-teacher` | Assign wali kelas |
| POST | `/classes/migrate-semester` | Migrasi semester (throttled) |
| GET/POST | `/subjects` | CRUD mapel |
| GET/PUT/DELETE | `/subjects/{subject}` | Show / update / delete |
| GET/POST | `/schedules` | CRUD jadwal |
| GET/PUT/DELETE | `/schedules/{schedule}` | Show / update / delete |
| GET | `/dashboard/stats` | Statistik dashboard |
| GET | `/activity-logs` | Log aktivitas |
| GET | `/reports/distribution` | Distribusi rapor (throttled) |
| GET | `/reports/attendance` | Summary absensi |
| GET | `/reports/academic` | Summary akademik |
| GET | `/reports/semester/{academicYearId}/students/{studentId}/pdf` | Download PDF rapor |

#### Teacher (`/api/v1/teacher`) — role `teacher`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/dashboard/stats` | Dashboard guru |
| GET | `/homeroom-class` | Detail kelas perwalian |
| GET | `/schedules/today` | Jadwal hari ini / filter day |
| GET | `/schedules/{schedule_id}` | Detail jadwal |
| GET | `/schedules/{schedule_id}/students` | Siswa untuk absensi |
| POST | `/attendances/bulk` | Submit absensi bulk |
| GET | `/attendance-requests` | List request izin/sakit |
| PATCH | `/attendance-requests/{id}/review` | Review request |
| GET | `/schedules/{schedule_id}/materials` | List materi |
| POST | `/materials` | Upload materi (throttled) |
| DELETE | `/materials/{id}` | Hapus materi |
| GET | `/schedules/{schedule_id}/assignments` | List tugas per jadwal |
| GET | `/assignments` | List semua tugas guru |
| POST | `/assignments` | Buat tugas (throttled) |
| DELETE | `/assignments/{id}` | Hapus tugas |
| GET | `/assignments/{id}/submissions` | Submission siswa |
| POST | `/submissions/{id}/grade` | Beri nilai |
| GET | `/schedules/{schedule_id}/grades/aggregate` | Agregasi nilai |

#### Student (`/api/v1/student`) — role `student`

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/attendance-requests` | List request absensi |
| POST | `/attendance-requests` | Buat request (throttled) |
| GET | `/materials` | List materi |
| GET | `/materials/{id}/download` | Download materi |
| GET | `/assignments` | List tugas |
| POST | `/assignments/{id}/submit` | Submit tugas (throttled) |
| GET | `/grades` | List nilai |
| GET | `/grades/aggregate` | Agregasi nilai |
| GET | `/reports/semester` | Data rapor semester |
| GET | `/reports/semester/pdf` | Download PDF rapor |

#### Principal (`/api/v1/principal`) — role `principal`, throttled

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/dashboard/overview` | Overview eksekutif |
| GET | `/dashboard/attendance-trends` | Tren absensi |
| GET | `/dashboard/academic-performance` | Performa akademik |

#### Notifications (`routes/api/v1/general.php`)

**Catatan**: File route ada tetapi **belum didaftarkan** di `bootstrap/app.php`. Endpoint yang didefinisikan:

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/notifications` | List notifikasi |
| PATCH | `/notifications/read-all` | Tandai semua dibaca |
| PATCH | `/notifications/{id}/read` | Tandai satu dibaca |

---

## 8. Authentication & Authorization

### Cara login

1. Form di `pages/Login.vue`
2. Opsional: `checkEmail()` saat blur email → tentukan tampilan reCAPTCHA (hanya admin)
3. `authStore.login({ email, password, 'g-recaptcha-response' })`
4. Backend validasi kredensial + captcha (admin) → return JWT + user object
5. Redirect ke `/{role}/dashboard` atau `/force-change-password`

### Penyimpanan token

| Key | Lokasi | Isi |
|-----|--------|-----|
| `access_token` | localStorage | JWT string |
| `user_data` | localStorage | JSON user (id, name, email, role, avatar_url, must_change_password, nested profile) |

JWT custom claims: `{ role: user.role }`

### Role yang tersedia

| Role | Deskripsi | Tabel profil |
|------|-----------|--------------|
| `admin` | Administrator sistem | `admins` (NIP) |
| `teacher` | Guru / wali kelas | `teachers` (NIP) |
| `student` | Siswa | `students` (NIS, NISN) |
| `principal` | Kepala sekolah | `principals` (NIP) |

### Middleware / guard

| Layer | Mekanisme |
|-------|-----------|
| **Backend** | `auth:api` (JWT), `password.changed`, `role:{role}` |
| **Frontend** | `router.beforeEach` — `requiresAuth`, `requiresGuest`, `meta.role` |
| **Rate limiting** | `api` (60/min), `auth-api` (5/min), `heavy-api` (15/min), `upload-api` (30/min) |

### Fitur keamanan tambahan

- Admin wajib reCAPTCHA v2 saat login
- `must_change_password` memblokir akses API (kecuali force-change & logout)
- `is_active` harus `true` untuk login
- Soft delete pada `users`

---

## 9. Reusable Components

| Komponen | Lokasi | Tujuan | Props penting | Kapan digunakan |
|----------|--------|--------|---------------|-----------------|
| **BaseTable** | `components/BaseTable.vue` | Tabel data dengan header brand-red, loading, empty state, paginasi | `columns`, `data`, `isLoading`, `emptyMessage`, `pagination` | Semua halaman list admin, beberapa teacher |
| **BaseModal** | `components/BaseModal.vue` | Modal dialog dengan header/footer slot | `isOpen`, `title`, `maxWidth`, `isPersistent` | Form create/edit, detail |
| **BaseSelect** | `components/BaseSelect.vue` | Dropdown custom dengan search & teleport | `modelValue`, `options`, `placeholder`, `disabled`, `searchable` | Form dengan pilihan dinamis |
| **ConfirmModal** | `components/ConfirmModal.vue` | Dialog konfirmasi aksi destruktif | `isOpen`, `title`, `message`, `confirmText`, `cancelText`, `isLoading` | Delete, logout, publish |
| **ToastBar** | `components/ToastBar.vue` | Notifikasi toast global | — (baca dari toast store) | Root `App.vue` |
| **HelloWorld** | `components/HelloWorld.vue` | Template default Vite | — | **Tidak dipakai** (legacy) |

### Slot pattern BaseTable

```vue
<BaseTable :columns="cols" :data="items" @page-change="fetchData">
  <template #cell(status)="{ item }">
  </template>
</BaseTable>
```

---

## 10. UI & Design System

### Warna utama (Tailwind `@theme` di `style.css`)

| Token | Hex | Penggunaan |
|-------|-----|------------|
| `brand-red` | `#E02E2B` | Sidebar, header tabel, CTA primary, branding |
| `brand-white` | `#FFFFFF` | Background utama, teks di sidebar |
| `brand-orange` | `#C66716` | Accent, active nav, hover, gradient |
| `gray-50`–`gray-800` | Tailwind default | Background, teks, border |

### Typography

| Font | Penggunaan |
|------|------------|
| **Plus Jakarta Sans** (`font-sans`) | Body, UI umum |
| **Fraunces** (`font-serif`) | Heading hero, judul section |

Loaded via Google Fonts di `index.html`.

### Layout pattern

- **Authenticated**: Sidebar fixed (brand-red) + main content area (white header + gray-50 content)
- **Login**: Split 60/40 — branding red (desktop) + form white
- **Spacing**: `space-y-6` untuk section, `p-4 sm:p-6` untuk main content
- **Responsive**: Mobile hamburger sidebar, `hidden md:flex` patterns

### Card pattern

```html
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
```

- Stat cards: icon di `rounded-xl` dengan background color variant
- Hero welcome card: `border-l-4 border-brand-red` atau gradient `from-brand-red to-brand-orange`

### Table pattern

- Gunakan `BaseTable` — header `bg-brand-red text-white`, row hover `hover:bg-gray-100`
- Wrapper: `rounded-xl shadow-sm border border-gray-200`

### Form pattern

- Input: `px-4 py-2.5/3 rounded-lg/3xl border border-gray-300 focus:ring-brand-red focus:border-brand-red`
- Label: `text-sm font-semibold text-gray-700`
- Select: prefer `BaseSelect` over native `<select>`
- Form dalam `BaseModal` dengan footer slot untuk tombol aksi

### Modal pattern

- `BaseModal` untuk form/data
- `ConfirmModal` untuk konfirmasi
- Backdrop: `bg-gray-900/40 backdrop-blur-sm`
- Card: `rounded-2xl shadow-2xl`

### Button pattern

| Tipe | Classes |
|------|---------|
| Primary | `bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg` |
| Secondary | `bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg` |
| Danger confirm | `bg-brand-red` di ConfirmModal |
| Ghost/link | `text-brand-red font-semibold hover:underline` |

### Aturan desain konsisten

1. Brand red + orange sebagai warna identitas; hindari warna primer lain
2. `rounded-2xl` untuk cards besar, `rounded-lg` untuk buttons/inputs
3. `font-serif` untuk heading penting, `font-sans` untuk body
4. Transisi halaman: fade 0.2s di MainLayout router-view
5. Loading: skeleton `animate-pulse` atau spinner `animate-spin text-brand-red`
6. Bahasa UI: **Bahasa Indonesia**

---

## 11. Page Inventory

### Halaman publik / auth

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| Login | `/login` | Autentikasi | `POST /v1/auth/login`, `check-requirements` | reCAPTCHA (admin) |
| Force Change Password | `/force-change-password` | Ganti password wajib | `POST /v1/auth/force-change-password` | Form |
| Unauthorized | `/unauthorized` | Akses ditolak | — | — |
| Server Down | `/server-down` | Backend tidak tersedia | — | — |
| Not Found | `/*` | 404 | — | — |

### Halaman shared

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| User Profile | `/account/profile`, `/admin/users/:id` | Profil & edit user | `userService` (CRUD, avatar) | Form, modals |

### Admin pages

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| Dashboard | `/admin/dashboard` | Statistik & aktivitas | `GET /v1/admin/dashboard/stats` | Stat cards |
| User Management | `/admin/users` | CRUD pengguna | `userService` | BaseTable, BaseModal, BaseSelect, ConfirmModal |
| Class Management | `/admin/classes` | CRUD kelas, assign siswa/guru | `classService`, `userService` | BaseTable, BaseModal, BaseSelect, ConfirmModal |
| Class Detail | `/admin/classes/:id` | Detail kelas & siswa | `classService` | BaseTable |
| Academic Year | `/admin/academic-years` | CRUD tahun ajaran | `academicYearService` | BaseTable, BaseModal, ConfirmModal |
| Subject Management | `/admin/subjects` | CRUD mapel | `subjectService` | BaseTable, BaseModal, ConfirmModal |
| Schedule Management | `/admin/schedules` | CRUD jadwal | `scheduleService`, `api` (dropdown) | BaseTable, BaseModal, BaseSelect, ConfirmModal |
| Activity Logs | `/admin/activity-logs` | Audit log | `activityLogService` | BaseTable, BaseSelect |
| Report Management | `/admin/reports` | Laporan & publikasi rapor | `reportService`, `academicYearService`, `classService` | BaseSelect, custom tables |

### Teacher pages

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| Dashboard | `/teacher/dashboard` | Jadwal, stat, perwalian | `teacherDashboardService` | Custom cards |
| Attendance Schedule | `/teacher/schedules/today` | Jadwal mengajar hari ini | `attendanceService.getSchedules` | Custom list |
| Schedule Detail | `/teacher/classes/:schedule_id/detail` | Tab absensi/materi/tugas | `attendanceService` | AttendancePanel, MaterialPanel, AssignmentPanel |
| Assignment List | `/teacher/assignments` | Semua tugas guru | `assignmentService.getAllAssignments` | Custom list |
| Assignment Detail | `/teacher/assignments/:id` | Nilai submission | `assignmentService` | Form grading |
| Class Detail | `/teacher/classes/:id` | Kelas perwalian | `homeroomService` | BaseTable, BaseSelect |
| Student Profile | `/teacher/students/:id` | Profil siswa (partial) | — (commented service) | Placeholder |

### Student pages

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| Dashboard | `/student/dashboard` | Landing siswa | — (static placeholder) | Static widgets |

**Catatan**: Navigasi sidebar siswa (`/student/materials`, `/student/assignments`, `/student/grades`) **belum memiliki route** di router.

### Principal pages

| Halaman | Route | Tujuan | API | Komponen utama |
|---------|-------|--------|-----|----------------|
| Dashboard | `/principal/dashboard` | Dashboard eksekutif | — (placeholder, API ada) | Placeholder charts |

**Catatan**: Nav `/principal/reports` di sidebar **belum memiliki route**.

---

## 12. Data Models

### Entity utama & relasi

```
User (1) ──hasOne── Student | Teacher | Admin | Principal
                │
AcademicYear (1) ──hasMany── SchoolClass, Schedule
                │
SchoolClass ──belongsTo── AcademicYear, homeroomTeacher (Teacher)
            ──belongsToMany── Student (pivot: class_student + academic_year_id)
            ──hasMany── Schedule

Schedule ──belongsTo── SchoolClass, Subject, Teacher, AcademicYear
         ──hasMany── Attendance, Assignment, Material

Assignment ──hasMany── Submission
Submission ──hasOne── Grade (graded_by → Teacher)

Attendance ──belongsTo── Schedule, Student
AttendanceRequest ──belongsTo── Student, Schedule

Material ──belongsTo── Schedule (attachments: JSON array)
ActivityLog ──polymorphic audit trail
```

### Struktur data penting

**User**
```json
{
  "id", "name", "email", "role", "avatar_url",
  "must_change_password", "is_active",
  "nip", "nis", "nisn",
  "student", "teacher", "admin", "principal"
}
```

**SchoolClass** (`classes` table)
```json
{
  "id", "name", "academic_year_id", "homeroom_teacher_id"
}
```

**Schedule**
```json
{
  "id", "class_id", "subject_id", "teacher_id",
  "academic_year_id", "day_of_week", "start_time", "end_time"
}
```

**Assignment** (multi-file)
```json
{
  "id", "schedule_id", "date", "title", "description",
  "due_date", "attachments": ["path1", "path2"]
}
```

**Grade**
```json
{
  "id", "submission_id", "score", "feedback", "graded_by"
}
```

### Pivot tables

| Tabel | Kolom kunci |
|-------|-------------|
| `class_student` | `class_id`, `student_id`, `academic_year_id` |

---

## 13. Coding Conventions

### Penamaan file

| Tipe | Pola | Contoh |
|------|------|--------|
| Vue page | PascalCase, role folder | `pages/admin/UserManagement.vue` |
| Vue component | PascalCase | `BaseTable.vue`, `ConfirmModal.vue` |
| Service JS | camelCase + `Service` suffix | `userService.js` |
| Store JS | camelCase, nama singkat | `auth.js`, `toast.js` |
| PHP Controller | PascalCase + `Controller` | `UserController.php` |
| PHP Service | PascalCase + `Service` | `UserService.php` |
| PHP Request | PascalCase + action | `StoreUserRequest.php` |
| PHP Model | PascalCase singular | `SchoolClass.php` (table: `classes`) |
| Migration | timestamp descriptive | `create_users_table.php` |

### Penamaan component Vue

- PascalCase di import dan template: `<BaseTable />`
- File name = component name

### Penamaan function

- **Frontend**: camelCase — `fetchUsers`, `handleSubmit`, `getRowNumber`
- **Backend**: camelCase — `storeBulk`, `getTodaySchedules`, `respondWithToken`
- **Service export**: object dengan method camelCase — `userService.getAll()`

### Penamaan API service

```javascript
export const userService = {
  getAll(params) { ... },
  getById(id) { ... },
  create(payload) { ... },
  update(id, payload) { ... },
  delete(id) { ... },
};
```

### Penamaan store

```javascript
export const useAuthStore = defineStore('auth', { ... });
export const useToastStore = defineStore('toast', { ... });
```

### Pola backend

- Controller tipis → delegasi ke Service
- Validasi di Form Request classes
- Constructor injection untuk Service di Controller
- Activity logging via `RecordsActivity` trait pada Model
- API prefix versioning: `api/v1/{role}/...`

### Pola frontend

- `<script setup>` exclusively
- Data fetching di `onMounted` atau `watch`
- Toast untuk feedback: `toastStore.success()` / `toastStore.error()`
- Lazy import untuk route components: `() => import('...')`

---

## 14. Feature Development Guide

### Bagaimana menambahkan halaman baru

#### 1. File yang perlu dibuat

```
sms-frontend/src/pages/{role}/MyNewPage.vue     # Halaman
sms-frontend/src/services/modules/{role}/myService.js  # (jika butuh API baru)
```

#### 2. Route yang perlu ditambah

Edit `sms-frontend/src/router/index.js`:

```javascript
{
  path: '/teacher',
  component: MainLayout,
  meta: { requiresAuth: true, role: 'teacher' },
  children: [
    {
      path: 'my-feature',
      name: 'TeacherMyFeature',
      component: () => import('../pages/teacher/MyNewPage.vue'),
    },
  ],
},
```

#### 3. Service yang perlu dibuat

```javascript
// services/modules/teacher/myService.js
import api from '../../api';

export const myService = {
  getAll() {
    return api.get('/v1/teacher/my-endpoint');
  },
};
```

#### 4. Store yang perlu digunakan

- **Auth**: `useAuthStore()` jika butuh info user/role
- **Toast**: `useToastStore()` untuk feedback
- Buat store baru **hanya jika** state benar-benar global dan shared

#### 5. Komponen yang dapat digunakan ulang

- `BaseTable` — halaman list
- `BaseModal` — form create/edit
- `BaseSelect` — dropdown
- `ConfirmModal` — konfirmasi delete
- Ikuti pola card/button dari section UI & Design System

#### 6. Backend (jika endpoint baru)

```bash
php artisan make:controller API/Teacher/MyController
php artisan make:request Teacher/StoreMyRequest
# Tambah method di Service atau buat Service baru
```

Tambah route di `routes/api/v1/teacher.php` (atau role sesuai).

#### 7. Sidebar navigation

Edit `MainLayout.vue` — tambah item di `adminNav`, `teacherNav`, dll.

#### 8. Checklist

- [ ] Route + meta.role
- [ ] Service module
- [ ] Backend route + middleware
- [ ] Form Request validation
- [ ] Toast feedback
- [ ] Loading & empty states
- [ ] Responsive layout

---

## 15. Important Files

| File | Fungsi | Mengapa penting |
|------|--------|-----------------|
| `backend/bootstrap/app.php` | Registrasi route API, middleware alias | Pintu masuk semua API v1 & RBAC |
| `backend/app/Http/Controllers/API/AuthController.php` | Login, JWT, password flow | Core authentication |
| `backend/app/Models/User.php` | User model + JWT + relasi role | Identitas semua actor |
| `backend/app/Http/Middleware/RoleMiddleware.php` | RBAC backend | Proteksi per role |
| `backend/app/Http/Middleware/EnsurePasswordIsChanged.php` | Force password change | Security gate |
| `sms-frontend/src/router/index.js` | SPA routing + guards | Navigasi & akses frontend |
| `sms-frontend/src/stores/auth.js` | Session state | Login/logout/token |
| `sms-frontend/src/services/api.js` | Axios + interceptors | Semua HTTP communication |
| `sms-frontend/src/layouts/MainLayout.vue` | App shell + sidebar nav | Layout semua halaman auth |
| `sms-frontend/src/style.css` | Brand theme Tailwind | Design tokens global |
| `sms-frontend/vite.config.js` | Dev proxy API | Development workflow |
| `setup.md` | Setup guide | Onboarding environment |
| `backend/routes/api/v1/*.php` | Route definitions per role | API surface area |
| `backend/app/Providers/AppServiceProvider.php` | Rate limiting config | API throttling |
| `backend/config/auth.php` | JWT guard config | Auth driver setup |

---

## 16. Development Notes

### Technical debt yang ditemukan

| Area | Issue |
|------|-------|
| **Student frontend** | Hanya dashboard; sidebar link ke `/student/materials`, `/assignments`, `/grades` tidak ada route |
| **Principal frontend** | Dashboard placeholder; API backend ada tapi tidak dipanggil; `/principal/reports` tidak ada route |
| **Notifications** | `routes/api/v1/general.php` tidak didaftarkan di `bootstrap/app.php` |
| **assignmentService.js** | Method `submitGrade` didefinisikan dua kali (duplikat) |
| **Teacher router** | Beberapa child route pakai path absolut `/teacher/assignments` (inkonsisten dengan sibling) |
| **StudentProfile.vue** | Service import di-comment; halaman belum fungsional penuh |
| **HelloWorld.vue** | Komponen template Vite, tidak dipakai |
| **Principal nav** | Link laporan rapor di sidebar tanpa halaman |
| **Mixed API paths** | Avatar upload `/users/{id}/avatar` tanpa prefix `v1` (sengaja di `api.php`) |
| **Student dashboard** | Data statis/hardcoded, tidak call API |

### Potensi risiko

1. **RBAC frontend-only** — guard UI bisa di-bypass; backend middleware adalah proteksi real
2. **JWT di localStorage** — rentan XSS; pertimbangkan hardening jika production
3. **Rate limiting** — pastikan throttle middleware terpasang di route sensitif
4. **File upload** — perhatikan storage path & cleanup (`CleanOrphanedFiles` command ada)
5. **Academic year context** — banyak operasi bergantung tahun ajaran aktif; selalu validasi di backend
6. **Password default** — seeder pakai `password123`; wajib ganti di production

### Area perhatian sebelum fitur baru

1. **Cek role middleware** — endpoint baru harus di route group role yang benar
2. **Cek `password.changed`** — user dengan password default tidak bisa akses API normal
3. **Ikuti Service layer** — jangan taruh business logic di Controller
4. **Gunakan Form Request** — untuk validasi input
5. **RecordsActivity** — tambah trait ke Model baru jika perlu audit
6. **Reuse BaseTable/BaseModal** — konsistensi UI
7. **Toast feedback** — setiap operasi CRUD
8. **Throttling** — gunakan `throttle:upload-api` atau `heavy-api` untuk operasi berat
9. **Multi-file attachments** — Assignment & Material sudah support JSON array attachments
10. **Test dengan seeder** — `php artisan migrate --seed` untuk data development

### Perintah development umum

```bash
# Backend
cd backend
composer run dev          # server + queue + vite
php artisan serve         # API only
php artisan test          # run tests
vendor/bin/pint --dirty   # format PHP

# Frontend
cd sms-frontend
npm run dev               # Vite dev server
npm run build             # production build
```

---

## Quick Reference: Role → URL → API Prefix

| Role | Dashboard URL | API Prefix |
|------|---------------|------------|
| admin | `/admin/dashboard` | `/api/v1/admin` |
| teacher | `/teacher/dashboard` | `/api/v1/teacher` |
| student | `/student/dashboard` | `/api/v1/student` |
| principal | `/principal/dashboard` | `/api/v1/principal` |

---

*Dokumen ini dibuat berdasarkan analisis codebase pada Juni 2026. Perbarui dokumen ini saat ada perubahan arsitektur signifikan.*
