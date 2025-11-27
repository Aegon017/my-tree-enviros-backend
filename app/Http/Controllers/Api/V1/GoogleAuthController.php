<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google_Client;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');
        return $provider->stateless()->redirect()->getTargetUrl();
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
            $user->addMediaFromUrl($googleUser->getAvatar())->toMediaCollection('avatars');
        }

        $token = $user->createToken('web')->plainTextToken;

        return redirect("https://your-frontend.com/auth/callback?token={$token}");
    }

    public function mobileLogin(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);

        $payload = $client->verifyIdToken($request->token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid Google token'], 401);
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

        return response()->json([
            'token' => $token,
            'user' => $user->load('media'),
        ]);
    }
}
