<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SponsorTreeCollection;
use App\Http\Resources\Api\V1\SponsorTreeResource;
use App\Services\SponsorTreeService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SponsorTreeController extends Controller
{
    use ResponseHelpers;

    public function __construct(
        private readonly SponsorTreeService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $lat = (float) $request->user_lat;
        $lng = (float) $request->user_lng;
        $radius = (float) ($request->radius_km ?? 50);
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $data = $this->service->getSponsorList($lat, $lng, $radius, $perPage);

        return $this->success(new SponsorTreeCollection($data));
    }

    public function show(string $identifier): JsonResponse
    {
        $tree = $this->service->getByIdOrSlug($identifier);

        return $this->success(['tree' => new SponsorTreeResource($tree)]);
    }
}
