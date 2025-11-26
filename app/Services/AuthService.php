<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\RateLimiter;

final readonly class AuthService
{
    private const TEST_PHONE = '9876543210';

    private const TEST_OTP = '123456';

    public function __construct(private AuthRepository $repo) {}

    public function register(array $data): User
    {
        return $this->repo->createUser($data);
    }

    public function findByPhone(string $c, string $p): ?User
    {
        return $this->repo->findByPhone($c, $p);
    }

    public function sendOtp(User $user): bool
    {
        $key = 'otp:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return false;
        }

        if ($user->phone === self::TEST_PHONE && ! app()->isProduction()) {
            $this->repo->storeOtp($user, self::TEST_OTP, now()->addMinutes(5));
        } else {
            $this->repo->dispatchOtp($user);
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        $key = 'verify:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }

        $valid = $this->repo->consumeOtp($user, $otp);

        if (! $valid) {
            RateLimiter::hit($key, 300);

            return false;
        }

        RateLimiter::clear($key);

        return true;
    }

    public function createToken(User $user): string
    {
        return $this->repo->createToken($user);
    }

    public function signOut(User $user, bool $all): void
    {
        $this->repo->revokeTokens($user, $all);
    }
}
