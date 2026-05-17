<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\User;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController
{
    public function __construct(protected UserService $userService) {}

    // READ (Daftar semua user dengan pagination & filter role)
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['student', 'teacher']);

        // Opsional: Filter berdasarkan role (contoh: ?role=student)
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    // CREATE (Sudah kita buat, ditambah penanganan error HttpException dari Service)
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'User berhasil didaftarkan.',
                'data' => $user
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat user: ' . $e->getMessage()], 500);
        }
    }

    // READ (Detail satu user)
    public function show(string $id): JsonResponse
    {
        $user = User::with(['student', 'teacher'])->findOrFail($id);
        return response()->json($user);
    }

    // UPDATE
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui',
            'data' => $user
        ]);
    }

    // DELETE (Soft Delete / Deactivate)
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Mencegah admin menghapus dirinya sendiri
        if (auth('api')->id() == $user->id) {
            return response()->json(['error' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 403);
        }

        $user->update(['is_active' => false]);
        $user->delete(); // Memicu SoftDelete

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dinonaktifkan.'
        ]);
    }
}