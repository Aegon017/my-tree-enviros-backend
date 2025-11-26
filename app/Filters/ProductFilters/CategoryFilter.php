<?php

declare(strict_types=1);

namespace App\Filters\ProductFilters;

final class CategoryFilter
{
    public static function apply($query, $value): void
    {
        $query->where('product_category_id', $value);
    }
}
