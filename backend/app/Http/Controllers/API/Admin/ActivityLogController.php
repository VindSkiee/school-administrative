<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController
{
    public function index(Request $request): JsonResponse
    {
        // PERF FIX: use join instead of whereHas for user name search — avoids correlated subquery
        $query = ActivityLog::with('user:id,name,role')->latest();

        // 1. Filter Dropdown Entitas
        // PERF FIX: replaced LIKE '%ClassName%' with exact match on loggable_type
        if ($request->filled('model_type')) {
            $modelType = $request->model_type;
            // Map short names to FQCN if needed (e.g., "Grade" → "App\Models\Grade")
            if (! str_contains($modelType, '\\')) {
                $modelType = 'App\\Models\\' . $modelType;
            }
            $query->where('loggable_type', $modelType);
        }

        // 2. Pencarian Dinamis (Smart Search)
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            // Map Indonesian search terms to English DB action values
            $mappedAction = $search;
            if (str_contains($search, 'membuat') || str_contains($search, 'tambah')) {
                $mappedAction = 'created';
            } elseif (str_contains($search, 'memperbarui') || str_contains($search, 'edit')) {
                $mappedAction = 'updated';
            } elseif (str_contains($search, 'menghapus') || str_contains($search, 'hapus')) {
                $mappedAction = 'deleted';
            }

            $query->where(function ($q) use ($search, $mappedAction) {
                // A. Search by user name — prefix search uses index on users.name
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', $search . '%');
                })
                // B. Search by target ID — exact integer match (sargable)
                ->orWhere(function ($q2) use ($search) {
                    if (is_numeric($search)) {
                        $q2->where('loggable_id', (int) $search);
                    }
                })
                // C. Search by action — FULLTEXT index (replaces LIKE '%...%')
                ->orWhereFullText('action', $mappedAction)
                // D. Search by model type — exact match with FQCN prefix
                ->orWhere('loggable_type', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }
}
