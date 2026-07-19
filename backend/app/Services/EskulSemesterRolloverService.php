<?php

namespace App\Services;

use App\Models\EskulChangeRequest;
use App\Models\StudentEskul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EskulSemesterRolloverService
{
    public function processRollover(int $oldAcademicYearId, int $newAcademicYearId): array
    {
        $results = ['rollover_count' => 0, 'change_applied_count' => 0, 'errors' => []];

        $oldStudentEskuls = StudentEskul::where('academic_year_id', $oldAcademicYearId)
            ->get()
            ->keyBy('student_id');

        $pendingRequests = EskulChangeRequest::where('academic_year_id', $oldAcademicYearId)
            ->where('status', 'pending')
            ->get()
            ->keyBy('student_id');

        DB::transaction(function () use ($oldStudentEskuls, $pendingRequests, $newAcademicYearId, &$results) {
            foreach ($oldStudentEskuls as $studentId => $oldEskul) {
                try {
                    $newEskulId = $oldEskul->eskul_id;

                    if (isset($pendingRequests[$studentId])) {
                        $newEskulId = $pendingRequests[$studentId]->requested_eskul_id;

                        $pendingRequests[$studentId]->update(['status' => 'processed']);
                        $results['change_applied_count']++;

                        // If requested_eskul_id is null, student will re-select next semester
                        if ($newEskulId === null) {
                            $results['rollover_count']++;

                            continue;
                        }
                    }

                    StudentEskul::create([
                        'student_id' => $studentId,
                        'eskul_id' => $newEskulId,
                        'academic_year_id' => $newAcademicYearId,
                    ]);

                    $results['rollover_count']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Student ID {$studentId}: {$e->getMessage()}";
                    Log::error("Eskul rollover failed for student {$studentId}", ['error' => $e->getMessage()]);
                }
            }
        });

        return $results;
    }
}
