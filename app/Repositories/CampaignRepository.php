<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;

final class CampaignRepository
{
    public function baseQuery(): Builder
    {
        return Campaign::query()->with('location:id,name,parent_id');
    }

    public function find(string $id): ?Campaign
    {
        return $this->baseQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Campaign
    {
        return $this->baseQuery()->where('slug', $slug)->first();
    }
}
