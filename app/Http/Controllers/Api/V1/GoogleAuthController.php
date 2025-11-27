<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ResponseHelpers;
use Google_Client;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleAuthController extends Controller
{
    use ResponseHelpers;

    public function redirect()
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');
        return $provider->stateless()->redirect();
    }

    public function callback()
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');
        $googleUser = $provider->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
            ]
        );

        if (! $user->hasMedia('avatars')) {
            $user->addMediaFromUrl($googleUser->getAvatar())
                ->toMediaCollection('avatars');
        }

        $token = $user->createToken('web')->plainTextToken;

        $redirectUrl = env('FRONTEND_URL') . "/auth/callback?token={$token}";
        return redirect($redirectUrl);
    }

    public function mobileLogin(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);

        $payload = $client->verifyIdToken($request->token);

        if (! $payload) {
            return $this->error('Invalid Google token', 401);
        }

        $email = $payload['email'];
        $name = $payload['name'];
        $googleId = $payload['sub'];
        $avatar = $payload['picture'] ?? null;

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'google_id' => $googleId,
            ]
        );

        if ($avatar && ! $user->hasMedia('avatars')) {
            $user->addMediaFromUrl($avatar)->toMediaCollection('avatars');
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Logged in successfully');
    }
}
