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
                cos(radians({$lat})) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians({$lng})) +
                sin(radians({$lat})) *
                sin(radians(latitude))
            )
        )";

        $query = Tree::query()
            ->withCount([
                'treeInstances' => fn ($q) => $q->where('status', TreeStatusEnum::ADOPTABLE->value),
            ])
            ->whereHas('treeInstances.location', function ($q) use ($haversine, $radius): void {
                $q->selectRaw($haversine.' AS distance')
                    ->having('distance', '<=', $radius);
            })->whereHas('planPrices.plan', fn ($q) => $q->where('type', TreeTypeEnum::ADOPT->value));

        return $query->paginate($perPage);
    }

    public function getAdoptDetails(int|string $identifier): ?TreeInstance
    {
        return Tree::query()
            ->with('planPrices.plan')
            ->when(is_numeric($identifier), fn ($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();
    }
}
