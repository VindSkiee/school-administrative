<?php

namespace App\Http\Controllers\API\Student;

use App\Models\AttendanceRequest;
use App\Http\Requests\Student\StoreAttendanceRequest;
use App\Services\AttendanceRequestService;
use Illuminate\Http\JsonResponse;

class AttendanceRequestController
{
    public function __construct(protected AttendanceRequestService $service) {}

    public function index(): JsonResponse
    {
        $studentId = auth('api')->user()->id; // user_id
        
        $requests = AttendanceRequest::with('schedule.subject')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($requests);
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $studentId = auth('api')->user()->id;

        try {
            $attendanceRequest = $this->service->submitRequest(
                $studentId, 
                $request->validated(), 
                $request->file('proof_file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil dikirim.',
                'data' => $attendanceRequest
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}