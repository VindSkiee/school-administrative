# Session: 2026-07-01 - UI Fixes & Attendance Recap Feature

## Summary
Sesi ini mencakup perbaikan UI pada ScheduleManagement dan TeacherGradebook, serta pembuatan fitur baru "Rekap Kehadiran" untuk guru. Perubahan meliputi: menghapus kolom No dan button Tutup dari modal pertemuan, menambahkan BasePopoverInfo sebagai panduan di tabel nilai, membuat halaman rekap kehadiran lengkap dengan backend controller, route, service, dan navigasi sidebar, serta memperbaiki masalah 403 Forbidden yang terjadi karena penggunaan globalDropdowns store yang memanggil endpoint admin.

## Decisions Made
- **Kolom "No" diubah labelnya menjadi "Pertemuan"**: User mengklarifikasi bahwa kolom tidak perlu dihapus, cukup labelnya diubah. Kolom dikembalikan dengan label yang sudah benar.
- **BasePopoverInfo ditambahkan di kedua tabel gradebook**: Standar mode (tabel nilai) dan homeroom mode (rekap kelas wali) keduanya mendapat panduan.
- **Halaman baru Rekap Kehadiran (bukan di Gradebook)**: Diputuskan membuat halaman terpisah karena gradebook sudah kompleks (~1143 baris) dan fokusnya berbeda (nilai vs kehadiran). Halaman rekap butuh view lintas waktu yang berbeda dari gradebook.
- **Backend endpoint menggunakan controller baru**: `AttendanceRecapController` dengan endpoint `GET /v1/teacher/attendance-recap` mengembalikan semua jadwal guru beserta status kehadiran per pertemuan.
- **Fix 403 Forbidden**: `AttendanceRecap.vue` sebelumnya menggunakan `useGlobalDropdownsStore()` yang memanggil `/v1/admin/academic-years` (endpoint admin). Diganti dengan panggilan langsung ke `/v1/teacher/gradebook/academic-years` (endpoint teacher).

## Files Modified

### Backend
- `backend/app/Http/Controllers/API/Teacher/AttendanceRecapController.php`: **Baru** — Controller untuk rekap kehadiran guru, mengembalikan jadwal + status session + progress bar
- `backend/routes/api/v1/teacher.php`: Ditambah route `GET /attendance-recap` + use statement

### Frontend
- `sms-frontend/src/pages/admin/ScheduleManagement.vue`: Kolom `meeting_number` label "Pertemuan" dipertahankan, button "Tutup" dihapus dari footer modal detail pertemuan
- `sms-frontend/src/pages/teacher/TeacherGradebook.vue`: Import + tambah `BasePopoverInfo` di header "Tabel Nilai" dan "Rekap Kelas Wali"
- `sms-frontend/src/pages/teacher/AttendanceRecap.vue`: **Baru** — Halaman rekap kehadiran (grid cards per jadwal, progress bar, status per pertemuan, tombol navigasi ke AttendancePanel)
- `sms-frontend/src/services/modules/teacher/attendanceRecapService.js`: **Baru** — Service layer untuk GET `/v1/teacher/attendance-recap`
- `sms-frontend/src/router/index.js`: Ditambah route `attendance-recap` → `TeacherAttendanceRecap`
- `sms-frontend/src/layouts/MainLayout.vue`: Ditambah item "Rekap Kehadiran" di sidebar teacher

## Verification
- [x] `vendor/bin/pint --dirty --format agent` — Berhasil, fixed concat_space, no_unused_imports, dll
- [x] `php artisan route:list --path=attendance-recap` — Route terdaftar dengan benar
- [x] Tidak ada perubahan pada flow absensi yang sudah ada
- [x] Sidebar navigation dan router guard berfungsi untuk teacher

## Pending Tasks
- None — semua task sudah selesai

## Next Steps
1. Test end-to-end: login sebagai teacher → akses Rekap Kehadiran → klik "Isi Absensi" → pastikan navigate ke AttendancePanel dengan tanggal yang benar
2. Pertimbangkan untuk membuat teacher endpoint sendiri untuk academic years (bukan bergantung pada `/v1/teacher/gradebook/academic-years`) jika diperlukan
3. Update `PROJECT_CONTEXT.md` dengan endpoint dan halaman baru
