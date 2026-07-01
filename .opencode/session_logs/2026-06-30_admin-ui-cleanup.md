# Session: 2026-06-30 - Admin UI Cleanup

## Summary
Melanjutkan sesi sebelumnya untuk cleanup halaman admin. Menghapus semua tombol "Batal" dari BaseModal di UserManagement, SubjectManagement, dan AcademicYearManagement agar konsisten dengan ClassManagement. Menambahkan disabled state pada edit button di ScheduleManagement saat schedule sudah memiliki data (absensi/tugas/materi/pertemuan), mengikuti pola yang sudah ada di delete button.

## Decisions Made
- **Hapus semua tombol "Batal"**: User harus klik X di modal untuk close. Konsisten dengan ClassManagement yang sudah dihapus sebelumnya.
- **Edit button disabled saat has_data**: Mengikuti pola delete button yang sudah ada. Tooltip menjelaskan alasan disable.

## Files Modified
- `sms-frontend/src/pages/admin/UserManagement.vue`: Hapus tombol "Batal" dari modal create/edit user (line 351-359)
- `sms-frontend/src/pages/admin/SubjectManagement.vue`: Hapus tombol "Batal" dari modal create/edit mapel (line 109-117)
- `sms-frontend/src/pages/admin/AcademicYearManagement.vue`: Hapus tombol "Batal" dari modal create/edit tahun ajaran (line 247-255)
- `sms-frontend/src/pages/admin/ScheduleManagement.vue`: Tambah `:disabled="item.has_data"` + conditional styling + tooltip pada edit button (line 195-201)

## Verification
- [x] Build frontend berhasil tanpa error
- [x] Semua edit button di admin pages sudah ter-handle

## Pending Tasks
- (none untuk sesi ini)

## Next Steps
- Lanjutkan fitur lain jika ada
- Cek apakah ada halaman teacher/student yang perlu di-cleanup juga
