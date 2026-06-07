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

        // 1. Filter Dropdown Entitas
        if ($request->filled('model_type')) {
            $query->where('loggable_type', 'like', '%' . $request->model_type . '%');
        }

        // 2. Pencarian Dinamis (Smart Search)
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            // Trik Enterprise: Translasi pencarian Bahasa Indonesia ke Bahasa Inggris Database
            $mappedAction = $search;
            if (str_contains('membuat', $search) || str_contains('tambah', $search)) {
                $mappedAction = 'created';
            } elseif (str_contains('memperbarui', $search) || str_contains('edit', $search)) {
                $mappedAction = 'updated';
            } elseif (str_contains('menghapus', $search) || str_contains('hapus', $search)) {
                $mappedAction = 'deleted';
            }

            // Lakukan grouping pencarian agar tidak bentrok dengan filter Dropdown
            $query->where(function ($q) use ($search, $mappedAction) {
                // A. Cari berdasarkan Nama User/Aktor
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%');
                })
                // B. Cari berdasarkan ID Target
                ->orWhere('loggable_id', 'like', '%' . $search . '%')
                // C. Cari berdasarkan Aksi (Menggunakan kata yang sudah ditranslasi)
                ->orWhere('action', 'like', '%' . $mappedAction . '%')
                // D. Cari berdasarkan nama Model Asli
                ->orWhere('loggable_type', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->paginate(20);

        return response()->json($logs);
    }
}