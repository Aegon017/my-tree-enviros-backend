<?php

declare(strict_types=1);

namespace App\Filters\ProductFilters;

final class InStockFilter
{
    public static function apply($query, $value): void
    {
        $query->whereHas('inventory.productVariants', fn ($q) => $q->where('is_instock', filter_var($value, FILTER_VALIDATE_BOOLEAN)));
    }
}
