<?php

declare(strict_types=1);

namespace App\Filters\CampaignFilters;

use Illuminate\Database\Eloquent\Builder;

final class CampaignSortFilter
{
    private const ALLOWED_SORT_FIELDS = ['created_at', 'name', 'start_date', 'end_date'];

    private const ALLOWED_SORT_ORDERS = ['asc', 'desc'];

    public static function apply(Builder $query, string $sortBy = 'created_at', string $sortOrder = 'desc'): void
    {
        $sortBy = in_array($sortBy, self::ALLOWED_SORT_FIELDS, true) ? $sortBy : 'created_at';
        $sortOrder = in_array(mb_strtolower($sortOrder), self::ALLOWED_SORT_ORDERS, true) ? $sortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);
    }
}
