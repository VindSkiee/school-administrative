<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name,role')->latest();

        // Fitur filter sederhana untuk Admin
        if ($request->has('model_type')) {
            $query->where('loggable_type', 'like', '%'.$request->model_type.'%');
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }
}
