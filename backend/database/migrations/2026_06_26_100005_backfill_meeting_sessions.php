<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Collect distinct (schedule_id, date) pairs from existing attendances
        $attendancePairs = DB::table('attendances')
            ->select('schedule_id', 'date')
            ->distinct()
            ->get();

        if ($attendancePairs->isEmpty()) {
            return;
        }

        // 2. Group by schedule_id to assign meeting_numbers per schedule
        $grouped = $attendancePairs->groupBy('schedule_id');

        $sessionsToInsert = [];

        foreach ($grouped as $scheduleId => $dateRecords) {
            $meetingNumber = 1;
            foreach ($dateRecords->sortBy('date') as $record) {
                $sessionsToInsert[] = [
                    'schedule_id' => $record->schedule_id,
                    'meeting_number' => $meetingNumber,
                    'date' => $record->date,
                    'status' => 'completed',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $meetingNumber++;
            }
        }

        // 3. Bulk insert all meeting sessions
        DB::table('meeting_sessions')->insert($sessionsToInsert);

        // 4. Build a lookup map: (schedule_id, date) → meeting_session_id
        $allSessions = DB::table('meeting_sessions')
            ->select('id', 'schedule_id', 'date')
            ->get()
            ->keyBy(fn ($s) => $s->schedule_id.'_'.$s->date);

        // 5. Update attendances in batches
        $attendances = DB::table('attendances')
            ->select('id', 'schedule_id', 'date')
            ->whereNull('meeting_session_id')
            ->get();

        $updates = [];
        foreach ($attendances as $att) {
            $key = $att->schedule_id.'_'.$att->date;
            if (isset($allSessions[$key])) {
                $updates[] = [
                    'id' => $att->id,
                    'meeting_session_id' => $allSessions[$key]->id,
                ];
            }
        }

        // Batch update using individual UPDATE statements for reliability
        foreach ($updates as $update) {
            DB::table('attendances')
                ->where('id', $update['id'])
                ->update(['meeting_session_id' => $update['meeting_session_id']]);
        }

        // 6. Update attendance_requests similarly
        $requests = DB::table('attendance_requests')
            ->select('id', 'schedule_id', 'date')
            ->whereNull('meeting_session_id')
            ->get();

        $requestUpdates = [];
        foreach ($requests as $req) {
            $key = $req->schedule_id.'_'.$req->date;
            if (isset($allSessions[$key])) {
                $requestUpdates[] = [
                    'id' => $req->id,
                    'meeting_session_id' => $allSessions[$key]->id,
                ];
            }
        }

        foreach ($requestUpdates as $update) {
            DB::table('attendance_requests')
                ->where('id', $update['id'])
                ->update(['meeting_session_id' => $update['meeting_session_id']]);
        }
    }

    public function down(): void
    {
        // Reset meeting_session_id columns to null
        DB::table('attendances')->update(['meeting_session_id' => null]);
        DB::table('attendance_requests')->update(['meeting_session_id' => null]);
        DB::table('meeting_sessions')->truncate();
    }
};
