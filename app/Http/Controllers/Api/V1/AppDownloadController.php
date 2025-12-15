<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppDownloadController extends Controller
{
    public function getApp(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        $settings = \App\Models\AppSetting::first();
        $androidUrl = $settings->android_url ?? config('services.app.android');
        $iosUrl = $settings->ios_url ?? config('services.app.ios');
        $fallbackUrl = '/';

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'iPod') !== false) {
            if ($iosUrl) return redirect()->away($iosUrl);
        }

        if (stripos($userAgent, 'Android') !== false) {
            if ($androidUrl) return redirect()->away($androidUrl);
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
            ]
        ]);
    }
}
