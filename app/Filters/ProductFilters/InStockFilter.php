<?php

namespace App\Filters\ProductFilters;

class InStockFilter
{
    public static function apply($query, $value)
    {
        $query->whereHas('inventory.productVariants', fn($q) => $q->where('is_instock', filter_var($value, FILTER_VALIDATE_BOOLEAN)));
    }
}
