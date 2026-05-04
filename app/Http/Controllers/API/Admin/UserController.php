<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController
{
    public function __construct(protected UserService $userService) {}

    public function store(StoreUserRequest $request): JsonResponse
    {
        // Input sudah terjamin aman dan tervalidasi karena melewati StoreUserRequest
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User berhasil didaftarkan.',
            'data' => $user
        ], 201);
    }
}