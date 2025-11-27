<?php

declare(strict_types=1);

namespace App\Filters\ProductFilters;

final class SortFilter
{
    public static function apply($query, string $field, $order): void
    {
        $query->orderBy('products.'.$field, $order);
    }
}
