<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdoptTreeCollection;
use App\Http\Resources\Api\V1\TreeInstanceResource;
use App\Services\AdoptTreeService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdoptTreeController extends Controller
{
    use ResponseHelpers;

    public function __construct(
        private readonly AdoptTreeService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $lat = (float) $request->user_lat;
        $lng = (float) $request->user_lng;
        $radius = (float) ($request->radius_km ?? 50);
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $data = $this->service->getAdoptList($lat, $lng, $radius, $perPage);

        return $this->success(new AdoptTreeCollection($data));
    }

    public function show(int|string $identifier): JsonResponse
    {
        $instance = $this->service->getAdoptDetails($identifier);

        if (! $instance instanceof \App\Models\TreeInstance) {
            return $this->notFound('Tree instance not found');
        }

        return $this->success([
            'tree' => new TreeInstanceResource($instance),
        ]);
    }
}
