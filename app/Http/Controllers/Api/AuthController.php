<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Api\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\SignInRequest;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function signUp(SignUpRequest $request): JsonResponse
    {
        $user = $this->authService->registerUser($request->validated());
        $user->sendOneTimePassword();

        return response()->json([
            'message' => 'User registered. OTP sent to phone number.',
        ], Response::HTTP_CREATED);
    }

    public function signIn(SignInRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->findUserByPhone(
            $data['country_code'],
            $data['phone'],
        );

        if (! $user instanceof \App\Models\User) {
            return response()->json([
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->sendOneTimePassword();

        return response()->json([
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        $data = $request->validated();

        Log::info($data);

        $user = $this->authService->findUserByPhone(
            $data['country_code'],
            $data['phone'],
        );

        if (! $user instanceof \App\Models\User) {
            return response()->json([
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->sendOneTimePassword();

        return response()->json([
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->findUserByPhone(
            $data['country_code'],
            $data['phone']
        );

        if (! $user instanceof \App\Models\User) {
            return response()->json([
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->authService->verifyOtp($user, $data['otp']);

        if ($result['success']) {
            return response()->json([
                'message' => 'OTP verified successfully.',
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ]);
        }

        return response()->json([
            'message' => $result['message'],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
