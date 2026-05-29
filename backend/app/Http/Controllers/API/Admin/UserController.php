<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Grade;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController
{
    public function __construct(protected UserService $userService) {}

    // READ (Daftar semua user dengan pagination & filter role)
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Opsional: Filter berdasarkan role (contoh: ?role=student)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->role === 'student') {
            $query->with(['student.classes']);
        } else {
            $query->with(['student', 'teacher', 'admin', 'principal']);
        }

        // TANGKAP PERMINTAAN 'ALL' UNTUK KEBUTUHAN DROPDOWN FRONTEND
        if ($request->query('per_page') === 'all') {
            return response()->json([
                'data' => $query->get(), // Dibungkus 'data' agar format response sama dengan paginate
            ]);
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 100));
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    // CREATE (Sudah kita buat, ditambah penanganan error HttpException dari Service)
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'User berhasil didaftarkan.',
                'data' => $user,
            ], 201);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat user: '.$e->getMessage()], 500);
        }
    }

    // READ (Detail satu user)
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'student') {
            $user->load([
                'student',
                'student.classes',
                'student.classes.academicYear',
            ]);

            if (Schema::hasTable('grades')) {
                $gradeHistory = Grade::query()
                    ->whereHas('submission', function ($query) use ($user) {
                        $query->where('student_id', $user->id);
                    })
                    ->with([
                        'submission.assignment.schedule.subject',
                        'submission.assignment.schedule.schoolClass',
                        'submission.assignment.schedule.academicYear',
                    ])
                    ->latest('id')
                    ->get()
                    ->map(static function (Grade $grade): array {
                        $schedule = $grade->submission?->assignment?->schedule;
                        $subject = $schedule?->subject;
                        $class = $schedule?->schoolClass;
                        $academicYear = $schedule?->academicYear;

                        return [
                            'grade_id' => $grade->id,
                            'score' => $grade->score,
                            'feedback' => $grade->feedback,
                            'graded_at' => $grade->updated_at,
                            'assignment_id' => $grade->submission?->assignment?->id,
                            'assignment_title' => $grade->submission?->assignment?->title,
                            'subject_id' => $subject?->id,
                            'subject_name' => $subject?->name,
                            'class_id' => $class?->id,
                            'class_name' => $class?->name,
                            'academic_year_id' => $academicYear?->id,
                            'academic_year_name' => $academicYear?->name,
                            'semester' => $academicYear?->semester,
                        ];
                    })
                    ->values();

                $user->setAttribute('grade_history', $gradeHistory);
            }
        }

        if ($user->role === 'teacher') {
            $user->load([
                'teacher',
                'teacher.schedules.schoolClass',
                'teacher.schedules.subject',
                'teacher.schedules.academicYear',
            ]);
        }

        if ($user->role === 'admin') {
            $user->load('admin');
        }

        if ($user->role === 'principal') {
            $user->load('principal');
        }

        return response()->json($user);
    }

    public function resetPassword(string $id): JsonResponse
    {
        $user = User::with(['student', 'teacher', 'admin', 'principal'])->findOrFail($id);

        $defaultPassword = 'password123';

        if ($user->role === 'student') {
            $defaultPassword = $user->student?->nisn ?: ($user->student?->nis ?: $defaultPassword);
        }

        if (in_array($user->role, ['teacher', 'admin', 'principal'], true)) {
            $defaultPassword = $user->teacher?->nip
                ?: ($user->admin?->nip ?: ($user->principal?->nip ?: $defaultPassword));
        }

        $user->update([
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset ke default.',
        ]);
    }

    // UPDATE
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::with(['student', 'teacher', 'admin', 'principal'])->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:8',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        $role = $user->role;
        $profileRules = [];

        if ($role === 'student') {
            $profileRules = [
                'nisn' => ['sometimes', 'string', 'max:50', Rule::unique('students', 'nisn')->ignore($user->id, 'user_id')],
                'nis' => ['sometimes', 'string', 'max:50', Rule::unique('students', 'nis')->ignore($user->id, 'user_id')],
                'gender' => ['sometimes', Rule::in(['L', 'P'])],
            ];
        } elseif ($role === 'teacher') {
            $profileRules = [
                'nip' => ['sometimes', 'string', 'max:50', Rule::unique('teachers', 'nip')->ignore($user->id, 'user_id')],
            ];
        } elseif ($role === 'admin') {
            $profileRules = [
                'nip' => ['sometimes', 'string', 'max:50', Rule::unique('admins', 'nip')->ignore($user->id, 'user_id')],
            ];
        } elseif ($role === 'principal') {
            $profileRules = [
                'nip' => ['sometimes', 'string', 'max:50', Rule::unique('principals', 'nip')->ignore($user->id, 'user_id')],
            ];
        }

        if (! empty($profileRules)) {
            $profileData = $request->validate($profileRules);

            if ($role === 'student') {
                $studentData = array_filter([
                    'nisn' => $profileData['nisn'] ?? null,
                    'nis' => $profileData['nis'] ?? null,
                    'gender' => $profileData['gender'] ?? null,
                ], static fn ($value) => $value !== null);

                if (! empty($studentData)) {
                    $user->student()->updateOrCreate(['user_id' => $user->id], $studentData);
                }
            } elseif (isset($profileData['nip'])) {
                $nipData = ['nip' => $profileData['nip']];

                if ($role === 'teacher') {
                    $user->teacher()->updateOrCreate(['user_id' => $user->id], $nipData);
                } elseif ($role === 'admin') {
                    $user->admin()->updateOrCreate(['user_id' => $user->id], $nipData);
                } elseif ($role === 'principal') {
                    $user->principal()->updateOrCreate(['user_id' => $user->id], $nipData);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui',
            'data' => $user->load(['student', 'teacher', 'admin', 'principal']),
        ]);
    }

    // DELETE (Soft Delete / Deactivate)
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Mencegah admin menghapus dirinya sendiri
        if (auth('api')->id() == $user->id) {
            return response()->json(['error' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 403);
        }

        $user->update(['is_active' => false]);
        $user->delete(); // Memicu SoftDelete

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dinonaktifkan.',
        ]);
    }
}
