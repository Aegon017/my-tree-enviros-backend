<?php

declare(strict_types=1);

namespace App\Api\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class AuthService
{
    private const TEST_PHONE = "9876543210";
    private const TEST_OTP = "123456";

    public function register(array $data): User
    {
        return User::create([
            "type" => $data["type"],
            "country_code" => $data["country_code"],
            "phone" => $data["phone"],
            "password" => Hash::make($data["phone"]),
        ]);
    }

    public function findByPhone(string $countryCode, string $phone): ?User
    {
        return User::where("country_code", $countryCode)
            ->where("phone", $phone)
            ->first();
    }

    public function sendOtp(User $user): bool
    {
        $key = "otp:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return false;
        }

        if ($this->isTestPhone($user->phone)) {
            $user->oneTimePasswords()->create([
                "password" => self::TEST_OTP,
                "expires_at" => now()->addMinutes(5),
            ]);
        } else {
            $user->sendOneTimePassword();
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        $key = "verify:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }

        $result = $user->consumeOneTimePassword($otp);

        if (!$result->isOk()) {
            RateLimiter::hit($key, 300);
            return false;
        }

        RateLimiter::clear($key);
        return true;
    }

    public function createToken(User $user): string
    {
        return $user->createToken(
            "auth-token",
            ["*"],
            now()->addDays(30),
        )->plainTextToken;
    }

    public function revokeTokens(User $user, bool $all = false): void
    {
        if ($all) {
            $user->tokens()->delete();
        } else {
            $user->currentAccessToken()?->delete();
        }
    }

    public function getRateLimitSeconds(User $user): ?int
    {
        $key = "otp:{$user->id}";

        return RateLimiter::tooManyAttempts($key, 3)
            ? RateLimiter::availableIn($key)
            : null;
    }

    private function isTestPhone(string $phone): bool
    {
        return $phone === self::TEST_PHONE && !app()->isProduction();
    }
}
