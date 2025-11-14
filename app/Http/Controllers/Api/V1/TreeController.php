<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TreeRequest;
use App\Http\Resources\Api\V1\TreeCollection;
use App\Http\Resources\Api\V1\TreeResource;
use App\Services\TreeService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;

final class TreeController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly TreeService $service) {}

    public function index(TreeRequest $request): JsonResponse
    {
        $lat = (float) $request->user_lat;
        $lng = (float) $request->user_lng;
        $radius = (float) ($request->radius_km ?? 50);
        $type = $request->type ?? 'all';
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $trees = $this->service->getTrees($lat, $lng, $radius, $type, $perPage);

        return $this->success(new TreeCollection($trees));
    }

    public function show(string $identifier): JsonResponse
    {
        $tree = $this->service->getByIdOrSlug($identifier);

        return $this->success(['tree' => new TreeResource($tree)]);
    }
}
