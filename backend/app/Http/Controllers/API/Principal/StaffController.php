<?php

namespace App\Http\Controllers\API\Principal;

use App\Models\Admin;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController
{
    /**
     * GET /staff
     * Return all teachers and admins with their roles, homeroom class, and subjects.
     */
    public function index(Request $request): JsonResponse
    {
        // PERF FIX: replaced unbounded get() with pagination, sort pushed to DB
        $perPage = min((int) $request->query('per_page', 20), 100);

        // --- Teachers (DB-sorted via join) ---
        $teachers = Teacher::join('users', 'users.id', '=', 'teachers.user_id')
            ->with([
                'homeroomClass:id,homeroom_teacher_id,name',
                'schedules.subject:id,name',
            ])
            ->select('teachers.*')
            ->orderBy('users.name')
            ->get()
            ->map(function ($teacher) {
                $subjects = $teacher->schedules
                    ->pluck('subject')
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);

                return [
                    'id' => $teacher->user_id,
                    'name' => $teacher->user?->name ?? 'Tanpa Nama',
                    'nip' => $teacher->nip ?? '-',
                    'role' => 'teacher',
                    'role_label' => 'Guru',
                    'is_homeroom' => $teacher->homeroomClass !== null,
                    'homeroom_class_name' => $teacher->homeroomClass?->name,
                    'subjects' => $subjects,
                    'sort_name' => $teacher->user?->name ?? '',
                ];
            });

        // --- Admins ---
        $admins = Admin::join('users', 'users.id', '=', 'admins.user_id')
            ->with('user:id,name')
            ->select('admins.*')
            ->orderBy('users.name')
            ->get()
            ->map(function ($admin) {
                return [
                    'id' => $admin->user_id,
                    'name' => $admin->user?->name ?? 'Tanpa Nama',
                    'nip' => $admin->nip ?? '-',
                    'role' => 'admin',
                    'role_label' => 'Admin',
                    'is_homeroom' => false,
                    'homeroom_class_name' => null,
                    'subjects' => collect(),
                    'sort_name' => $admin->user?->name ?? '',
                ];
            });

        // PERF FIX: merge then paginate manually — combined sorted collection
        $allStaff = $teachers->concat($admins)->sortBy('sort_name')->values();

        $total = $allStaff->count();
        $page = max(1, (int) $request->query('page', 1));
        $items = $allStaff->forPage($page, $perPage)->values();
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
