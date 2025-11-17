<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TreeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Tree;

final class TreeService
{
    public function __construct(private readonly TreeRepository $repository) {}

    public function getTrees(float $lat, float $lng, float $radius, string $type, int $perPage): LengthAwarePaginator
    {
        return $this->repository->findPaginatedTrees($lat, $lng, $radius, $type, $perPage);
    }

    public function getByIdOrSlug(string $identifier, string $type): ?Tree
    {
        return $this->repository->findTreeByIdOrSlug($identifier, $type);
    }
}
