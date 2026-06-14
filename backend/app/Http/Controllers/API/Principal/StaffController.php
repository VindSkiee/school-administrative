<?php

namespace App\Http\Controllers\API\Principal;

use App\Models\Admin;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class StaffController
{
    /**
     * GET /staff
     * Return all teachers and admins with their roles, homeroom class, and subjects.
     */
    public function index(): JsonResponse
    {
        // --- Teachers ---
        $teachers = Teacher::with(['user:id,name', 'homeroomClass:id,homeroom_teacher_id,name', 'schedules.subject:id,name'])
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
                ];
            });

        // --- Admins ---
        $admins = Admin::with('user:id,name')
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
                ];
            });

        // Merge and sort by name
        $staff = $teachers->concat($admins)->sortBy('name')->values();

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }
}
