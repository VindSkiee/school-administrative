# Session: 2026-06-30 - Schedule Per-Day Slots, Clash Detection & UI Fixes

## Summary
Fixed meeting count discrepancy in ClassReadinessService by adding `date <= CURDATE()` filter to `getClassReadinessDetail()` SQL — previously the detail view counted ALL non-holiday sessions (including future), causing 10/19 vs 10/20 inconsistency between Monday (which has a holiday on Aug 17) and other days. Added BasePopoverInfo to the expanded per-class detail dropdown in ReportManagement.vue explaining the X/Y columns (kehadiran, tugas, UTS, UAS). Refactored ScheduleManagement.vue from single time inputs to per-day time slots (`form.day_slots` array), enabling admin to create one subject with multiple schedules on different days with different times (e.g., Matematik on Senin 07:00 and Rabu 10:00). Added dynamic client-side clash detection that warns in real-time when selected day+time conflicts with existing schedules. Fixed BaseTimePicker.vue vertical overflow — dropdown now flips upward when near viewport bottom.

## Decisions Made
- **`getClassReadinessDetail()` uses same date filter as `getAttendanceReadiness()`**: Both views should count only sessions up to today, not future sessions. This ensures consistency across all readiness displays.
- **Per-day time slots use multi-call create**: Frontend iterates `form.day_slots` and sends one API call per slot with `days: [slot.day]`. Backend `ScheduleService::createSchedule()` already supports this — no backend changes needed.
- **Clash detection is client-side only**: Checks existing `schedules.value` against form values. Backend `validateClash()` remains the final safeguard. Client-side provides instant UX feedback.
- **BaseTimePicker vertical flip uses `offsetHeight`**: Measures actual panel height after render, falls back to 470px. Checks `spaceBelow` vs `spaceAbove` to decide direction.

## Files Modified
- `backend/app/Services/ClassReadinessService.php`: Added `$today = now()->toDateString()` and `AND ms.date <= ?` filter to both `total_meetings` and `recorded_meetings` subqueries in `getClassReadinessDetail()` (lines 95-112)
- `sms-frontend/src/pages/admin/ReportManagement.vue`: Imported `BasePopoverInfo`, added it next to "Detail Kesiapan" heading in expanded detail dropdown with explanations for Kehadiran X/Y, Tugas/UTS/UAS X/Y, and Status columns
- `sms-frontend/src/pages/admin/ScheduleManagement.vue`: Replaced `form.start_time`/`form.end_time` with `form.day_slots` array; replaced single time grid template with per-day time slot rows; added `watch(form.days)` to sync checkboxes ↔ slots; added `getSlotClash()` function for dynamic clash detection; updated `openModal()` for create/edit initialization; updated `saveSchedule()` for multi-call create with per-slot validation
- `sms-frontend/src/components/BaseTimePicker.vue`: Enhanced `calculatePosition()` with vertical flip — reads `dropdownPanel.value.offsetHeight`, calculates `spaceBelow`/`spaceAbove`, opens upward when insufficient space below

## Verification
- [x] `npm run build` — all passes (no errors)
- [ ] `vendor/bin/pint --dirty --format agent` — needs manual run on Windows (PHP not available in WSL)
- [ ] Manual test: schedule form — select multiple days, verify per-day time inputs appear
- [ ] Manual test: clash detection — verify warning appears when time conflicts with existing schedule
- [ ] Manual test: BaseTimePicker — verify dropdown flips upward when trigger is near viewport bottom
- [ ] Manual test: ReportManagement — verify BasePopoverInfo hover/click shows explanations

## Pending Tasks
- Run `vendor/bin/pint --dirty --format agent` on backend
- Run `php artisan migrate:fresh --seed` to verify full flow
- Manual testing of all new features

## Next Steps
1. Run pint and verify frontend builds locally
2. Test schedule creation: create Matematik on Senin 07:00 and Rabu 10:00 — verify 2 separate schedules with separate meeting sessions
3. Test clash detection: create schedule at same time as existing — verify amber warning appears
4. Test BaseTimePicker: open dropdown at bottom of page — verify it flips upward
5. Test ReportManagement: hover over info icon in expanded detail — verify popover with explanations
