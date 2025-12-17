<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentGateway\PaymentGatewayResource;
use App\Models\PaymentGateway;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;

class PaymentGatewayController extends Controller
{
    use ResponseHelpers;

    public function index(): JsonResponse
    {
        $gateways = PaymentGateway::orderBy('sort')->get();

        return $this->success(PaymentGatewayResource::collection($gateways));
    }
}
