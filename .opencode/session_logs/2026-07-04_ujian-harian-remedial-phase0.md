# Session: 2026-07-04 — Ujian Harian + Remedial Feature (Phase 0: Performance)

## Summary
Implemented Phase 0 performance optimizations in preparation for the ujian harian + remedial feature. Fixed N+1 query bug in AdminSemesterReportService, consolidated redundant queries in TeacherGradebookController, added missing database indexes, implemented caching on heavy endpoints, added rate limiting, and fixed a lazy-loading issue.

## Decisions Made
- **Bulk attendance pre-fetch** over per-schedule N+1 queries in report calculation
- **Cache 60s** for gradebook and homeroom recap endpoints (invalidated on grade save)
- **Cache key pattern**: `gradebook_{scheduleId}_{academicYearId}` and `homeroom_recap_{classId}_{academicYearId}`
- **throttle:heavy-api** (30/min) for gradebook and homeroom recap GET endpoints
- **Removed dead code**: `$schedulesBySubject` in homeroomRecap (defined but never used)

## Files Modified
- `backend/app/Services/AdminSemesterReportService.php` — Added `bulkFetchAttendanceRates()` method, replaced per-schedule N+1 with bulk pre-fetch
- `backend/app/Http/Controllers/API/Teacher/TeacherGradebookController.php` — Consolidated 3 schedule queries into 1, added Cache facade, wrapped index() and homeroomRecap() in Cache::remember(), added cache invalidation in inlineSave()
- `backend/app/Http/Controllers/API/Teacher/AssignmentController.php` — Added 'schedule' to eager load in submissions()
- `backend/routes/api/v1/teacher.php` — Added throttle:heavy-api middleware to gradebook and homeroomRecap routes
- `backend/database/migrations/2026_07_04_212421_add_student_id_indexes_to_submissions_and_attendances_table.php` — NEW: Adds [student_id] indexes to submissions and attendances tables

## Verification
- [x] PHP syntax check — all 4 backend files pass `php -l`
- [x] Pint formatting — passed
- [x] npm build — passed
- [ ] `php artisan migrate` — pending (indexes not yet applied)

## Phase 0 Impact Summary
| Metric | Before | After |
|--------|--------|-------|
| PDF 1 siswa | ~20 queries | ~6 queries |
| Gradebook load | 6 queries, no cache | 6 queries, 60s cache |
| Homeroom recap | 9 queries, no cache | 7 queries, 60s cache |
| submissions() | 2 queries (1 lazy) | 2 queries (0 lazy) |

## Next Steps
1. Run `php artisan migrate` to apply indexes
2. Proceed with Phase 1: Database layer for ujian harian + remedial
