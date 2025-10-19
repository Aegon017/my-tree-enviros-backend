<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Api\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\SignInRequest;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 */
final class AuthController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly AuthService $authService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/sign-up",
     *     summary="Register a new user",
     *     description="Register a new user with phone number and send OTP for verification",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"country_code", "phone", "name"},
     *             @OA\Property(property="country_code", type="string", example="+91", description="Country code"),
     *             @OA\Property(property="phone", type="string", example="9876543210", description="Phone number without country code"),
     *             @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address (optional)"),
     *             @OA\Property(property="type", type="string", enum={"individual", "organization"}, example="individual", description="User type")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully, OTP sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many OTP requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Too many OTP requests"),
     *             @OA\Property(property="retry_after", type="integer", example=60)
     *         )
     *     )
     * )
     */
    public function signUp(SignUpRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = $this->authService->register($request->validated());

            if (!$this->authService->sendOtp($user)) {
                return $this->tooManyRequests(
                    'Too many OTP requests',
                    $this->authService->getRateLimitSeconds($user)
                );
            }

            return $this->created(null, 'OTP sent successfully');
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sign-in",
     *     summary="Sign in user",
     *     description="Sign in with phone number and receive OTP",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"country_code", "phone"},
     *             @OA\Property(property="country_code", type="string", example="+91", description="Country code"),
     *             @OA\Property(property="phone", type="string", example="9876543210", description="Phone number without country code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many OTP requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Too many OTP requests"),
     *             @OA\Property(property="retry_after", type="integer", example=60)
     *         )
     *     )
     * )
     */
    public function signIn(SignInRequest $request): JsonResponse
    {
        $user = $this->authService->findByPhone(
            $request->country_code,
            $request->phone
        );

        if (!$user) {
            return $this->notFound('User not found');
        }

        if (!$this->authService->sendOtp($user)) {
            return $this->tooManyRequests(
                'Too many OTP requests',
                $this->authService->getRateLimitSeconds($user)
            );
        }

        return $this->success(null, 'OTP sent successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/verify-otp",
     *     summary="Verify OTP",
     *     description="Verify OTP code and authenticate user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"country_code", "phone", "otp"},
     *             @OA\Property(property="country_code", type="string", example="+91", description="Country code"),
     *             @OA\Property(property="phone", type="string", example="9876543210", description="Phone number"),
     *             @OA\Property(property="otp", type="string", example="123456", description="6-digit OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Authentication successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz123456", description="Bearer token (for mobile apps)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP")
     *         )
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->authService->findByPhone(
            $request->country_code,
            $request->phone
        );

        if (!$user) {
            return $this->notFound('User not found');
        }

        if (!$this->authService->verifyOtp($user, $request->otp)) {
            return $this->error('Invalid or expired OTP', 422);
        }

        $platform = strtolower($request->header('X-Platform', 'web'));
        $isMobile = in_array($platform, ['ios', 'android', 'mobile']);

        if ($isMobile) {
            return $this->success([
                'user' => new UserResource($user),
                'token' => $this->authService->createToken($user),
            ], 'Authentication successful');
        }

        Auth::login($user, true);

        return $this->success([
            'user' => new UserResource($user),
        ], 'Authentication successful');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/resend-otp",
     *     summary="Resend OTP",
     *     description="Resend OTP code to user's phone number",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"country_code", "phone"},
     *             @OA\Property(property="country_code", type="string", example="+91"),
     *             @OA\Property(property="phone", type="string", example="9876543210")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many OTP requests"
     *     )
     * )
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = $this->authService->findByPhone(
            $request->country_code,
            $request->phone
        );

        if (!$user) {
            return $this->notFound('User not found');
        }

        if (!$this->authService->sendOtp($user)) {
            return $this->tooManyRequests(
                'Too many OTP requests',
                $this->authService->getRateLimitSeconds($user)
            );
        }

        return $this->success(null, 'OTP sent successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Logout user",
     *     description="Logout user and revoke tokens (for mobile) or invalidate session (for web)",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="all", type="boolean", example=false, description="Revoke all tokens (mobile only)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $isMobile = $request->bearerToken() !== null;

        if ($isMobile) {
            $this->authService->revokeTokens(
                $request->user(),
                $request->boolean('all', false)
            );
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     summary="Get current user",
     *     description="Get authenticated user's profile information",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => new UserResource($request->user()),
        ]);
    }
}
