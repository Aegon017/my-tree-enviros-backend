<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

class AppDownloadController extends Controller
{
    /**
     * Redirect users to the appropriate store based on their device.
     */
    public function getApp(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        $androidUrl = config('services.app.android');
        $iosUrl = config('services.app.ios');
        $fallbackUrl = '/'; // Or a specific landing page

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'iPod') !== false) {
            if ($iosUrl) return redirect()->away($iosUrl);
        }

        if (stripos($userAgent, 'Android') !== false) {
            if ($androidUrl) return redirect()->away($androidUrl);
        }

        // Fallback for desktop or unknown devices
        return redirect()->to($fallbackUrl);
    }
}
