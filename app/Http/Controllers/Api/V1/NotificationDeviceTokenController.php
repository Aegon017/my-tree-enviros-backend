<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class NotificationDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'platform' => 'required|string|in:ios,android,web',
            'token' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        $request->user()->notificationDeviceTokens()->updateOrCreate(
            ['token' => $data['token']],
            [
                'platform' => $data['platform'],
                'device_id' => $data['device_id'],
                'last_used_at' => now(),
            ]
        );

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $request->user()
            ->notificationDeviceTokens()
            ->where('token', $data['token'])
            ->delete();

        return response()->noContent();
    }
}
