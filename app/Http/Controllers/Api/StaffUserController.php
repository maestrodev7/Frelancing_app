<?php

namespace App\Http\Controllers\Api;

use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStaffUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StaffUserController extends Controller
{
    public function store(StoreStaffUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'country_id' => $request->countryId(),
            'password' => $validated['password'],
            'role' => UserRole::from($validated['role']),
            'status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Compte staff créé avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
            ],
        ], 201);
    }
}
