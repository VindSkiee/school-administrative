# Session: 2026-07-05 - KKM Display & AssignmentList Redesign

## Summary
Displayed per-subject KKM (from `SubjectCompetencySetting.min_score`) on three frontend pages: AssignmentPanel (teacher creation form), TeacherAssignmentDetail (grading view), and StudentAssignments (student card). Redesigned AssignmentList teacher page with new grading-status tabs (Belum Dinilai/Sudah Dinilai/Semua), BaseSelect filters for type and class, and simplified cards showing graded count. Added `submissions_graded_count` to the backend globalIndex endpoint. Fixed duplicate "Semua Tipe" in BaseSelect by removing redundant "all" entries from options arrays.

## Decisions Made
- **KKM data flow**: Eager-load `subject.competencySettings` filtered by active academic year in all relevant endpoints, serialized as `competency_settings[]` in JSON
- **AssignmentList tabs**: Replaced deadline-based tabs (Aktif/Tenggat Selesai) with grading-status tabs (Belum Dinilai/Sudah Dinilai/Semua) — more actionable for teachers
- **BaseSelect placeholder pattern**: Use the `placeholder` prop as the "all" option instead of adding `{ value: "all", label: "Semua" }` to options array — prevents duplicate entries
- **Card simplification**: Removed deadline display from AssignmentList cards, added graded count (X/Y) — focus shifted to grading status
- **Late submissions**: Already visible in TeacherAssignmentDetail via red "Terlambat" badge — no changes needed

## Files Modified
- `backend/app/Http/Controllers/API/Teacher/AttendanceController.php`: Added `subject.competencySettings` eager load filtered by active academic year in `show()`
- `backend/app/Http/Controllers/API/Teacher/AssignmentController.php`: Added `submissions_graded_count` to `globalIndex()`, added `competencySettings` to `index()`, `submissions()`, `globalIndex()`
- `backend/app/Http/Controllers/API/Student/AssignmentController.php`: Added `competencySettings` eager load to `index()`
- `sms-frontend/src/pages/teacher/schedulePanel/AssignmentPanel.vue`: Added `subjectKKM` state, `getAssignmentKKM()` helper, KKM indicator in form and assignment cards, `getScheduleDetail` fetch
- `sms-frontend/src/pages/teacher/TeacherAssignmentDetail.vue`: Added `subjectKKM` computed, KKM badge next to type badge
- `sms-frontend/src/pages/student/StudentAssignments.vue`: Added `getSubjectKKM()` helper, KKM badge on exam-type cards
- `sms-frontend/src/pages/teacher/AssignmentList.vue`: Redesigned tabs (Belum Dinilai/Sudah Dinilai/Semua), added BaseSelect for type and class, simplified card with graded count, removed deadline display

## Verification
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `npm run build` — passed (all builds successful)

## Pending Tasks
- None — all tasks completed

## Next Steps
1. Run `php artisan migrate:fresh --seed` on dev environment to verify seeder data
2. End-to-end test: create UH → grade → below-KKM → remedial → report readiness
3. Test AssignmentList: verify "Belum Dinilai" tab shows correct ungraded assignments
4. Test KKM display on all three pages with real data
