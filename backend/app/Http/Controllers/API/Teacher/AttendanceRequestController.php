<?php

namespace App\Http\Controllers\API\Teacher;

use App\Models\AttendanceRequest;
use App\Http\Requests\Teacher\ReviewAttendanceRequest;
use App\Services\AttendanceRequestService;
use Illuminate\Http\JsonResponse;

class AttendanceRequestController
{
    public function __construct(protected AttendanceRequestService $service) {}

    public function index(): JsonResponse
    {
        $teacherId = auth('api')->user()->id;

        // Ambil request dari jadwal yang diajar oleh guru ini
        $requests = AttendanceRequest::with(['student.user', 'schedule.subject', 'schedule.schoolClass'])
            ->whereHas('schedule', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($requests);
    }

    public function review(ReviewAttendanceRequest $request, string $id): JsonResponse
    {
        $teacherId = auth('api')->user()->id;
        $attendanceRequest = AttendanceRequest::findOrFail($id);

        try {
            $updatedRequest = $this->service->reviewRequest(
                $teacherId, 
                $attendanceRequest, 
                $request->status
            );

            return response()->json([
                'success' => true,
                'message' => "Pengajuan berhasil di-{$request->status}.",
                'data' => $updatedRequest
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}