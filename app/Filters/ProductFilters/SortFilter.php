<?php

namespace App\Filters\ProductFilters;

class SortFilter
{
    public static function apply($query, $field, $order)
    {
        $query->orderBy("products.$field", $order);
    }
}