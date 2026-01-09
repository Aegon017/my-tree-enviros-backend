<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\SignInRequest;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Services\CartService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuthController extends Controller
{
    use ResponseHelpers;

    public function __construct(
        private readonly AuthService $service,
        private readonly CartService $cartService
    ) {}

    public function signUp(SignUpRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request): JsonResponse {
            $user = $this->service->register($request->validated());

            if (! $this->service->sendOtp($user)) {
                return $this->tooManyRequests('Too many OTP requests');
            }

            return $this->created(null, 'OTP sent successfully');
        });
    }

    public function signIn(SignInRequest $request): JsonResponse
    {
        $user = $this->service->findByPhone($request->country_code, $request->phone);

        if (! $user instanceof \App\Models\User) {
            return $this->notFound('User not found');
        }

        if (! $this->service->sendOtp($user)) {
            return $this->tooManyRequests('Too many OTP requests');
        }

        return $this->success(null, 'OTP sent successfully');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->service->findByPhone(
            $request->country_code,
            $request->phone
        );

        if (! $user instanceof \App\Models\User) {
            return $this->notFound('User not found');
        }

        if (! $this->service->verifyOtp($user, $request->otp)) {
            return $this->error('Invalid or expired OTP', 422);
        }

        $token = $this->service->createToken($user);

        $guestSessionId = $request->session()->get('guest_cart_id');
        if ($guestSessionId) {
            try {
                $this->cartService->mergeGuestCart($guestSessionId, $user->id);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = $this->service->findByPhone($request->country_code, $request->phone);

        if (! $user instanceof \App\Models\User) {
            return $this->notFound('User not found');
        }

        if (! $this->service->sendOtp($user)) {
            return $this->tooManyRequests('Too many OTP requests');
        }

        return $this->success(null, 'OTP sent successfully');
    }

    public function signOut(Request $request): JsonResponse
    {
        $this->service->signOut(
            $request->user(),
            $request->boolean('all', false)
        );

        return $this->success(null, 'Signed out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = collect($request->validated())->except('avatar')->toArray();

        $user->update($data);

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
        }

        return $this->success([
            'user' => new UserResource($user->refresh()),
        ], 'Profile updated successfully');
    }
}
