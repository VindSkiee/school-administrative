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

    /** Flush all class option cache keys (all years + specific years) */
    private function flushClassOptionsCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('admin_class_options_all');
        // Flush year-specific keys for known years
        $yearIds = \Illuminate\Support\Facades\DB::table('academic_years')->pluck('id');
        foreach ($yearIds as $yearId) {
            \Illuminate\Support\Facades\Cache::forget('admin_class_options_year_' . $yearId);
        }
    }

    // PERF FIX: lightweight dropdown endpoint + 120s cache
    public function options(Request $request): JsonResponse
    {
        $cacheKey = 'admin_class_options' . ($request->filled('academic_year_id') ? '_year_' . $request->academic_year_id : '_all');

        $classes = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($request) {
            $query = \Illuminate\Support\Facades\DB::table('classes')
                ->select('classes.id', 'classes.name', 'classes.academic_year_id');

            if ($request->filled('academic_year_id')) {
                $query->where('classes.academic_year_id', $request->academic_year_id);
            }

            return $query->orderBy('classes.name')->get()->toArray();
        });

        return response()->json(['data' => $classes]);
    }

    // PERF FIX: single raw SQL with LEFT JOINs replaces 4 Eloquent queries (N+1 elimination)
    public function index(Request $request): JsonResponse
    {
        $query = \Illuminate\Support\Facades\DB::table('classes as c')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'c.academic_year_id')
            ->leftJoin('teachers as ht', 'ht.user_id', '=', 'c.homeroom_teacher_id')
            ->leftJoin('users as hu', 'hu.id', '=', 'ht.user_id')
            ->select(
                'c.id',
                'c.name',
                'c.academic_year_id',
                'c.homeroom_teacher_id',
                'ay.name as academic_year_name',
                'ay.semester as academic_year_semester',
                'ay.is_active as academic_year_is_active',
                'hu.id as homeroom_teacher_user_id',
                'hu.name as homeroom_teacher_name',
                \Illuminate\Support\Facades\DB::raw('(SELECT COUNT(*) FROM class_student cs WHERE cs.class_id = c.id) as students_count')
            );

        if ($request->filled('academic_year_id')) {
            $query->where('c.academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('grade')) {
            $grade = $request->query('grade');
            $romanMap = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'];

            if (isset($romanMap[$grade])) {
                $roman = $romanMap[$grade];
                $query->where(function ($subQuery) use ($grade, $roman) {
                    $subQuery->where('c.name', 'like', $grade . '%')
                             ->orWhere('c.name', 'like', 'KELAS ' . $grade . '%')
                             ->orWhere('c.name', 'like', $roman . '%')
                             ->orWhere('c.name', 'like', 'KELAS ' . $roman . '%');
                });
            }
        }

        // PERF FIX: removed per_page=all branch, capped max at 200
        $perPage = min((int) $request->query('per_page', 20), 200);
        $page = (int) $request->query('page', 1);
        $total = $query->count();
        $classes = $query->orderBy('c.name')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'academic_year_id' => $row->academic_year_id,
                    'homeroom_teacher_id' => $row->homeroom_teacher_id,
                    'academic_year' => $row->academic_year_id ? [
                        'id' => $row->academic_year_id,
                        'name' => $row->academic_year_name,
                        'semester' => $row->academic_year_semester,
                        'is_active' => (bool) $row->academic_year_is_active,
                    ] : null,
                    'homeroom_teacher' => $row->homeroom_teacher_user_id ? [
                        'user_id' => $row->homeroom_teacher_user_id,
                        'user' => [
                            'id' => $row->homeroom_teacher_user_id,
                            'name' => $row->homeroom_teacher_name,
                        ],
                    ] : null,
                    'students_count' => (int) $row->students_count,
                ];
            });

        return response()->json([
            'data' => $classes,
            'total' => $total,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
        ]);
    }

    // CREATE
    public function store(StoreClassRequest $request): JsonResponse
    {
        $class = SchoolClass::create($request->validated());
        $this->flushClassOptionsCache();

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
        $this->flushClassOptionsCache();

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
        $this->flushClassOptionsCache();

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

            // PERF FIX: Check source classes exist in one query
            $classCount = DB::table('classes')->where('academic_year_id', $fromYearId)->count();
            if ($classCount === 0) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada kelas di tahun ajaran asal.'], 404);
            }

            $now = now()->toDateTimeString();

            // PERF FIX: Bulk upsert classes using INSERT ... SELECT with ON DUPLICATE KEY
            // Step 1: Insert new classes that don't exist yet in target year
            DB::statement("
                INSERT INTO classes (name, academic_year_id, homeroom_teacher_id, created_at, updated_at)
                SELECT c.name, ?, c.homeroom_teacher_id, ?, ?
                FROM classes c
                WHERE c.academic_year_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM classes c2
                    WHERE c2.name = c.name AND c2.academic_year_id = ?
                )
            ", [$toYearId, $now, $now, $fromYearId, $toYearId]);

            // Step 2: Update homeroom_teacher_id where target class has NULL but source has value
            DB::statement("
                UPDATE classes target
                INNER JOIN classes source ON source.name = target.name
                    AND source.academic_year_id = ?
                SET target.homeroom_teacher_id = source.homeroom_teacher_id,
                    target.updated_at = ?
                WHERE target.academic_year_id = ?
                AND target.homeroom_teacher_id IS NULL
                AND source.homeroom_teacher_id IS NOT NULL
            ", [$fromYearId, $now, $toYearId]);

            $migratedClassCount = DB::table('classes')->where('academic_year_id', $toYearId)->count();

            // PERF FIX: Bulk insert student pivots using INSERT ... SELECT (single query)
            DB::statement("
                INSERT IGNORE INTO class_student (class_id, student_id, academic_year_id, created_at, updated_at)
                SELECT target.id, cs.student_id, ?, ?, ?
                FROM class_student cs
                INNER JOIN classes source ON source.id = cs.class_id AND source.academic_year_id = ?
                INNER JOIN classes target ON target.name = source.name AND target.academic_year_id = ?
            ", [$toYearId, $now, $now, $fromYearId, $toYearId]);

            $migratedStudentCount = DB::table('class_student')
                ->where('academic_year_id', $toYearId)
                ->count();

            // PERF FIX: Bulk duplicate schedules using INSERT ... SELECT (single query)
            DB::statement("
                INSERT INTO schedules (class_id, subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time, created_at, updated_at)
                SELECT target.id, s.subject_id, s.teacher_id, ?, s.day_of_week, s.start_time, s.end_time, ?, ?
                FROM schedules s
                INNER JOIN classes source ON source.id = s.class_id AND source.academic_year_id = ?
                INNER JOIN classes target ON target.name = source.name AND target.academic_year_id = ?
            ", [$toYearId, $now, $now, $fromYearId, $toYearId]);

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

            // 3. Check source classes exist
            $classCount = DB::table('classes')->where('academic_year_id', $fromYearId)->count();
            if ($classCount === 0) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada kelas di tahun ajaran aktif.'], 404);
            }

            // PERF FIX: Bulk mark grade 9 students as graduated (single query instead of loop)
            // Extract grade from class name: if name starts with '9' or 'IX', it's grade 9
            $graduatedCount = DB::statement("
                UPDATE students s
                INNER JOIN class_student cs ON cs.student_id = s.user_id
                INNER JOIN classes c ON c.id = cs.class_id
                SET s.status = 'graduated', s.updated_at = ?
                WHERE c.academic_year_id = ?
                AND (c.name REGEXP '^9' OR c.name REGEXP '^IX')
            ", [$now, $fromYearId]);

            // PERF FIX: Bulk create new classes for promoted students (grade 7→8, 8→9)
            // Using CASE expression to map grade numbers in SQL
            DB::statement("
                INSERT INTO classes (name, academic_year_id, homeroom_teacher_id, created_at, updated_at)
                SELECT
                    CASE
                        WHEN c.name REGEXP '^7' THEN CONCAT('8', SUBSTRING(c.name, 2))
                        WHEN c.name REGEXP '^VII' THEN CONCAT('VIII', SUBSTRING(c.name, 4))
                        WHEN c.name REGEXP '^8' THEN CONCAT('9', SUBSTRING(c.name, 2))
                        WHEN c.name REGEXP '^VIII' THEN CONCAT('IX', SUBSTRING(c.name, 4))
                    END,
                    ?,
                    NULL,
                    ?,
                    ?
                FROM classes c
                WHERE c.academic_year_id = ?
                AND (
                    c.name REGEXP '^7' OR c.name REGEXP '^VII'
                    OR c.name REGEXP '^8' OR c.name REGEXP '^VIII'
                )
                AND NOT EXISTS (
                    SELECT 1 FROM classes c2
                    WHERE c2.academic_year_id = ?
                    AND c2.name = CASE
                        WHEN c.name REGEXP '^7' THEN CONCAT('8', SUBSTRING(c.name, 2))
                        WHEN c.name REGEXP '^VII' THEN CONCAT('VIII', SUBSTRING(c.name, 4))
                        WHEN c.name REGEXP '^8' THEN CONCAT('9', SUBSTRING(c.name, 2))
                        WHEN c.name REGEXP '^VIII' THEN CONCAT('IX', SUBSTRING(c.name, 4))
                    END
                )
            ", [$toYearId, $now, $now, $fromYearId, $toYearId]);

            $newClassCount = DB::table('classes')->where('academic_year_id', $toYearId)->count();

            // PERF FIX: Bulk copy student pivots to new classes (single query)
            $promotedCount = DB::statement("
                INSERT IGNORE INTO class_student (class_id, student_id, academic_year_id, created_at, updated_at)
                SELECT target.id, cs.student_id, ?, ?, ?
                FROM class_student cs
                INNER JOIN classes source ON source.id = cs.class_id AND source.academic_year_id = ?
                INNER JOIN classes target ON target.academic_year_id = ?
                AND (
                    -- Grade 7→8 mapping
                    (source.name REGEXP '^7' AND target.name = CONCAT('8', SUBSTRING(source.name, 2)))
                    OR (source.name REGEXP '^VII' AND target.name = CONCAT('VIII', SUBSTRING(source.name, 4)))
                    -- Grade 8→9 mapping
                    OR (source.name REGEXP '^8' AND target.name = CONCAT('9', SUBSTRING(source.name, 2)))
                    OR (source.name REGEXP '^VIII' AND target.name = CONCAT('IX', SUBSTRING(source.name, 4)))
                )
            ", [$toYearId, $now, $now, $fromYearId, $toYearId]);

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
