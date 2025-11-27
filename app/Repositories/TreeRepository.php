<?php

declare(strict_types=1);

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
                cos(radians({$lat})) *
                cos(radians(locations.latitude)) *
                cos(radians(locations.longitude) - radians({$lng})) +
                sin(radians({$lat})) *
                sin(radians(locations.latitude))
            )
        )";

        $query = Tree::query()->with(['planPrices.plan']);

        if ($type === 'adopt') {
            $query->whereHas('treeInstances', function ($q) use ($haversine, $radius): void {
                $q->where('status', 'adoptable')
                    ->whereHas('location', function ($loc) use ($haversine, $radius): void {
                        $loc->selectRaw(sprintf('locations.*, %s as distance', $haversine))
                            ->having('distance', '<=', $radius);
                    });
            })->whereHas('planPrices.plan', function ($p) use ($type): void {
                $p->where('type', $type);
            });
        } else {
            $query->whereHas('planPrices', function ($q) use ($haversine, $radius, $type): void {
                $q->whereHas(
                    'plan',
                    fn ($p) => $p->where('type', $type)
                )->whereHas('location', function ($loc) use ($haversine, $radius): void {
                    $loc->selectRaw(sprintf('locations.*, %s as distance', $haversine))
                        ->having('distance', '<=', $radius);
                });
            });
        }

        return $query->paginate($perPage);
    }

    public function findTreeByIdOrSlug(string $identifier, string $type): ?Tree
    {
        return Tree::query()
            ->where('is_active', true)
            ->with([
                'planPrices.plan' => fn ($q) => $q->where('type', $type),
            ])
            ->with([
                'treeInstances' => fn ($q) => $q->where('status', 'adoptable')
                    ->with('location'),
            ])
            ->withCount([
                'treeInstances as adoptable_count' => fn ($q) => $q->where('status', 'adoptable'),
            ])
            ->when(
                is_numeric($identifier),
                fn ($q) => $q->where('id', $identifier),
                fn ($q) => $q->where('slug', $identifier)
            )
            ->first();
    }
}
