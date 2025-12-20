<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\DeviceTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\NotificationDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

final class FcmTokenController extends Controller
{
    /**
     * Store or update FCM token for the authenticated user.
     *
     * @OA\Post(
     *     path="/api/v1/fcm-tokens",
     *     summary="Store or update FCM token",
     *     tags={"FCM Tokens"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"token", "device_type"},
     *
     *             @OA\Property(property="token", type="string", example="dGhpcyBpcyBhIHRva2Vu..."),
     *             @OA\Property(property="device_type", type="string", enum={"android", "ios", "web"}, example="android")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FCM token stored successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FCM token stored successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="device_type", type="string", example="android"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ]);

        $user = Auth::user();

        // Check if token already exists for this user
        $fcmToken = NotificationDeviceToken::where('user_id', $user->id)
            ->where('token', $validated['token'])
            ->first();

        if ($fcmToken) {
            // Update existing token
            $fcmToken->update([
                'platform' => $validated['platform'],
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'data' => $fcmToken,
            ], 200);
        }

        // Create new token
        $fcmToken = NotificationDeviceToken::create([
            'user_id' => $user->id,
            'token' => $validated['token'],
            'platform' => $validated['platform'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token stored successfully',
            'data' => $fcmToken,
        ], 201);
    }

    /**
     * Get all FCM tokens for the authenticated user.
     *
     * @OA\Get(
     *     path="/api/v1/fcm-tokens",
     *     summary="Get all FCM tokens for authenticated user",
     *     tags={"FCM Tokens"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="FCM tokens retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="token", type="string"),
     *                     @OA\Property(property="device_type", type="string", example="android"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        // Assuming user model doesn't strictly need relationship method updated if we query directly, 
        // but typically one would update User model too. Since we can't edit User model efficiently in same step,
        // we'll query directly to avoid relationship errors if the relationship name differs.
        $tokens = NotificationDeviceToken::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ], 200);
    }

    /**
     * Delete a specific FCM token.
     *
     * @OA\Delete(
     *     path="/api/v1/fcm-tokens/{id}",
     *     summary="Delete a specific FCM token",
     *     tags={"FCM Tokens"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FCM token deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FCM token deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="FCM token not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $fcmToken = NotificationDeviceToken::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'FCM token not found',
            ], 404);
        }

        $fcmToken->delete();

        return response()->json([
            'success' => true,
            'message' => 'FCM token deleted successfully',
        ], 200);
    }

    /**
     * Delete FCM token by token string.
     *
     * @OA\Post(
     *     path="/api/v1/fcm-tokens/delete-by-token",
     *     summary="Delete FCM token by token string",
     *     tags={"FCM Tokens"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"token"},
     *
     *             @OA\Property(property="token", type="string", example="dGhpcyBpcyBhIHRva2Vu...")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FCM token deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FCM token deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="FCM token not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function destroyByToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = Auth::user();

        $fcmToken = NotificationDeviceToken::where('token', $validated['token'])
            ->where('user_id', $user->id)
            ->first();

        if (! $fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'FCM token not found',
            ], 404);
        }

        $fcmToken->delete();

        return response()->json([
            'success' => true,
            'message' => 'FCM token deleted successfully',
        ], 200);
    }

    /**
     * Delete all FCM tokens for the authenticated user.
     *
     * @OA\Delete(
     *     path="/api/v1/fcm-tokens/all",
     *     summary="Delete all FCM tokens for authenticated user",
     *     tags={"FCM Tokens"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="All FCM tokens deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All FCM tokens deleted successfully"),
     *             @OA\Property(property="deleted_count", type="integer", example=3)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroyAll(): JsonResponse
    {
        $user = Auth::user();
        $deletedCount = NotificationDeviceToken::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All FCM tokens deleted successfully',
            'deleted_count' => $deletedCount,
        ], 200);
    }
}
