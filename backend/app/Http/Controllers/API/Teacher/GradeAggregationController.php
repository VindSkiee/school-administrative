<?php

namespace App\Http\Controllers\API\Teacher;

use App\Services\GradeAggregationService;
use Illuminate\Http\JsonResponse;

class GradeAggregationController
{
    public function __construct(protected GradeAggregationService $service) {}

    public function show(string $scheduleId): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        try {
            $aggregates = $this->service->getClassAggregate($teacherId, $scheduleId);

            return response()->json([
                'success' => true,
                'data' => $aggregates
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}