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
                
                // REFACTOR: Gunakan Eloquent biasa, jangan gunakan DB::raw() atau UPPER()
                // karena MySQL secara default sudah case-insensitive (utf8_general_ci / utf8mb4_unicode_ci)
                $query->where(function ($subQuery) use ($grade, $roman) {
                    $subQuery->where('name', 'like', $grade . '%')
                             ->orWhere('name', 'like', 'KELAS ' . $grade . '%')
                             ->orWhere('name', 'like', $roman . '%')
                             ->orWhere('name', 'like', 'KELAS ' . $roman . '%');
                });
            }
        }

        // PERF FIX: removed per_page=all branch, capped max at 200
        $perPage = min((int) $request->query('per_page', 20), 200);
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
     * POST /classes/migrate-semester
     * Bulk migrate classes from odd to even semester (duplicate classes, students, and schedules).
     * Uses raw queries for maximum performance.
     */
    public function migrateSemester(Request $request)
    {
        $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
        ]);

        $fromYearId = (int) $request->from_academic_year_id;
        $toYearId = (int) $request->to_academic_year_id;

        try {
            DB::beginTransaction();

            // 1. Fetch old classes (raw query for performance)
            $oldClasses = DB::table('classes')
                ->where('academic_year_id', $fromYearId)
                ->get();

            if ($oldClasses->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada kelas di tahun ajaran asal.'], 404);
            }

            $now = now()->toDateTimeString();
            $migratedClassCount = 0;
            $migratedStudentCount = 0;
            $classIdMap = []; // old_id => new_id

            foreach ($oldClasses as $oldClass) {
                // 2. Upsert class (firstOrCreate equivalent with raw)
                $existingNew = DB::table('classes')
                    ->where('name', $oldClass->name)
                    ->where('academic_year_id', $toYearId)
                    ->first();

                if (! $existingNew) {
                    $newClassId = DB::table('classes')->insertGetId([
                        'name' => $oldClass->name,
                        'academic_year_id' => $toYearId,
                        'homeroom_teacher_id' => $oldClass->homeroom_teacher_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $newClassId = $existingNew->id;
                    // Update homeroom if empty
                    if ($existingNew->homeroom_teacher_id === null && $oldClass->homeroom_teacher_id) {
                        DB::table('classes')
                            ->where('id', $newClassId)
                            ->update(['homeroom_teacher_id' => $oldClass->homeroom_teacher_id, 'updated_at' => $now]);
                    }
                }

                $classIdMap[$oldClass->id] = $newClassId;
                $migratedClassCount++;
            }

            // 3. Bulk insert student pivots (single raw query per class)
            foreach ($oldClasses as $oldClass) {
                $newClassId = $classIdMap[$oldClass->id];

                $studentIds = DB::table('class_student')
                    ->where('class_id', $oldClass->id)
                    ->pluck('student_id');

                if ($studentIds->isNotEmpty()) {
                    $pivotRows = [];
                    foreach ($studentIds as $studentId) {
                        $pivotRows[] = [
                            'class_id' => $newClassId,
                            'student_id' => $studentId,
                            'academic_year_id' => $toYearId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Insert ignoring duplicates (INSERT IGNORE for MySQL)
                    foreach (array_chunk($pivotRows, 100) as $chunk) {
                        DB::table('class_student')->insertOrIgnore($chunk);
                    }

                    $migratedStudentCount += $studentIds->count();
                }
            }

            // 4. Bulk duplicate schedules (map class_id to new class_id)
            foreach ($classIdMap as $oldClassId => $newClassId) {
                $oldSchedules = DB::table('schedules')
                    ->where('class_id', $oldClassId)
                    ->where('academic_year_id', $fromYearId)
                    ->get();

                if ($oldSchedules->isNotEmpty()) {
                    $scheduleRows = [];
                    foreach ($oldSchedules as $sched) {
                        $scheduleRows[] = [
                            'class_id' => $newClassId,
                            'subject_id' => $sched->subject_id,
                            'teacher_id' => $sched->teacher_id,
                            'academic_year_id' => $toYearId,
                            'day_of_week' => $sched->day_of_week,
                            'start_time' => $sched->start_time,
                            'end_time' => $sched->end_time,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table('schedules')->insert($scheduleRows);
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil memigrasi $migratedClassCount kelas, menyalin $migratedStudentCount siswa, dan menduplikasi jadwal ke semester baru.",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat migrasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /classes/migrate-class
     * Promote students to next grade level (7→8, 8→9, 9→alumni).
     * Creates new classes for the next academic year WITHOUT homeroom teacher and schedules.
     * Only works when active year is EVEN semester. Auto-activates the new year after success.
     * Uses raw queries for maximum performance.
     */
    public function migrateClass(Request $request)
    {
        $request->validate([
            'to_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $toYearId = (int) $request->to_academic_year_id;

        try {
            DB::beginTransaction();

            // 1. Find current active year (must be even)
            $activeYear = DB::table('academic_years')->where('is_active', true)->first();

            if (! $activeYear) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada tahun ajaran aktif.'], 422);
            }

            if ($activeYear->semester !== 'even') {
                DB::rollBack();
                return response()->json(['message' => 'Migrasi kelas hanya dapat dilakukan pada semester Genap.'], 422);
            }

            // 2. Verify target year is not yet active and is different
            $targetYear = DB::table('academic_years')->where('id', $toYearId)->first();

            if (! $targetYear) {
                DB::rollBack();
                return response()->json(['message' => 'Tahun ajaran tujuan tidak ditemukan.'], 404);
            }

            if ($targetYear->is_active) {
                DB::rollBack();
                return response()->json(['message' => 'Tahun ajaran tujuan sudah aktif. Gunakan tahun ajaran yang belum aktif.'], 422);
            }

            if ($targetYear->id === $activeYear->id) {
                DB::rollBack();
                return response()->json(['message' => 'Tahun ajaran tujuan tidak boleh sama dengan tahun ajaran aktif.'], 422);
            }

            $now = now()->toDateTimeString();
            $fromYearId = $activeYear->id;

            // 3. Fetch all classes from current even year
            $oldClasses = DB::table('classes')->where('academic_year_id', $fromYearId)->get();

            if ($oldClasses->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada kelas di tahun ajaran aktif.'], 404);
            }

            $promotedCount = 0;
            $graduatedCount = 0;
            $newClassCount = 0;

            // Grade promotion map
            $gradeMap = ['7' => '8', '8' => '9'];

            foreach ($oldClasses as $oldClass) {
                $grade = $this->extractGrade($oldClass->name);
                $suffix = $this->extractSuffix($oldClass->name);

                // Grade 9 students become alumni
                if ($grade === '9') {
                    // Mark all students in this class as graduated
                    $studentIds = DB::table('class_student')
                        ->where('class_id', $oldClass->id)
                        ->pluck('student_id');

                    if ($studentIds->isNotEmpty()) {
                        DB::table('students')
                            ->whereIn('user_id', $studentIds)
                            ->update(['status' => 'graduated', 'updated_at' => $now]);
                        $graduatedCount += $studentIds->count();
                    }
                    continue;
                }

                // Grade 7 and 8 get promoted
                if (! isset($gradeMap[$grade])) {
                    continue;
                }

                $newGrade = $gradeMap[$grade];
                $newClassName = $newGrade . $suffix;

                // Create new class in target year (no homeroom, no schedules)
                $newClassId = DB::table('classes')->insertGetId([
                    'name' => $newClassName,
                    'academic_year_id' => $toYearId,
                    'homeroom_teacher_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $newClassCount++;

                // Copy students to new class
                $studentIds = DB::table('class_student')
                    ->where('class_id', $oldClass->id)
                    ->pluck('student_id');

                if ($studentIds->isNotEmpty()) {
                    $pivotRows = [];
                    foreach ($studentIds as $studentId) {
                        $pivotRows[] = [
                            'class_id' => $newClassId,
                            'student_id' => $studentId,
                            'academic_year_id' => $toYearId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    foreach (array_chunk($pivotRows, 100) as $chunk) {
                        DB::table('class_student')->insertOrIgnore($chunk);
                    }

                    $promotedCount += $studentIds->count();
                }
            }

            // 4. Auto-activate the target academic year, deactivate old year
            DB::table('academic_years')->where('is_active', true)->update(['is_active' => false, 'updated_at' => $now]);
            DB::table('academic_years')->where('id', $toYearId)->update(['is_active' => true, 'updated_at' => $now]);

            DB::commit();

            return response()->json([
                'message' => "Migrasi kelas berhasil! $newClassCount kelas baru dibuat, $promotedCount siswa dinaikkan kelas, $graduatedCount siswa kelas 9 diluluskan. Tahun ajaran baru telah diaktifkan.",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat migrasi kelas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract grade number from class name (e.g. "7A" → "7", "VIII-A" → "8").
     */
    private function extractGrade(string $name): ?string
    {
        $romanMap = ['VII' => '7', 'VIII' => '8', 'IX' => '9'];
        $upper = strtoupper(trim($name));

        foreach ($romanMap as $roman => $digit) {
            if (str_starts_with($upper, $roman)) {
                return $digit;
            }
        }

        // Try digit prefix
        if (preg_match('/^(\d)/', $name, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Extract suffix after grade (e.g. "7A" → "A", "8-B" → "-B", "VII C" → " C").
     */
    private function extractSuffix(string $name): string
    {
        $romanMap = ['VIII', 'VII', 'IX'];
        $upper = strtoupper(trim($name));

        foreach ($romanMap as $roman) {
            if (str_starts_with($upper, $roman)) {
                return substr($name, strlen($roman));
            }
        }

        // Digit prefix: take everything after first digit
        if (preg_match('/^\d/', $name)) {
            return substr($name, 1);
        }

        return $name;
    }
}
