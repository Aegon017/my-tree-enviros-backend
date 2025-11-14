<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdoptTreeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\TreeInstance;

final class AdoptTreeService
{
    public function __construct(
        private readonly AdoptTreeRepository $repo
    ) {}

    public function getAdoptList(float $lat, float $lng, float $radius, int $perPage): LengthAwarePaginator
    {
        return $this->repo->getAdoptList($lat, $lng, $radius, $perPage);
    }

    public function getAdoptDetails(int $instanceId): ?TreeInstance
    {
        return $this->repo->getAdoptDetails($instanceId);
    }
}
