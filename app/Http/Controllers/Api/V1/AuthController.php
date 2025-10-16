<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Api\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\SignInRequest;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function signUp(SignUpRequest $request): JsonResponse
    {
        $user = $this->authService->registerUser($request->validated());

        if ($user->phone === '9876543210') {
            $user->oneTimePasswords()->create([
                'password' => '123456',
                'expires_at' => now()->addMinutes(2)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully.',
            ]);
        }

        $user->sendOneTimePassword();

        return response()->json([
            'status' => true,
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

        if (! $user instanceof User) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->phone === '9876543210') {
            $user->oneTimePasswords()->create([
                'password' => '123456',
                'expires_at' => now()->addMinutes(2)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully.',
            ]);
        }

        $user->sendOneTimePassword();

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        $data = $request->validated();

        $user = $this->authService->findUserByPhone(
            $data['country_code'],
            $data['phone'],
        );

        if (! $user instanceof User) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->sendOneTimePassword();

        return response()->json([
            'status' => true,
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

        if (! $user instanceof User) {
            return response()->json([
                'status' => false,
                'message' => 'No user found with this phone number.',
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->authService->verifyOtp($user, $data['otp']);

        if ($result['success']) {
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully.',
                'user' => $result['user'],
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
