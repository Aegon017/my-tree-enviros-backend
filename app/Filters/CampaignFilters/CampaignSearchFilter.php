<?php

declare(strict_types=1);

namespace App\Filters\CampaignFilters;

use Illuminate\Database\Eloquent\Builder;

final class CampaignSearchFilter
{
    public static function apply(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search): void {
            $q->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $search));
        });
    }
}
