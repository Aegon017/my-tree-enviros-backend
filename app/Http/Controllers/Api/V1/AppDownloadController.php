<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class AppDownloadController extends Controller
{
    public function getApp(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        $settings = \App\Models\AppSetting::first();
        $androidUrl = $settings->android_url ?? config('services.app.android');
        $iosUrl = $settings->ios_url ?? config('services.app.ios');
        $fallbackUrl = '/';

        if ((mb_stripos($userAgent, 'iPhone') !== false || mb_stripos($userAgent, 'iPad') !== false || mb_stripos($userAgent, 'iPod') !== false) && $iosUrl) {
            return redirect()->away($iosUrl);
        }

        if (mb_stripos($userAgent, 'Android') !== false && $androidUrl) {
            return redirect()->away($androidUrl);
        }

        return redirect()->to($fallbackUrl);
    }

    public function getSettings()
    {
        $settings = \App\Models\AppSetting::first();

        return response()->json([
            'success' => true,
            'data' => [
                'android_url' => $settings->android_url ?? config('services.app.android'),
                'ios_url' => $settings->ios_url ?? config('services.app.ios'),
            ],
        ]);
    }
}
