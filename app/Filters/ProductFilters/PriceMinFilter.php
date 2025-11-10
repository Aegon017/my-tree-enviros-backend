<?php

namespace App\Filters\ProductFilters;

class PriceMinFilter
{
    public static function apply($query, $value)
    {
        $min = (float) $value;

        $query->whereHas('inventory.productVariants', function ($q) use ($min) {
            $q->where('base_price', '>', 0)
              ->groupBy('inventory_id')
              ->havingRaw('MIN(base_price) >= ?', [$min]);
        });
    }
}
