<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Carbon;

final class AuthRepository
{
    public function createUser(array $data): User
    {
        return User::create([
            'type' => $data['type'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['phone']),
        ]);
    }

    public function findByPhone(string $country, string $phone): ?User
    {
        return User::where('country_code', $country)
            ->where('phone', $phone)
            ->first();
    }

    public function storeOtp(User $user, string $otp, Carbon $exp): void
    {
        $user->oneTimePasswords()->create([
            'password' => $otp,
            'expires_at' => $exp,
        ]);
    }

    public function dispatchOtp(User $user): void
    {
        $user->sendOneTimePassword();
    }

    public function consumeOtp(User $user, string $otp): bool
    {
        return $user->consumeOneTimePassword($otp)->isOk();
    }

    public function createToken(User $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }

    public function revokeTokens(User $user, bool $all): void
    {
        $all
            ? $user->tokens()->delete()
            : $user->currentAccessToken()?->delete();
    }
}
