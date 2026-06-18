<?php

namespace App\Http\Controllers\API\Principal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffController
{
    /**
     * GET /staff
     * Return all teachers and admins with their roles, homeroom class, and subjects.
     * PERF FIX: Uses raw UNION query with DB-level pagination instead of loading all records.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        // PERF FIX: Single UNION query at DB level with pagination
        // Combines teachers + admins, sorted by name, with LIMIT/OFFSET
        $staffQuery = "
            SELECT user_id, name, nip, role, role_label, is_homeroom, homeroom_class_name
            FROM (
                SELECT
                    t.user_id,
                    u.name,
                    t.nip,
                    'teacher' AS role,
                    'Guru' AS role_label,
                    CASE WHEN hc.id IS NOT NULL THEN 1 ELSE 0 END AS is_homeroom,
                    hc.name AS homeroom_class_name
                FROM teachers t
                INNER JOIN users u ON u.id = t.user_id
                LEFT JOIN classes hc ON hc.homeroom_teacher_id = t.user_id

                UNION ALL

                SELECT
                    a.user_id,
                    u.name,
                    a.nip,
                    'admin' AS role,
                    'Admin' AS role_label,
                    0 AS is_homeroom,
                    NULL AS homeroom_class_name
                FROM admins a
                INNER JOIN users u ON u.id = a.user_id
            ) AS combined
            ORDER BY name ASC
            LIMIT ? OFFSET ?
        ";

        $staffRows = DB::select($staffQuery, [$perPage, $offset]);

        // Count total
        $totalQuery = "
            SELECT COUNT(*) AS total FROM (
                SELECT t.user_id FROM teachers t
                UNION ALL
                SELECT a.user_id FROM admins a
            ) AS combined
        ";
        $total = DB::selectOne($totalQuery)->total;

        // PERF FIX: Collect teacher user_ids and admin user_ids for targeted eager loads
        $teacherUserIds = [];
        $adminUserIds = [];
        foreach ($staffRows as $row) {
            if ($row->role === 'teacher') {
                $teacherUserIds[] = $row->user_id;
            } else {
                $adminUserIds[] = $row->user_id;
            }
        }

        // PERF FIX: Bulk eager load only for the paginated results (not ALL teachers/admins)
        $teacherSubjects = [];
        if ($teacherUserIds) {
            $scheduleData = DB::table('schedules')
                ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
                ->whereIn('schedules.teacher_id', $teacherUserIds)
                ->select('schedules.teacher_id', 'subjects.id AS subject_id', 'subjects.name AS subject_name')
                ->get()
                ->groupBy('teacher_id');

            foreach ($scheduleData as $teacherId => $schedules) {
                $teacherSubjects[$teacherId] = $schedules
                    ->unique('subject_id')
                    ->map(fn ($s) => ['id' => $s->subject_id, 'name' => $s->subject_name])
                    ->values()
                    ->all();
            }
        }

        // Build response
        $items = collect($staffRows)->map(function ($row) use ($teacherSubjects) {
            return [
                'id' => $row->user_id,
                'name' => $row->name ?? 'Tanpa Nama',
                'nip' => $row->nip ?? '-',
                'role' => $row->role,
                'role_label' => $row->role_label,
                'is_homeroom' => (bool) $row->is_homeroom,
                'homeroom_class_name' => $row->homeroom_class_name,
                'subjects' => $row->role === 'teacher'
                    ? collect($teacherSubjects[$row->user_id] ?? [])
                    : collect(),
            ];
        });

        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
            ],
        ]);
    }
}
