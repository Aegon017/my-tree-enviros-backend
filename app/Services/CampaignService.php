<?php

declare(strict_types=1);

namespace App\Services;

use App\Filters\CampaignFilters\CampaignSearchFilter;
use App\Filters\CampaignFilters\CampaignSortFilter;
use App\Http\Resources\Api\V1\CampaignCollection;
use App\Repositories\CampaignRepository;
use Illuminate\Http\Request;

final readonly class CampaignService
{
    public function __construct(private CampaignRepository $repo) {}

    public function paginate(Request $request): CampaignCollection
    {
        $query = $this->repo->baseQuery();

        if ($request->filled('search')) {
            CampaignSearchFilter::apply($query, $request->string('search')->toString());
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        CampaignSortFilter::apply($query, $sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $campaigns = $query->paginate($perPage);

        return new CampaignCollection($campaigns);
    }

    public function findByIdOrSlug(string $identifier): ?object
    {
        if (is_numeric($identifier)) {
            return $this->repo->find($identifier);
        }

        return $this->repo->findBySlug($identifier);
    }
}
