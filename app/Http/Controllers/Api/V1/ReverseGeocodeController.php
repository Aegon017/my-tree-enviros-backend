<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReverseGeocodeController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;

        $cacheKey = "reverse_geocode:{$lat}:{$lng}";
        $data = cache()->remember($cacheKey, 3600, function () use ($lat, $lng) {
            $resp = Http::withHeaders(['User-Agent' => 'MyTreeApp/1.0'])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'addressdetails' => 1,
                ])->json();

            $addr = $resp['address'] ?? [];

            return [
                'area' => $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['village'] ?? $addr['hamlet'] ?? $addr['city_district'] ?? null,
                'city' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['county'] ?? null,
                'state' => $addr['state'] ?? null,
                'postal_code' => $addr['postcode'] ?? null,
                'display_name' => $resp['display_name'] ?? null,
            ];
        });

        return response()->json($data);
    }
}
