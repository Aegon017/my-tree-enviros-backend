<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tree;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TreeRepository
{
    public function findPaginatedTrees(float $lat, float $lng, float $radius, string $type, int $perPage): LengthAwarePaginator
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
                'instances' => fn($q) => $q->where('status', 'available'),
            ])
            ->whereHas('instances.location', function ($q) use ($haversine, $radius) {
                $q->selectRaw("$haversine AS distance")
                    ->having('distance', '<=', $radius);
            });

        if ($type !== 'all') {
            $query->whereHas('planPrices.plan', fn($q) => $q->where('type', $type));
        }

        return $query->paginate($perPage);
    }

    public function findTreeByIdOrSlug(string $identifier): ?Tree
    {
        return Tree::query()
            ->with([
                'planPrices.plan',
                'instances' => fn($q) => $q->where('status', 'available')->limit(5),
                'instances.location',
            ])
            ->when(is_numeric($identifier), fn($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();
    }
}
