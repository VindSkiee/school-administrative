<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\SchoolClass;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\AssignStudentRequest;
use App\Services\ClassService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassController
{
    public function __construct(protected ClassService $classService) {}

    // READ (Daftar Kelas dengan relasi Tahun Ajaran & Wali Kelas)
    public function index(Request $request): JsonResponse
    {
        $classes = SchoolClass::with(['academicYear', 'homeroomTeacher.user'])
            ->withCount('students') // Otomatis menghitung jumlah siswa di dalam kelas
            ->paginate(15);

        return response()->json($classes);
    }

    // CREATE
    public function store(StoreClassRequest $request): JsonResponse
    {
        $class = SchoolClass::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dibuat.',
            'data' => $class
        ], 201);
    }

    // READ DETAIL
    public function show(string $id): JsonResponse
    {
        $class = SchoolClass::with(['academicYear', 'homeroomTeacher.user', 'students.user'])->findOrFail($id);
        return response()->json($class);
    }

    // UPDATE
    public function update(StoreClassRequest $request, string $id): JsonResponse
    {
        $class = SchoolClass::findOrFail($id);
        $class->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil diperbarui.',
            'data' => $class
        ]);
    }

    // ASSIGN STUDENTS (Bulk Action)
    public function assignStudents(AssignStudentRequest $request, string $id): JsonResponse
    {
        $class = SchoolClass::findOrFail($id);
        
        try {
            $this->classService->assignStudents($class, $request->student_ids);
            
            return response()->json([
                'success' => true,
                'message' => 'Siswa berhasil dimasukkan ke dalam kelas.'
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menetapkan siswa.'], 500);
        }
    }

    // ASSIGN TEACHER (Wali Kelas)
    public function assignTeacher(Request $request, string $id): JsonResponse
    {
        $request->validate(['teacher_id' => 'required|exists:teachers,user_id']);
        
        $class = SchoolClass::findOrFail($id);
        $updatedClass = $this->classService->assignTeacher($class, $request->teacher_id);

        return response()->json([
            'success' => true,
            'message' => 'Wali kelas berhasil ditetapkan.',
            'data' => $updatedClass
        ]);
    }

    // DELETE
    public function destroy(string $id): JsonResponse
    {
        $class = SchoolClass::findOrFail($id);

        if ($class->students()->exists() || $class->schedules()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus kelas karena masih memiliki siswa atau jadwal ujian/pelajaran aktif.'
            ], 403);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus.'
        ]);
    }
}