<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Requests\Api\Auth\SignInRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AuthController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly AuthService $service) {}

    public function signUp(SignUpRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
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

        if (! $user) {
            return $this->notFound('User not found');
        }

        if (! $this->service->sendOtp($user)) {
            return $this->tooManyRequests('Too many OTP requests');
        }

        return $this->success(null, 'OTP sent successfully');
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $user = $this->service->findByPhone(
            $request->country_code,
            $request->phone
        );

        if (!$user) {
            return $this->notFound('User not found');
        }

        if (!$this->service->verifyOtp($user, $request->otp)) {
            return $this->error('Invalid or expired OTP', 422);
        }

        $token = $this->service->createToken($user);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = $this->service->findByPhone($request->country_code, $request->phone);

        if (! $user) {
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
}
