# Session: 2026-07-19 - Eskul PIC Replacement in Teacher Deactivation

## Summary
Implemented PIC eskul replacement requirement when deactivating a teacher. Previously, the backend blindly cleared `teacher_id` to `null` on all eskuls when a teacher was deactivated. Now the admin must assign a new PIC for each eskul before deactivation — matching the existing pattern for schedules and homeroom class.

## Decisions Made
- **PIC scope is global** — eskul PIC is not per academic year, so no year-based filtering needed
- **Reuse `teacher_options` for eskul dropdown** — same list of active teachers works for all three replacement types
- **Direct DB update in transaction** — consistent with how schedule/homeroom replacements are done inline (not via service)
- **Backward compatible fallback** — if no `eskul_replacements` sent, still clears to null (existing behavior preserved)
- **Purple icon for eskul section** — visually distinct from blue (schedules) and orange (homeroom)

## Files Modified
- `backend/app/Http/Controllers/API/Admin/UserController.php`: Added `pic_eskuls` query + response in `teacherActiveSchedules()`, added `eskul_replacements` validation rules, added replacement logic in `update()` transaction
- `sms-frontend/src/components/TeacherReplacementModal.vue`: Added `picEskuls`/`eskulTeacherOptions` props, `eskulReplacements` reactive state, PIC eskul section template, updated `canConfirm` and `handleConfirm` payload
- `sms-frontend/src/pages/admin/UserManagement.vue`: Added `picEskuls`/`eskulTeacherOptions` to reactive state, populated from API response, passed new props to modal

## Verification
- `vendor/bin/pint --dirty --format agent` — passed
- `npm run build` — passed

## Pending Tasks
- None — feature is complete

## Next Steps
- Test the full deactivation flow with a teacher who is PIC of an eskul (requires running MySQL)
- Consider: should the toast message be updated to mention eskul? (currently says "jadwal digantikan")
