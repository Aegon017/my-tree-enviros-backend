<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::paginate(15);

        return $this->success([
            'users' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,organization',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users',
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|unique:users',
        ]);

        $user = User::create($validated);

        return $this->created(['user' => new UserResource($user)]);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(['user' => new UserResource($user)]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:individual,organization',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'country_code' => 'sometimes|string|max:5',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
        ]);

        $user->update($validated);

        return $this->success(['user' => new UserResource($user->fresh())]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->noContent();
    }
}
