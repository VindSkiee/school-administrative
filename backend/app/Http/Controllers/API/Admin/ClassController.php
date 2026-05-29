<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\AssignStudentRequest;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ClassService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClassController
{
    public function __construct(protected ClassService $classService) {}

    // READ (Daftar Kelas dengan relasi Tahun Ajaran & Wali Kelas)
    public function index(Request $request): JsonResponse
    {
        $query = SchoolClass::with(['academicYear', 'homeroomTeacher.user'])
            ->withCount('students'); // Otomatis menghitung jumlah siswa di dalam kelas

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('grade')) {
            $grade = $request->query('grade');
            $romanMap = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'];

            if (isset($romanMap[$grade])) {
                $roman = $romanMap[$grade];
                $query->where(function ($subQuery) use ($grade, $roman) {
                    $subQuery
                        ->whereRaw('UPPER(name) LIKE ?', [strtoupper($grade).'%'])
                        ->orWhereRaw('UPPER(name) LIKE ?', [strtoupper('KELAS '.$grade).'%'])
                        ->orWhereRaw('UPPER(name) LIKE ?', [$roman.'%'])
                        ->orWhereRaw('UPPER(name) LIKE ?', [strtoupper('KELAS '.$roman).'%']);
                });
            }
        }

        if ($request->query('per_page') === 'all') {
            return response()->json([
                'data' => $query->get(),
            ]);
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = max(1, min($perPage, 100));
        $classes = $query->paginate($perPage);

        return response()->json($classes);
    }

    // CREATE
    public function store(StoreClassRequest $request): JsonResponse
    {
        $class = SchoolClass::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dibuat.',
            'data' => $class,
        ], 201);
    }

    // READ DETAIL
    public function show(string $id): JsonResponse
    {
        $class = SchoolClass::with([
            'academicYear',
            'homeroomTeacher.user',
            'students' => function ($query) {
                $query->join('users', 'students.user_id', '=', 'users.id')
                    ->select('students.*')
                    ->orderBy('users.name')
                    ->with('user');
            },
            'schedules' => function ($query) {
                $query->orderByRaw("CASE day_of_week WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 WHEN 'sunday' THEN 7 ELSE 8 END")
                    ->orderBy('start_time')
                    ->with(['subject', 'teacher.user']);
            },
        ])->findOrFail($id);

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
            'data' => $class,
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
                'message' => 'Siswa berhasil dimasukkan ke dalam kelas.',
            ]);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
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
            'data' => $updatedClass,
        ]);
    }

    // DELETE
    public function destroy(string $id): JsonResponse
    {
        $class = SchoolClass::findOrFail($id);

        if ($class->students()->exists() || $class->schedules()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus kelas karena masih memiliki siswa atau jadwal ujian/pelajaran aktif.',
            ], 403);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }

    /**
     * BULK ACTION: Migrasi Kelas dari Ganjil ke Genap (Arsitektur Pivot)
     */
    public function migrateSemester(Request $request)
    {
        $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
        ]);

        try {
            DB::beginTransaction();

            // Eager load 'students' agar query ke database lebih efisien
            $oldClasses = SchoolClass::with('students')
                ->where('academic_year_id', $request->from_academic_year_id)
                ->get();

            if ($oldClasses->isEmpty()) {
                return response()->json(['message' => 'Tidak ada kelas di tahun ajaran asal.'], 404);
            }

            $migratedClassCount = 0;
            $migratedStudentCount = 0;

            foreach ($oldClasses as $oldClass) {
                // 1. Duplikasi kelas (atau gunakan kelas tujuan yang sudah ada)
                $newClass = SchoolClass::firstOrCreate(
                    [
                        'name' => $oldClass->name,
                        'academic_year_id' => $request->to_academic_year_id,
                    ],
                    [
                        'homeroom_teacher_id' => $oldClass->homeroom_teacher_id,
                    ]
                );

                // Jika kelas sudah ada tapi wali kelasnya kosong, update wali kelasnya
                if ($newClass->homeroom_teacher_id === null && $oldClass->homeroom_teacher_id) {
                    $newClass->update([
                        'homeroom_teacher_id' => $oldClass->homeroom_teacher_id,
                    ]);
                }

                // 2. Ambil semua ID siswa dari kelas lama (Ingat, PK student adalah user_id)
                $studentIds = $oldClass->students->pluck('user_id')->toArray();

                if (count($studentIds) > 0) {
                    // 3. Siapkan data pivot untuk di-sync ke kelas baru
                    $syncData = [];
                    foreach ($studentIds as $studentId) {
                        $syncData[$studentId] = [
                            'academic_year_id' => $request->to_academic_year_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // 4. Attach siswa ke kelas baru TANPA menghapus mereka dari kelas lama
                    // syncWithoutDetaching mencegah error jika siswa kebetulan sudah ada di kelas baru
                    $newClass->students()->syncWithoutDetaching($syncData);

                    $migratedStudentCount += count($studentIds);
                }

                $migratedClassCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil memigrasi $migratedClassCount kelas dan menyalin $migratedStudentCount siswa ke semester baru.",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat migrasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
