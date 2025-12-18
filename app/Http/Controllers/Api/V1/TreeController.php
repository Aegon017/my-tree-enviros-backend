<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TreeRequest;
use App\Http\Resources\Api\V1\TreeCollection;
use App\Http\Resources\Api\V1\TreeInstanceCollection;
use App\Http\Resources\Api\V1\TreeInstanceDetailResource;
use App\Http\Resources\Api\V1\TreeResource;
use App\Services\TreeService;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TreeController extends Controller
{
    use ResponseHelpers;

    public function __construct(private readonly TreeService $service) {}

    public function index(TreeRequest $request): JsonResponse
    {
        $lat = (float) $request->user_lat;
        $lng = (float) $request->user_lng;
        $radius = (float) ($request->radius_km ?? 50);
        $type = $request->type;
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $trees = $this->service->getTrees($lat, $lng, $radius, $type, $perPage);

        if ($type === 'adopt') {
            return $this->success(new TreeInstanceCollection($trees));
        }

        return $this->success(new TreeCollection($trees));
    }

    public function show(string $identifier, Request $request): JsonResponse
    {
        $type = $request->type;

        if ($type === 'adopt') {
            $instance = $this->service->getInstance((int) $identifier);

            if (! $instance instanceof \App\Models\TreeInstance) {
                return $this->notFound('Tree instance not found');
            }

            return $this->success([
                'tree' => new TreeInstanceDetailResource(
                    $instance->load([
                        'tree.planPrices.plan',
                        'location',
                        'tree.media',
                    ])
                ),
            ]);
        }

        $tree = $this->service->getByIdOrSlug($identifier, $type);

        if (! $tree instanceof \App\Models\Tree) {
            return $this->notFound('Tree not found');
        }

        return $this->success(['tree' => new TreeResource($tree->load([
            'planPrices' => fn ($q) => $q->whereHas(
                'plan',
                fn ($p) => $p->where('type', $type)
            )->with('plan'),
            'treeInstances' => fn ($q) => $q->where('status', 'adoptable')
                ->with('location'),
        ]))]);
    }
}
