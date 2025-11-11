<?php

namespace App\Filters\ProductFilters;

class CategoryFilter
{
    public static function apply($query, $value)
    {
        $query->where('product_category_id', $value);
    }
}
