<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tree;
use App\Repositories\SponsorTreeRepository;

final readonly class SponsorTreeService
{
    public function __construct(
        private SponsorTreeRepository $repo
    ) {}

    public function getSponsorList(float $lat, float $lng, float $radius, int $perPage)
    {
        return $this->repo->getSponsorList($lat, $lng, $radius, $perPage);
    }

    public function getByIdOrSlug(string $identifier): ?Tree
    {
        return $this->repo->findTreeByIdOrSlug($identifier);
    }
}
