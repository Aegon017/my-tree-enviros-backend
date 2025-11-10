<?php

namespace App\Filters\ProductFilters;

class PriceMaxFilter
{
    public static function apply($query, $value)
    {
        $max = (float) $value;

        $query->whereHas('inventory.productVariants', function ($q) use ($max) {
            $q->where('base_price', '>', 0)
              ->groupBy('inventory_id')
              ->havingRaw('MIN(base_price) <= ?', [$max]);
        });
    }
}
