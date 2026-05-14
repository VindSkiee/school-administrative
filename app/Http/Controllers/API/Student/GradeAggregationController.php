<?php

namespace App\Http\Controllers\API\Student;

use App\Services\GradeAggregationService;
use Illuminate\Http\JsonResponse;

class GradeAggregationController
{
    public function __construct(protected GradeAggregationService $service) {}

    public function index(): JsonResponse
    {
        $student = auth('api')->user()->student;

        if (!$student || $student->status !== 'active' || !$student->class_id) {
            return response()->json(['error' => 'Anda tidak memiliki kelas aktif.'], 403);
        }

        $aggregates = $this->service->getStudentAggregate($student->user_id, $student->class_id);

        return response()->json([
            'success' => true,
            'data' => $aggregates
        ]);
    }
}