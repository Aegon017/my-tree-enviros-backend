<?php

namespace App\Filters\ProductFilters;

class SortFilter
{
    public static function apply($query, $field, $order)
    {
        if ($field === 'price') {
            $query
                ->leftJoin('inventories', 'inventories.product_id', '=', 'products.id')
                ->leftJoin('product_variants', 'product_variants.inventory_id', '=', 'inventories.id')
                ->selectRaw('MIN(CASE WHEN product_variants.base_price > 0 THEN product_variants.base_price END) AS min_price')
                ->groupBy('products.id', 'products.name', 'products.slug', 'products.short_description', 'products.product_category_id', 'products.created_at')
                ->orderByRaw('COALESCE(min_price, 0) ' . $order);
        } else {
            $query->orderBy("products.$field", $order);
        }
    }
}
