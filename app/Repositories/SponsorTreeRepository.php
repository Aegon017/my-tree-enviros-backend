<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TreeTypeEnum;
use App\Models\Tree;

final class SponsorTreeRepository
{
    public function getSponsorList(float $lat, float $lng, float $radius, int $perPage)
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
            ->whereHas('planPrices.location', function ($q) use ($haversine, $radius): void {
                $q->selectRaw($haversine.' AS distance')
                    ->having('distance', '<=', $radius);
            })->whereHas('planPrices.plan', fn ($q) => $q->where('type', TreeTypeEnum::SPONSOR->value));

        return $query->paginate($perPage);
    }

    public function findTreeByIdOrSlug(string $identifier): ?Tree
    {
        return Tree::query()
            ->with('planPrices.plan')
            ->when(is_numeric($identifier), fn ($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();
    }
}
