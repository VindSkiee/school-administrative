<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PrincipalDashboardService
{
    private function getActiveAcademicYearId(): int
    {
        // Cache ID Tahun Ajaran aktif selama 24 jam karena jarang sekali berubah
        return Cache::remember('active_academic_year_id', now()->addHours(24), function () {
            $activeYear = AcademicYear::query()->where('is_active', true)->first();
            if (!$activeYear) {
                throw new HttpException(400, "Sistem belum memiliki Tahun Ajaran yang aktif.");
            }
            return $activeYear->id;
        });
    }

    /**
     * 1. Overview Metrik Sekolah
     */
    public function getOverviewMetrics(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        return Cache::remember('principal_overview_metrics', now()->addHours(24), function () use ($yearId) {
            return [
                // Gunakan ->query() di semua panggilan statis Eloquent
                'total_active_students' => Student::query()->where('status', 'active')->count('*'),
                'total_teachers' => Teacher::query()->count('*'),
                'total_active_classes' => SchoolClass::query()->where('academic_year_id', $yearId)->count('*'),
            ];
        });
    }

    /**
     * 2. Tren Kehadiran Global (Persentase)
     */
    public function getAttendanceTrends(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        // DB facade biasanya aman dari Intelephense, biarkan seperti ini
        $stats = DB::table('attendances')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('classes.academic_year_id', $yearId)
            ->select(
                DB::raw('COUNT(attendances.id) as total_attendance_records'),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN attendances.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN attendances.status = 'permission' THEN 1 ELSE 0 END) as permission"),
                DB::raw("SUM(CASE WHEN attendances.status = 'alpa' THEN 1 ELSE 0 END) as alpa"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late")
            )
            ->first();

        // Kalkulasi Persentase jika ada data
        $total = $stats->total_attendance_records ?: 1; // Cegah division by zero

        return Cache::remember('principal_attendance_trends', now()->addHours(24), function () use ($stats, $total) {
            return [
                'raw_data' => $stats,
                'percentages' => [
                    'present' => round(($stats->present / $total) * 100, 2).'%',
                    'sick' => round(($stats->sick / $total) * 100, 2).'%',
                    'permission' => round(($stats->permission / $total) * 100, 2).'%',
                    'alpa' => round(($stats->alpa / $total) * 100, 2).'%',
                    'late' => round(($stats->late / $total) * 100, 2).'%',
                ],
            ];
        });
    }

    /**
     * 3. Performa Akademik (Top Kelas Berdasarkan Rata-rata)
     */
    public function getAcademicPerformance(): array
    {
        $yearId = $this->getActiveAcademicYearId();

        // Query berat ini di-cache selama 1 jam. 
        // Kepala sekolah tidak butuh data yang real-time per detik, data per 1 jam sudah sangat relevan.
        $cacheKey = "principal_academic_performance_year_{$yearId}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($yearId) {
            $performance = DB::table('classes')
                ->leftJoin('schedules', 'classes.id', '=', 'schedules.class_id')
                ->leftJoin('assignments', 'schedules.id', '=', 'assignments.schedule_id')
                ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
                ->leftJoin('grades', 'submissions.id', '=', 'grades.submission_id')
                ->where('classes.academic_year_id', $yearId)
                ->select(
                    'classes.id',
                    'classes.name as class_name',
                    DB::raw('ROUND(AVG(grades.score), 2) as average_score')
                )
                ->groupBy('classes.id', 'classes.name')
                ->havingRaw('average_score IS NOT NULL')
                ->orderBy('average_score', 'desc')
                ->take(10)
                ->get();

            return $performance->toArray();
        });
    }

    /**
     * 4. YoY (Year-over-Year) School Performance Index — last 5 academic years.
     *
     * Formula:
     *   N_A = (0.4 × Formative_Avg) + (0.6 × Summative_Avg)  [per student per subject]
     *   GPA_S = avg(N_A) across all subjects                    [per student]
     *   I_S   = avg(GPA_S) across all students                  [per year]
     */
    public function getYearOverYearIndex(): array
    {
        return Cache::remember('principal_yoy_index', now()->addHours(6), function () {
            // Step 1: Get last 5 PUBLISHED academic years (only years with final/locked grades)
            $years = AcademicYear::where('is_report_published', true)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get()
                ->reverse()
                ->values();

            $result = [];

            foreach ($years as $year) {
                // Step 2: Fetch all graded scores for this academic year in a single query
                $rows = DB::table('grades')
                    ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
                    ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                    ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
                    ->where('schedules.academic_year_id', $year->id)
                    ->whereNotNull('grades.score')
                    ->select(
                        'submissions.student_id',
                        'schedules.subject_id',
                        'assignments.type',
                        DB::raw('AVG(grades.score) as avg_score')
                    )
                    ->groupBy('submissions.student_id', 'schedules.subject_id', 'assignments.type')
                    ->get();

                if ($rows->isEmpty()) {
                    continue; // Skip years with no graded data
                }

                // Step 3: Build nested structure → student → subject → [task, uts, uas]
                $studentSubjectScores = [];

                foreach ($rows as $row) {
                    $studentSubjectScores[$row->student_id][$row->subject_id][$row->type] = (float) $row->avg_score;
                }

                // Step 4: Calculate N_A per student-subject, then GPA_S, then I_S
                $studentGpas = [];

                foreach ($studentSubjectScores as $studentId => $subjects) {
                    $naValues = [];

                    foreach ($subjects as $typeScores) {
                        $formative = $typeScores['task'] ?? null; // Tugas Harian (40%)
                        $uts       = $typeScores['uts']  ?? null; // UTS (part of summative 60%)
                        $uas       = $typeScores['uas']  ?? null; // UAS (part of summative 60%)

                        // Summative average (UTS + UAS)
                        $summativeScores = array_filter([$uts, $uas], fn ($v) => $v !== null);
                        $summative = count($summativeScores) > 0
                            ? array_sum($summativeScores) / count($summativeScores)
                            : null;

                        // N_A = weighted combination (fallback gracefully if one component is missing)
                        if ($formative !== null && $summative !== null) {
                            $na = (0.4 * $formative) + (0.6 * $summative);
                        } elseif ($formative !== null) {
                            $na = $formative; // Only formative available
                        } elseif ($summative !== null) {
                            $na = $summative; // Only summative available
                        } else {
                            continue;
                        }

                        $naValues[] = $na;
                    }

                    // GPA_S = average of all N_A for this student
                    if (!empty($naValues)) {
                        $studentGpas[] = array_sum($naValues) / count($naValues);
                    }
                }

                // Step 5: I_S = mean of all student GPAs
                $schoolIndex = !empty($studentGpas)
                    ? round(array_sum($studentGpas) / count($studentGpas), 2)
                    : null;

                $result[] = [
                    'academic_year' => $year->name,
                    'school_index'  => $schoolIndex,
                ];
            }

            return $result;
        });
    }

    /**
     * 5. Current Grade Distribution — based on active academic year.
     *
     * Categories (standard thresholds):
     *   A (Sangat Baik): ≥ 85
     *   B (Baik):        ≥ 75
     *   C (Kurang):      ≥ 60
     *   D (Sangat Kurang): < 60
     */
    public function getGradeDistribution(): array
    {
        $yearId = $this->getActiveAcademicYearId();
        $activeYear = AcademicYear::find($yearId);

        $cacheKey = "principal_grade_distribution_year_{$yearId}";

        $distribution = Cache::remember($cacheKey, now()->addHours(2), function () use ($yearId) {
            // Step 1: Get average grade per student for this academic year
            $studentAverages = DB::table('grades')
                ->join('submissions', 'grades.submission_id', '=', 'submissions.id')
                ->join('assignments', 'submissions.assignment_id', '=', 'assignments.id')
                ->join('schedules', 'assignments.schedule_id', '=', 'schedules.id')
                ->where('schedules.academic_year_id', $yearId)
                ->whereNotNull('grades.score')
                ->select(
                    'submissions.student_id',
                    DB::raw('AVG(grades.score) as avg_score')
                )
                ->groupBy('submissions.student_id')
                ->get();

            if ($studentAverages->isEmpty()) {
                return [
                    ['category' => 'A', 'count' => 0, 'percentage' => 0],
                    ['category' => 'B', 'count' => 0, 'percentage' => 0],
                    ['category' => 'C', 'count' => 0, 'percentage' => 0],
                    ['category' => 'D', 'count' => 0, 'percentage' => 0],
                ];
            }

            // Step 2: Bucket students into grade categories
            $buckets = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];

            foreach ($studentAverages as $row) {
                $score = (float) $row->avg_score;
                if ($score >= 85) {
                    $buckets['A']++;
                } elseif ($score >= 75) {
                    $buckets['B']++;
                } elseif ($score >= 60) {
                    $buckets['C']++;
                } else {
                    $buckets['D']++;
                }
            }

            // Step 3: Calculate percentages
            $total = $studentAverages->count();

            $result = [];
            foreach ($buckets as $category => $count) {
                $result[] = [
                    'category'   => $category,
                    'count'      => $count,
                    'percentage' => round(($count / $total) * 100, 1),
                ];
            }

            return $result;
        });

        // Include active year name in response
        $yearLabel = $activeYear
            ? $activeYear->name . ' — ' . ($activeYear->semester === 'odd' ? 'Ganjil' : 'Genap')
            : 'Belum ada tahun ajaran aktif';

        return [
            'distribution' => $distribution,
            'academic_year_name' => $yearLabel,
        ];
    }

    // ======================================================================
    //  CURRICULUM TREND — Grade-level perspective over multiple years
    // ======================================================================

    /**
     * Maps a numeric grade level to LIKE patterns for class names.
     * Supports both Roman numeral (VII) and Arabic numeral (7) formats.
     */
    private function gradePatterns(int $grade): array
    {
        $romanMap = [7 => 'VII', 8 => 'VIII', 9 => 'IX'];
        $roman = $romanMap[$grade] ?? null;

        return $roman
            ? [$roman . '%', 'KELAS ' . $roman . '%', (string) $grade . '%', 'KELAS ' . $grade . '%']
            : [(string) $grade . '%', 'KELAS ' . $grade . '%'];
    }

    /**
     * 6. Curriculum Trend: avg index + demographics for a specific grade level
     *    across the last N published academic years.
     *
     * Aggregation strategy:
     *   - ONE query for demographics: JOIN class_student → students → classes → academic_years
     *     with conditional SUM(CASE WHEN gender) — no subqueries.
     *   - ONE query for scores: JOIN grades → submissions → assignments → schedules → classes → academic_years
     *     with GROUP BY academic_year, then compute weighted N_A in PHP.
     *
     * Indexes needed (see recommendations in controller docblock):
     *   - idx_class_student_ay_class  ON class_student(academic_year_id, class_id)
     *   - idx_schedules_ay_class      ON schedules(academic_year_id, class_id)
     */
    public function getCurriculumTrend(int $gradeLevel, int $limit = 5): array
    {
        $cacheKey = "principal_curriculum_trend_v2_g{$gradeLevel}_l{$limit}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($gradeLevel, $limit) {
            $romanMap = [7 => 'VII', 8 => 'VIII', 9 => 'IX'];
            $roman = $romanMap[$gradeLevel] ?? null;

            // ── Step 1: Get COMBINED academic years (both odd+even published) ──
            // Group by year name, only include names where BOTH semesters are published.
            $allPublished = AcademicYear::where('is_report_published', true)
                ->orderBy('id', 'desc')
                ->get(['id', 'name', 'semester']);

            $grouped = $allPublished->groupBy('name');
            $combinedYears = collect();

            foreach ($grouped as $name => $semesters) {
                $hasOdd = $semesters->contains('semester', 'odd');
                $hasEven = $semesters->contains('semester', 'even');

                if ($hasOdd && $hasEven) {
                    // Add all semester records for this combined year
                    foreach ($semesters as $year) {
                        $combinedYears->push($year);
                    }
                }

                if ($combinedYears->count() >= $limit * 2) break; // Enough data
            }

            // Limit to N combined years (each has 2 records: odd + even)
            $years = $combinedYears->reverse()->values();

            if ($years->isEmpty()) {
                return [
                    'categories' => [],
                    'series' => [['name' => "Rata-rata Kelas {$gradeLevel}", 'data' => []]],
                    'demographics' => [],
                    'has_combined_years' => false,
                ];
            }

            $yearIds = $years->pluck('id');

            // ── Step 2: Demographics — single aggregated query ──
            // Uses conditional aggregation for male/female split.
            // Joins: class_student → students (for gender) → classes (for academic_year_id + name filtering).
            $demoQuery = DB::table('class_student as cs')
                ->join('students as st', 'st.user_id', '=', 'cs.student_id')
                ->join('classes as cl', 'cl.id', '=', 'cs.class_id')
                ->whereIn('cs.academic_year_id', $yearIds)
                ->where('st.status', 'active')
                ->select(
                    'cs.academic_year_id',
                    DB::raw('COUNT(DISTINCT cs.student_id) as total'),
                    DB::raw("SUM(CASE WHEN st.gender = 'L' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN st.gender = 'P' THEN 1 ELSE 0 END) as female")
                );

            // Apply grade-level filter on class names
            if ($roman) {
                $demoQuery->where(function ($q) use ($gradeLevel, $roman) {
                    $q->where('cl.name', 'like', $roman . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $roman . '%')
                      ->orWhere('cl.name', 'like', $gradeLevel . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $gradeLevel . '%');
                });
            } else {
                $demoQuery->where(function ($q) use ($gradeLevel) {
                    $q->where('cl.name', 'like', $gradeLevel . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $gradeLevel . '%');
                });
            }

            $demoRows = $demoQuery->groupBy('cs.academic_year_id')->get();
            $demoMap = $demoRows->keyBy('academic_year_id');

            // ── Step 3: Score aggregation — single query, grouped by year + subject + type ──
            // Joins: grades → submissions → assignments → schedules → classes
            // Filters by published year IDs and grade-level class name patterns.
            $scoreRows = DB::table('grades as g')
                ->join('submissions as sub', 'sub.id', '=', 'g.submission_id')
                ->join('assignments as a', 'a.id', '=', 'sub.assignment_id')
                ->join('schedules as sch', 'sch.id', '=', 'a.schedule_id')
                ->join('classes as cl', 'cl.id', '=', 'sch.class_id')
                ->whereIn('sch.academic_year_id', $yearIds)
                ->whereNotNull('g.score')
                ->select(
                    'sch.academic_year_id',
                    'a.type',
                    DB::raw('AVG(g.score) as avg_score')
                );

            if ($roman) {
                $scoreRows->where(function ($q) use ($gradeLevel, $roman) {
                    $q->where('cl.name', 'like', $roman . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $roman . '%')
                      ->orWhere('cl.name', 'like', $gradeLevel . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $gradeLevel . '%');
                });
            } else {
                $scoreRows->where(function ($q) use ($gradeLevel) {
                    $q->where('cl.name', 'like', $gradeLevel . '%')
                      ->orWhere('cl.name', 'like', 'KELAS ' . $gradeLevel . '%');
                });
            }

            $scoreRows->groupBy('sch.academic_year_id', 'a.type');
            $scoreData = $scoreRows->get();

            // ── Step 4: Compute weighted index per COMBINED year ──
            // Group scores by year name (merging odd+even semesters)
            $yearNameTypeMap = [];
            foreach ($scoreData as $row) {
                // Map academic_year_id → year name
                $yearName = $years->firstWhere('id', $row->academic_year_id)?->name;
                if (!$yearName) continue;
                // Merge both semesters' scores under the same year name
                if (!isset($yearNameTypeMap[$yearName])) {
                    $yearNameTypeMap[$yearName] = ['task' => [], 'uts' => [], 'uas' => []];
                }
                $yearNameTypeMap[$yearName][$row->type][] = (float) $row->avg_score;
            }

            // ── Step 5: Build ApexCharts-ready response (one point per combined year) ──
            $uniqueNames = $years->pluck('name')->unique()->values();
            $categories = [];
            $indexData = [];
            $demographics = [];

            foreach ($uniqueNames as $yearName) {
                $categories[] = $yearName;

                // Compute N_A from merged scores of both semesters
                $typeMap = $yearNameTypeMap[$yearName] ?? [];
                $formativeScores = $typeMap['task'] ?? [];
                $utsScores = $typeMap['uts'] ?? [];
                $uasScores = $typeMap['uas'] ?? [];

                $formative = count($formativeScores) > 0
                    ? array_sum($formativeScores) / count($formativeScores)
                    : null;

                $summativeScores = array_merge($utsScores, $uasScores);
                $summative = count($summativeScores) > 0
                    ? array_sum($summativeScores) / count($summativeScores)
                    : null;

                if ($formative !== null && $summative !== null) {
                    $na = (0.4 * $formative) + (0.6 * $summative);
                } elseif ($formative !== null) {
                    $na = $formative;
                } elseif ($summative !== null) {
                    $na = $summative;
                } else {
                    $na = null;
                }

                $indexData[] = $na !== null ? round($na, 2) : null;

                // Demographics: sum both semesters for this year name
                $yearIdsForName = $years->where('name', $yearName)->pluck('id');
                $totalMale = 0;
                $totalFemale = 0;
                $totalStudents = 0;
                foreach ($yearIdsForName as $yid) {
                    $demo = $demoMap[$yid] ?? null;
                    if ($demo) {
                        $totalMale += (int) $demo->male;
                        $totalFemale += (int) $demo->female;
                        $totalStudents += (int) $demo->total;
                    }
                }

                $demographics[] = [
                    'academic_year' => $yearName,
                    'total'  => $totalStudents,
                    'male'   => $totalMale,
                    'female' => $totalFemale,
                ];
            }

            return [
                'categories'   => $categories,
                'series'       => [['name' => "Rata-rata Kelas {$gradeLevel}", 'data' => $indexData]],
                'demographics' => $demographics,
                'has_combined_years' => true,
            ];
        });
    }

    // ======================================================================
    //  COHORT TREND — Student-batch perspective across grade progression
    // ======================================================================

    /**
     * 7. Cohort Trend: avg index of a specific student batch as they progress
     *    through grade levels (7 → 8 → 9), with current demographics.
     *
     * Cohort definition: students whose EARLIEST class_student record
     * belongs to the given academic_year_id (i.e., they entered school that year).
     *
     * Aggregation strategy:
     *   - Subquery identifies cohort members via MIN(academic_year_id) = entry year.
     *   - ONE score query: joins through class_student to get grade_level per year,
     *     groups by (grade_level, type), computes weighted N_A in PHP.
     *   - ONE demographics query: counts current active cohort members by gender.
     *
     * Indexes needed:
     *   - idx_class_student_student ON class_student(student_id)
     */
    public function getCohortTrend(array $entryYearIds): array
    {
        // Build cache key from sorted IDs
        sort($entryYearIds);
        $idsKey = implode('_', $entryYearIds);
        $cacheKey = "principal_cohort_trend_v2_{$idsKey}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($entryYearIds) {
            // Get representative year for label (first entry year)
            $entryYear = AcademicYear::find($entryYearIds[0]);
            // Use year name (e.g., "2025/2026") as cohort label
            $cohortLabel = $entryYear ? "Angkatan {$entryYear->name}" : "Angkatan";

            // ── Step 1: Identify cohort members ──
            // Students whose FIRST academic year in the system is one of the entry years.
            // This includes students from both odd and even semesters of a combined year.
            $cohortStudentIds = DB::table('class_student as cs_outer')
                ->select('cs_outer.student_id')
                ->whereIn('cs_outer.academic_year_id', $entryYearIds)
                ->whereExists(function ($sub) use ($entryYearIds) {
                    $sub->select(DB::raw('1'))
                        ->from('class_student as cs_inner')
                        ->whereColumn('cs_inner.student_id', 'cs_outer.student_id')
                        ->whereIn('cs_inner.academic_year_id', $entryYearIds)
                        ->whereRaw('cs_inner.academic_year_id = (SELECT MIN(cs_min.academic_year_id) FROM class_student cs_min WHERE cs_min.student_id = cs_outer.student_id)');
                })
                ->groupBy('cs_outer.student_id')
                ->pluck('student_id');

            if ($cohortStudentIds->isEmpty()) {
                return [
                    'categories' => [],
                    'series' => [['name' => $cohortLabel, 'data' => []]],
                    'cohort_demographics' => ['total' => 0, 'male' => 0, 'female' => 0],
                ];
            }

            // ── Step 2: Score aggregation grouped by grade_level ──
            // FIXED: No class_student join — fetch ALL scores for cohort students
            // across ALL academic years, grouped by the class (grade level) they were in.
            // This tracks the SAME students as they progress from Kelas 7 → 8 → 9.
            $scoreRows = DB::table('grades as g')
                ->join('submissions as sub', 'sub.id', '=', 'g.submission_id')
                ->join('assignments as a', 'a.id', '=', 'sub.assignment_id')
                ->join('schedules as sch', 'sch.id', '=', 'a.schedule_id')
                ->join('classes as cl', 'cl.id', '=', 'sch.class_id')
                ->whereIn('sub.student_id', $cohortStudentIds)
                ->whereNotNull('g.score')
                ->select(
                    'cl.name as class_name',
                    'a.type',
                    DB::raw('AVG(g.score) as avg_score'),
                    DB::raw('COUNT(DISTINCT sub.student_id) as student_count')
                )
                ->groupBy('cl.name', 'a.type')
                ->get();

            // ── Step 3: Extract grade level from class name ──
            // Map Roman numerals: VII→7, VIII→8, IX→9
            $extractGrade = function (string $className): ?int {
                $upper = strtoupper(trim($className));
                // Remove "KELAS " prefix if present
                $upper = preg_replace('/^KELAS\s*/', '', $upper);
                if (str_starts_with($upper, 'IX'))  return 9;
                if (str_starts_with($upper, 'VIII')) return 8;
                if (str_starts_with($upper, 'VII'))  return 7;
                // Arabic numeral fallback
                $firstChar = $upper[0] ?? '';
                if (in_array($firstChar, ['7', '8', '9'])) return (int) $firstChar;
                return null;
            };

            // Build: grade_level → type → avg_score
            // Also track student count per grade level
            $gradeTypeMap = [];
            $gradeStudentCount = [];
            foreach ($scoreRows as $row) {
                $gl = $extractGrade($row->class_name);
                if ($gl === null) continue;
                $gradeTypeMap[$gl][$row->type] = (float) $row->avg_score;
                $gradeStudentCount[$gl] = max(
                    $gradeStudentCount[$gl] ?? 0,
                    (int) $row->student_count
                );
            }

            // ── Step 4: Compute weighted N_A per grade level ──
            $gradeLabels = [7 => 'Kelas 7', 8 => 'Kelas 8', 9 => 'Kelas 9'];
            $categories = [];
            $indexData = [];

            foreach ([7, 8, 9] as $gl) {
                $types = $gradeTypeMap[$gl] ?? [];
                if (empty($types)) continue; // Skip grades with no data

                $categories[] = $gradeLabels[$gl];

                $formative = $types['task'] ?? null;
                $summativeScores = array_filter(
                    [$types['uts'] ?? null, $types['uas'] ?? null],
                    fn ($v) => $v !== null
                );
                $summative = count($summativeScores) > 0
                    ? array_sum($summativeScores) / count($summativeScores)
                    : null;

                if ($formative !== null && $summative !== null) {
                    $na = (0.4 * $formative) + (0.6 * $summative);
                } elseif ($formative !== null) {
                    $na = $formative;
                } elseif ($summative !== null) {
                    $na = $summative;
                } else {
                    $na = null;
                }

                $indexData[] = $na !== null ? round($na, 2) : null;
            }

            // Build per-grade demographics for tooltip
            $gradeDemo = [];
            foreach ($categories as $i => $cat) {
                $gl = [7 => 'Kelas 7', 8 => 'Kelas 8', 9 => 'Kelas 9'];
                $glKey = array_search($cat, $gl);
                $gradeDemo[] = [
                    'grade' => $cat,
                    'student_count' => $gradeStudentCount[$glKey] ?? 0,
                ];
            }

            // ── Step 5: Current demographics — single query on cohort members ──
            $demo = DB::table('students as st')
                ->whereIn('st.user_id', $cohortStudentIds)
                ->where('st.status', 'active')
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN st.gender = 'L' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN st.gender = 'P' THEN 1 ELSE 0 END) as female")
                )
                ->first();

            return [
                'categories' => $categories,
                'series'     => [['name' => $cohortLabel, 'data' => $indexData]],
                'cohort_demographics' => [
                    'total'  => $demo ? (int) $demo->total : 0,
                    'male'   => $demo ? (int) $demo->male : 0,
                    'female' => $demo ? (int) $demo->female : 0,
                ],
                'grade_demographics' => $gradeDemo,
            ];
        });
    }
}
