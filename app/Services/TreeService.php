<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tree;
use App\Repositories\TreeRepository;

final readonly class TreeService
{
    public function __construct(private TreeRepository $repository) {}

    public function getTrees(float $lat, float $lng, float $radius, string $type, int $perPage): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->findPaginatedTrees($lat, $lng, $radius, $type, $perPage);
    }

    public function getByIdOrSlug(string $identifier, string $type): ?Tree
    {
        return $this->repository->findTreeByIdOrSlug($identifier, $type);
    }
}
