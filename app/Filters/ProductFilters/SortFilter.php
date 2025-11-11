<?php

namespace App\Filters\ProductFilters;

use Illuminate\Support\Facades\Log;

class SortFilter
{
    public static function apply($query, $field, $order)
    {
        Log::info('Sort filter applied', [
            'field' => $field,
            'order' => $order,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        $query->orderBy("products.$field", $order);
    }
}