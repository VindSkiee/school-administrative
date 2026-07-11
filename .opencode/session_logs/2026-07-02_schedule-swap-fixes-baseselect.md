# Session: 2026-07-02 - Schedule Swap Fixes & BaseSelect Enhancements

## Summary

Perbaikan menyeluruh pada fitur pertukaran jadwal dan editing jadwal berdata. Fix validasi backend: `StoreScheduleRequest` sekarang skip required rules untuk teacher-only update, `ScheduleService::updateSchedule()` pindahkan `$data['academic_year_id']` setelah hasData check agar tidak terdeteksi sebagai disallowed field. Fix clash detection swap: `validateSwapClash()` sekarang exclude kedua schedule dari clash query (sebelumnya hanya exclude schedule yang sedang dicek, menyebabkan false positive). Tambahkan validasi durasi sama dan post-swap class/teacher clash check. Fix unique constraint collision: swap sekarang pakai 3-step atomic swap (placeholder 23:59) untuk hindari `unique_class_schedule` violation. Fix meeting session count: regenerate meeting sessions hanya dilakukan jika `day_of_week` berubah, bukan selalu. Frontend: tampilkan error inline di modal card (bukan toast) untuk teacher-only update errors, filter swap target by same class, BaseSelect dropdown support `direction="right"` dan `maxHeight` prop untuk modal pertukaran jadwal.

## Decisions Made

- **StoreScheduleRequest: teacher-only bypass** — Deteksi `$keys === ['teacher_id']` → hanya validasi teacher_id. Alasan: menghindari required validation error saat frontend kirim satu field saja.
- **Swap: 3-step atomic swap** — Step 1: A→23:59 (placeholder), Step 2: B→waktu A, Step 3: A→waktu B. Alasan: unique constraint `unique_class_schedule` pada `(class_id, day_of_week, start_time, academic_year_id)` akan collision jika langsung swap.
- **Swap: duration check** — Hitung durasi dalam menit, reject jika berbeda. Alasan: swap jadwal berdurasi berbeda akan mengubah alokasi waktu mengajar secara tidak adil.
- **Swap: conditional regenerate** — Hanya regenerate jika `day_of_week` berubah. Alasan: regenerate selalu akan duplikat session karena `generateMeetingSessions` generate dari start_date ke end_date tanpa skip existing.
- **Swap: exclude both schedules** — `validateSwapClash()` terima `$ignoreIds` array. Alasan: saat cek A, B masih di query → false positive "bentrok".
- **Inline error vs toast** — Teacher-only update error tampilkan di card (`formError` ref), bukan toast. Alasan: user bisa langsung lihat error dan pilih guru lain tanpa buka ulang modal.
- **BaseSelect direction prop** — Tambah `direction: 'down' | 'right'` dan `maxHeight` prop. Alasan: dropdown di swap modal perlu buka ke kanan dan lebih tinggi untuk daftar jadwal panjang.

## Files Modified

### Backend
- `backend/app/Http/Requests/Admin/StoreScheduleRequest.php`:
  - `rules()` — deteksi teacher-only update (`$keys === ['teacher_id']`), return hanya validasi teacher_id
- `backend/app/Services/ScheduleService.php`:
  - `updateSchedule()` — pindahkan `$data['academic_year_id']` SETELAH hasData check (fix: academic_year_id terdeteksi sebagai disallowed field)
  - `swapSchedules()` — tambah validasi same class, duration equal, post-swap clash check, 3-step atomic swap, conditional regenerate
  - +`validateSwapClash(Schedule, array, array)` — cek class + teacher clash setelah swap disimulasi, exclude both schedules
  - +`calculateDuration(string, string)` — hitung durasi dalam menit

### Frontend
- `sms-frontend/src/components/BaseSelect.vue`:
  - +`direction` prop (`'down'` | `'right'`, default: `'down'`)
  - +`maxHeight` prop (default: 240)
  - `calculatePosition()` — branch baru untuk direction `'right'`: posisi di kanan trigger, fallback kiri, clamp viewport
  - Template — dynamic transition (`translate-x` untuk right), dynamic margin class (`ml-1` untuk right), dynamic `<ul>` maxHeight dari prop
- `sms-frontend/src/pages/admin/ScheduleManagement.vue`:
  - +`formError` ref — state untuk inline error
  - `openModal()` — clear formError saat buka modal
  - `saveSchedule()` catch — teacher-only update error set formError (bukan toast)
  - Template — tambah `<div v-if="formError">` red error card di bawah amber warning
  - Teacher field BaseSelect — hapus `:disabled="isEditing && meetingHasData"` (guru selalu bisa diganti)
  - Swap modal BaseSelect — tambah `direction="right"` dan `:maxHeight="400"`
  - `swapTargetOptions` — filter by `class_id` (bukan `academic_year_id`)

## Verification
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `npm run build` — passed (all builds successful)
- [x] Teacher-only update validation: form request bypass ✓
- [x] Swap: duration check, class check, clash check ✓
- [x] Swap: 3-step atomic (avoid unique constraint) ✓
- [x] Swap: conditional regenerate (preserve session count) ✓
- [x] Inline error display in edit modal ✓
- [x] BaseSelect: direction="right" + maxHeight ✓

## Pending Tasks
- [ ] End-to-end testing dengan data seed
- [ ] Test swap same-day same-class schedules (verify meeting count preserved)
- [ ] Test swap different-day schedules (verify regenerate works)
- [ ] Test teacher-only edit: clash detection on new teacher
- [ ] Test BaseSelect direction="right" di swap modal

## Next Steps
1. Test swap flow: pilih dua jadwal kelas yang sama, hari yang sama, waktu berbeda — pastikan berhasil tanpa error
2. Test swap durasi beda — pastikan error "Durasi kedua jadwal harus sama"
3. Test edit guru pada jadwal berdata — pastikan inline error muncul jika guru baru bentrok
4. Verifikasi BaseSelect dropdown muncul di kanan pada swap modal
5. Jika semua test pass, fitur siap untuk integration testing
