# Session: 2026-07-01 - Teacher Replacement & Schedule Swap

## Summary

Implementasi lengkap sistem penggantian guru saat nonaktifkan akun dan fitur tukar jadwal (schedule swap). Fitur pertama: ketika admin nonaktifkan guru yang punya jadwal/kelas perwalian, muncul modal yang menampilkan jadwal aktif + kelas perwalian, admin wajib pilih guru pengganti terpisah untuk jadwal dan wali kelas sebelum nonaktifkan bisa dilakukan. Fitur kedua: admin bisa menukar waktu (day/time) antara dua jadwal dengan validasi clash, dan bisa mengganti guru pada jadwal yang sudah ada data absensi. Semua operasi memiliki validasi ketat untuk mencegah clash data.

## Decisions Made

- **Teacher replacement: 2 opsi terpisah** — `teacher_options` untuk jadwal (semua guru aktif) dan `homeroom_options` untuk wali kelas (exclude guru yang sudah jadi wali kelas lain). Alasan: satu guru hanya bisa jadi wali kelas satu kelas.
- **Clash detection tetap di backend** — Tidak pre-filter guru sibuk di list dropdown. Alasan: query lebih ringan, admin langsung tahu setelah konfirmasi, error message sudah jelas.
- **Schedule swap pakai swap method** — Bukan edit biasa. Alasan: dalam jadwal penuh (7-15), edit biasa akan clash di intermediate step. Swap langsung menukar tanpa clash.
- **Teacher change on has_data schedules** — Hanya `teacher_id` yang bisa diubah, field lain terkunci. Alasan: data historis absensi tetap valid, yang berubah hanya siapa yang mengajar mendatang.
- **Swap blocked jika ada absensi mendatang** — Jika ada `meeting_sessions` dengan `date >= today` yang sudah punya `attendances`, swap diblokir. Alasan: data sudah final untuk pertemuan tersebut.
- **ValidationException::withMessages()** — Ganti `new ValidationException()` yang salah parameter causing 500 error.

## Files Modified

### Backend
- `backend/app/Http/Controllers/API/Admin/UserController.php`:
  - +`teacherActiveSchedules()` method — return jadwal aktif, kelas perwalian, teacher_options, homeroom_options
  - `update()` — terima `schedule_replacements[]` + `homeroom_replacement`, validasi clash, proses reassignment dalam DB transaction
- `backend/app/Http/Controllers/API/Admin/ScheduleController.php`:
  - +`swap()` endpoint — POST schedules/swap
- `backend/app/Services/ScheduleService.php`:
  - `updateSchedule()` — izinkan teacher-only change saat has_data, validasi clash untuk guru baru
  - +`swapSchedules()` — swap day/time dua schedule, cek upcoming attendance, regenerate meeting sessions
- `backend/routes/api/v1/admin.php`:
  - +`GET users/{id}/teacher-active-schedules`
  - +`POST schedules/swap`

### Frontend
- `sms-frontend/src/components/TeacherReplacementModal.vue`:
  - **Baru** — modal dengan 2 section: Jadwal Mengajar (dropdown per jadwal) + Wali Kelas (dropdown terpisah)
- `sms-frontend/src/services/modules/admin/userService.js`:
  - +`getTeacherActiveSchedules(id)` method
- `sms-frontend/src/services/modules/admin/scheduleService.js`:
  - +`swap(id1, id2)` method
- `sms-frontend/src/pages/admin/UserManagement.vue`:
  - +import TeacherReplacementModal
  - +replacementModal state
  - `promptDeactivateUser()` → fetch jadwal dulu → tampilkan TeacherReplacementModal jika guru punya jadwal/kelas perwalian
  - +`handleReplacementConfirm()` — kirim schedule_replacements + homeroom_replacement
- `sms-frontend/src/pages/admin/ScheduleManagement.vue`:
  - +swap modal UI (BaseModal dengan preview hasil pertukaran)
  - +swap button (icon swap-horizontal) di actions column
  - edit button selalu aktif — jika has_data, mode teacher-only
  - warning message "Hanya Guru Pengajar yang dapat diganti"
  - +swap state: isSwapModalOpen, swapScheduleA, swapScheduleBId, swapError
  - +swap functions: openSwapModal, executeSwap, swapTargetOptions, swapPreview
  - `saveSchedule()` — handle teacher-only update saat meetingHasData

## Verification
- [x] `vendor/bin/pint --dirty --format agent` — passed (all PHP files formatted)
- [x] `php artisan route:list` — routes registered correctly
- [x] `php artisan test --compact` — 1 failure (pre-existing ExampleTest, unrelated)
- [x] Import `Validator` dihapus dari UserController (unused after fix)

## Pending Tasks
- [ ] Testing end-to-end dengan data seed (teacher replacement flow)
- [ ] Testing end-to-end dengan data seed (schedule swap flow)
- [ ] Testing edge case: swap schedule yang sudah ada absensi mendatang
- [ ] Testing edge case: teacher clash detection saat ganti guru pada jadwal berdata
- [ ] Testing: homeroom_options exclude guru yang sudah jadi wali kelas lain

## Next Steps
1. Jalankan `php artisan migrate --seed` untuk populate data test
2. Test alur nonaktifkan guru: pastikan modal muncul, dropdown guru pengganti terpisah untuk jadwal dan wali kelas
3. Test alur tukar jadwal: pilih dua jadwal, preview tampil, swap berhasil tanpa clash
4. Test edit guru pada jadwal berdata: pastikan hanya field guru yang aktif
5. Jika ada bug, fix di session berikutnya
