<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class PostOfficeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'pincode' => 'required|digits:6',
        ]);

        $pincode = $request->pincode;
        $url = 'https://api.postalpincode.in/pincode/'.$pincode;

        try {
            $response = Http::retry(3, 200)
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept' => 'application/json,text/html,*/*',
                ])
                ->get($url);

            $resp = $response->json();
        } catch (Exception) {
            return response()->json([], 200);
        }

        if (! is_array($resp) || $resp === []) {
            return response()->json([], 200);
        }

        if (($resp[0]['Status'] ?? null) !== 'Success') {
            return response()->json([], 200);
        }

        $postOffices = $resp[0]['PostOffice'] ?? [];

        $finalData = array_map(fn (array $po): array => [
            'name' => $po['Name'] ?? null,
            'branch_type' => $po['BranchType'] ?? null,
            'delivery_status' => $po['DeliveryStatus'] ?? null,
            'circle' => $po['Circle'] ?? null,
            'district' => $po['District'] ?? null,
            'division' => $po['Division'] ?? null,
            'region' => $po['Region'] ?? null,
            'block' => $po['Block'] ?? null,
            'state' => $po['State'] ?? null,
            'country' => $po['Country'] ?? null,
            'pincode' => $po['Pincode'] ?? null,
        ], $postOffices);

        return response()->json($finalData);
    }
}
