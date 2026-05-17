<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Requests\Teacher\StoreGradeRequest;
use App\Services\GradeService;
use Illuminate\Http\JsonResponse;

class GradeController
{
    public function __construct(protected GradeService $gradeService) {}

    public function store(StoreGradeRequest $request, string $submissionId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $grade = $this->gradeService->gradeSubmission(
                $teacherId, 
                $submissionId, 
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Nilai berhasil disimpan.',
                'data' => $grade
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}