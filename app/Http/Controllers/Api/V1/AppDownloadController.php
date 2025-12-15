<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppDownloadController extends Controller
{
    public function getApp(Request $request)
    {
        $userAgent = $request->header('User-Agent');

        $androidUrl = config('services.app.android');
        $iosUrl = config('services.app.ios');
        $fallbackUrl = '/';

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'iPod') !== false) {
            if ($iosUrl) return redirect()->away($iosUrl);
        }

        if (stripos($userAgent, 'Android') !== false) {
            if ($androidUrl) return redirect()->away($androidUrl);
        }

        return redirect()->to($fallbackUrl);
    }
}
