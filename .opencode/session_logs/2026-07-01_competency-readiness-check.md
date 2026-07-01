# Session: 2026-07-01 - Competency Readiness Check & UI Improvements

## Summary
Menambahkan pengecekan capaian kompetensi ke dalam readiness check sistem rapor, sehingga kelas tidak bisa dipublish dan PDF tidak bisa didownload jika ada mapel yang belum diatur capaian kompetensinya. Juga menambahkan label indikator "Belum Diatur Kompetensi" di halaman SubjectManagement, disable save button saat tidak ada perubahan di SubjectDetail, menambahkan onActivated hook ke SubjectManagement untuk fix stale data, meningkatkan fungsi refresh button di ReportManagement, dan menambahkan competency dirty flag agar ReportManagement otomatis refresh saat competency disimpan.

## Decisions Made
- **Competency check di readiness**: Kelas baru "Siap" jika semua mapel sudah punya competency setting per tahun ajaran. Ini memaksa admin mengatur competency sebelum publish/download.
- **PDF download diblokir**: Jika ada mapel tanpa competency, download PDF throw 422 error dengan pesan jelas nama mapel yang belum diatur.
- **Badge di SubjectManagement**: Menggunakan flag `has_competency` dari API ( ANY year ) — indikator visual bahwa mapel belum pernah diatur kompetensinya.
- **Save button disable**: Menggunakan snapshot pattern — bandingkan form current vs snapshot saat load, disable jika tidak ada perubahan.
- **onActivated di SubjectManagement**: Fix stale data karena keep-alive cache di MainLayout.vue.
- **Refresh button upgrade**: Refresh di dropdown detail sekarang juga refetch parent table (classesReadiness + classesSummary) secara paralel.
- **Competency dirty flag**: Tambah `competencies` ke dirtyFlags di globalDropdowns store. SubjectDetail panggil `invalidateCompetencies()` setelah save, ReportManagement cek di onActivated dan clear classDetailCache + refetch.

## Files Modified

### Backend
- `backend/app/Models/Subject.php`: Tambah `competencySettings()` relationship (hasMany → SubjectCompetencySetting)
- `backend/app/Http/Controllers/API/Admin/SubjectController.php`: Tambah field `has_competency` ke response index
- `backend/app/Services/ClassReadinessService.php`:
  - Import `SubjectCompetencySetting`
  - `getClassReadiness()`: Tambah competency check via `getCompetencyReadiness()`
  - `getClassReadinessDetail()`: Query competency per subject, tambah `competency_configured` flag, update `is_subject_ready`
  - `getStudentReadiness()`: Cek competency settings, tambah ke missing info
  - Baru: `getCompetencyReadiness()` method
- `backend/app/Services/AdminSemesterReportService.php`:
  - Import `SubjectCompetencySetting`
  - `getAcademicYearReadiness()`: Tambah `competency_ready` subquery ke SQL, update `$isReady` dan `missing_info`
  - `buildStudentReadinessPayload()`: Cek competency settings, tambah ke missing info
  - `downloadStudentPdf()`: Validasi competency sebelum generate PDF, throw 422 jika ada yang belum diatur

### Frontend
- `sms-frontend/src/stores/globalDropdowns.js`:
  - Tambah `competencies: false` ke dirtyFlags
  - Tambah `invalidateCompetencies()` method
  - Export `invalidateCompetencies`
- `sms-frontend/src/pages/admin/SubjectManagement.vue`:
  - Import `onActivated` dari vue
  - Tambah badge "Belum Diatur Kompetensi" di kolom nama mapel
  - Tambah `onActivated` hook untuk refetch data saat kembali dari SubjectDetail
- `sms-frontend/src/pages/admin/SubjectDetail.vue`:
  - Tambah `formSnapshot` ref
  - Tambah computed `hasChanges`
  - `fetchDetail()`: Set snapshot setelah load, handle default values
  - `saveCompetency()`: Reset snapshot setelah save + panggil `dropdowns.invalidateCompetencies()`
  - Save button: disabled saat `!hasChanges`, text berubah
- `sms-frontend/src/pages/admin/ReportManagement.vue`:
  - Tambah kolom "Capaian Kompetensi" di readiness detail table
  - Badge hijau/merah "Terkonfigurasi/Belum" per mapel
  - Update `colspan` empty state 7→8
  - Update tooltip `BasePopoverInfo` dengan penjelasan competency
  - `refreshClassDetail()`: Sekarang juga call `fetchClassesReadiness()` + clear cache
  - `onActivated()`: Cek `competenciesDirty` flag, clear classDetailCache + refetch jika ada perubahan

## Verification
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `php -l` syntax check — no errors
- [x] Semua file PHP dan Vue sudah di-review

## Pending Tasks
- [ ] Test manual: upload competency di SubjectDetail, cek badge hilang di SubjectManagement
- [ ] Test manual: coba download PDF tanpa competency → harus error 422
- [ ] Test manual: coba publish class tanpa competency → harus gagal
- [ ] Test manual: klik refresh button di dropdown → parent table juga refresh
- [ ] Test manual: simpan competency di SubjectDetail, kembali ke ReportManagement → data harus otomatis refresh

## Next Steps
1. Manual testing semua alur yang sudah diubah
2. Jika ada bug, fix di session berikutnya
