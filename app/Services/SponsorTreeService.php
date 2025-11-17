<?php

namespace App\Services;

use App\Models\Tree;
use App\Repositories\SponsorTreeRepository;

class SponsorTreeService
{
    public function __construct(
        private readonly SponsorTreeRepository $repo
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
