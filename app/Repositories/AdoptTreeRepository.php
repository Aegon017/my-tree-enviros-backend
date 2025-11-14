<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TreeStatusEnum;
use App\Enums\TreeTypeEnum;
use App\Models\Tree;
use App\Models\TreeInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AdoptTreeRepository
{
    public function getAdoptList(float $lat, float $lng, float $radius, int $perPage): LengthAwarePaginator
    {
        $haversine = "(
            6371 * acos(
                cos(radians($lat)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians($lng)) +
                sin(radians($lat)) *
                sin(radians(latitude))
            )
        )";

        $query = Tree::query()
            ->with(['planPrices.plan'])
            ->withCount([
                'instances' => fn($q) => $q->where('status', TreeStatusEnum::WAITING_ADOPTION->value),
            ])
            ->whereHas('instances.location', function ($q) use ($haversine, $radius) {
                $q->selectRaw("$haversine AS distance")
                    ->having('distance', '<=', $radius);
            })->whereHas('planPrices.plan', fn($q) => $q->where('type', TreeTypeEnum::ADOPT->value));

        return $query->paginate($perPage);
    }

    public function getAdoptDetails(int $instanceId): ?TreeInstance
    {
        return TreeInstance::with([
            'tree',
            'location',
            'geotags',
            'conditionUpdates' => fn($q) => $q->latest(),
            'history.user',
            'history.plan',
            'statusLogs' => fn($q) => $q->latest(),
        ])->find($instanceId);
    }
}
