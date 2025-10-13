<?php

namespace App\Api\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function registerUser(array $data): User
    {
        return User::create([
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['phone']),
        ]);
    }

    public function verifyOtp(User $user, string $otp): array
    {
        $result = $user->consumeOneTimePassword($otp);

        if ($result->isOk()) {
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->save();

            return [
                'success' => true,
                'user' => $user,
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at,
            ];
        }

        return [
            'success' => false,
            'message' => $result->validationMessage(),
        ];
    }

    public function findUserByPhone(string $countryCode, string $phone): ?User
    {
        return User::where('country_code', $countryCode)
            ->where('phone', $phone)
            ->first();
    }
}