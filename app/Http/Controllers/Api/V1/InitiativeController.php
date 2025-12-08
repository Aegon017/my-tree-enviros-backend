<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Initiative;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;

final class InitiativeController extends Controller
{
    use ResponseHelpers;

    public function index(): JsonResponse
    {
        $initiatives = Initiative::with('sites')
            ->whereHas('sites')
            ->get();

        return $this->success(['initiatives' => $initiatives]);
    }
}
