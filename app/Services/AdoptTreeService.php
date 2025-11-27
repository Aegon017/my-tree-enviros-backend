<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TreeInstance;
use App\Repositories\AdoptTreeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AdoptTreeService
{
    public function __construct(
        private AdoptTreeRepository $repo
    ) {}

    public function getAdoptList(float $lat, float $lng, float $radius, int $perPage): LengthAwarePaginator
    {
        return $this->repo->getAdoptList($lat, $lng, $radius, $perPage);
    }

    public function getAdoptDetails(int|string $identifier): ?TreeInstance
    {
        return $this->repo->getAdoptDetails($identifier);
    }
}
