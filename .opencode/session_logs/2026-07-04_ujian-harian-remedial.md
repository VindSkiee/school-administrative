# Session: 2026-07-04 — Ujian Harian + Remedial System

## Summary
Implemented full "Ujian Harian" (daily exam) assignment type with a complete remedial system for all exam types (ujian harian, UTS, UAS), including database schema, backend services, frontend grading settings, teacher assignment panels, and gradebook integration. All 7 phases completed.

## Decisions Made
- **Remedial scoring**: 3 modes — replace (max of exam/remedial), average (mean), custom (teacher manual score)
- **Default weights redistribution**: task 30 / ujian_harian 10 / uts 25 / uas 25 / attendance 10 (was 40/25/25/10)
- **Cache strategy**: 60s TTL for gradebook + homeroom recap, invalidated on grade save via `inlineSave()`
- **Remedial architecture**: Single remedial assignment per parent exam, auto-creates empty submissions for target students, stores `remedial_mode` on parent grade's record
- **`daily_exam_weight` default**: 10% (admin/principal can configure)
- **Remedial applies to**: `ujian_harian`, `uts`, and `uas` types (not `task`)
- **MySQL not running locally**: Migrations created but pending execution on dev environment

## Files Modified
### Backend (13 files)
- `backend/app/Services/GradeAggregationService.php`: Added `ujian_harian` to DEFAULT_WEIGHTS, scoresByType, walking average, `resolveRemedialScore()`, `resolveParentType()`, `findParentAssignmentId()`
- `backend/app/Services/AdminSemesterReportService.php`: Added `ujian_harian` to weights + scoresByType, N+1 fix (`bulkFetchAttendanceRates()`), remedial lookup resolution
- `backend/app/Services/AssignmentService.php`: Added `getStudentsBelowKKM()` and `createRemedialAssignments()` (DB transaction)
- `backend/app/Http/Controllers/API/Teacher/AssignmentController.php`: Added `belowKKM()` and `createRemedial()` endpoints, fixed lazy-load of schedule in `submissions()`
- `backend/app/Http/Controllers/API/Teacher/TeacherGradebookController.php`: Added `ujian_harian` to weights + type filter, cache `Cache::remember(60s)`, cache invalidation, `throttle:heavy-api`
- `backend/app/Http/Controllers/API/Admin/GradingSettingController.php`: Added `daily_exam_weight` to updateOrCreate
- `backend/app/Http/Controllers/API/Principal/SettingController.php`: Added `daily_exam_weight` to get/update + weight sum validation
- `backend/app/Http/Requests/Teacher/StoreAssignmentRequest.php`: Added `ujian_harian` to type enum, `enable_remedial` + `remedial_mode` optional fields
- `backend/app/Http/Requests/Admin/StoreGradingSettingRequest.php`: Added `daily_exam_weight`, updated weight sum to 5 components
- `backend/app/Models/GradingSetting.php`: Added `daily_exam_weight` to fillable/casts
- `backend/app/Models/Assignment.php`: Added `is_remedial`, `remedial_for_type`, `linked_assignment_id` + relationships
- `backend/app/Models/Grade.php`: Added `remedial_mode`, `custom_score` to fillable
- `backend/routes/api/v1/teacher.php`: Added `GET /assignments/{id}/below-kkm` and `POST /assignments/{id}/create-remedial`, `throttle:heavy-api` middleware

### Migrations (4 files)
- `backend/database/migrations/2026_07_04_212421_add_student_id_indexes_to_submissions_and_attendances.php`: student_id indexes
- `backend/database/migrations/2026_07_04_213226_add_daily_exam_weight_to_grading_settings.php`: daily_exam_weight column
- `backend/database/migrations/2026_07_04_213258_add_remedial_columns_to_assignments.php`: remedial columns + indexes
- `backend/database/migrations/2026_07_04_213335_add_remedial_mode_to_grades.php`: remedial_mode + custom_score

### Frontend (4 files)
- `sms-frontend/src/pages/principal/PrincipalGradingSettings.vue`: Added "Bobot Ujian Harian" input, grid-cols-3, updated weights payload
- `sms-frontend/src/pages/teacher/schedulePanel/AssignmentPanel.vue`: Added Ujian Harian radio option, remedial UI (checkbox + mode radios), form data updates
- `sms-frontend/src/pages/teacher/TeacherAssignmentDetail.vue`: Added below-KKM section, remedial creation UI, `getBelowKKM()`/`createRemedial()` calls
- `sms-frontend/src/pages/teacher/TeacherGradebook.vue`: Added `[UH]` badge, `ujian_harian` in assignmentColumns, typeLabel, typeColor, calculateWeightedAverage, weights display
- `sms-frontend/src/services/modules/teacher/assignmentService.js`: Added `getBelowKKM()` and `createRemedial()` methods

## Verification
- All PHP syntax checks (`php -l`) passed for all modified files
- `vendor/bin/pint --dirty` passed on all modified files
- `npm run build` passed after all changes
- No admin grading settings page exists that needed updating (only PrincipalGradingSettings.vue)

## Pending Tasks
- Run `php artisan migrate` on dev environment (MySQL not running locally)
- End-to-end testing with seeder data
- Optional: Admin grading settings page (if separate from principal)

## Next Steps
1. Run `php artisan migrate --seed` on dev environment
2. Test full flow: create ujian harian → grade students → check below-KKM → create remedial → grade remedial → verify gradebook weighted average
3. Optional: Add admin grading settings page if needed
