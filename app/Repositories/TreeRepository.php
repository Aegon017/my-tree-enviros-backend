<?php

namespace App\Repositories;

use App\Models\Tree;
use Illuminate\Pagination\LengthAwarePaginator;

final class TreeRepository
{
    public function findPaginatedTrees(
        float $lat,
        float $lng,
        float $radius,
        string $type,
        int $perPage
    ): LengthAwarePaginator {

        $haversine = "(
            6371 * acos(
                cos(radians($lat)) *
                cos(radians(locations.latitude)) *
                cos(radians(locations.longitude) - radians($lng)) +
                sin(radians($lat)) *
                sin(radians(locations.latitude))
            )
        )";

        $query = Tree::query()
            ->where('is_active', true)
            ->with(['planPrices.plan'])
            ->withCount([
                'treeInstances as adoptable_count' => fn($q) =>
                $q->where('status', 'adoptable')
            ])
            ->whereHas('treeInstances.location', function ($q) use ($haversine, $radius) {
                $q->selectRaw("locations.*, $haversine as distance")
                    ->having('distance', '<=', $radius);
            })->whereHas(
                'planPrices.plan',
                fn($q) =>
                $q->where('type', $type)
            );

        return $query->paginate($perPage);
    }

    public function findTreeByIdOrSlug(string $identifier, string $type): ?Tree
    {
        return Tree::query()
            ->where('is_active', true)
            ->with([
                'planPrices.plan' => fn($q) =>
                $q->where('type', $type),
            ])
            ->with([
                'treeInstances' => fn($q) =>
                $q->where('status', 'adoptable')
                    ->with('location')
            ])
            ->withCount([
                'treeInstances as adoptable_count' => fn($q) =>
                $q->where('status', 'adoptable')
            ])
            ->when(
                is_numeric($identifier),
                fn($q) => $q->where('id', $identifier),
                fn($q) => $q->where('slug', $identifier)
            )
            ->first();
    }
}
