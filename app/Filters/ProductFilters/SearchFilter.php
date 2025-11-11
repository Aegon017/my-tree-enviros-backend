<?php

namespace App\Filters\ProductFilters;

class SearchFilter
{
    public static function apply($query, $value)
    {
        $value = strtolower($value);
        $query->where(
            fn($q) =>
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$value}%"])
                ->orWhereRaw('LOWER(botanical_name) LIKE ?', ["%{$value}%"])
                ->orWhereRaw('LOWER(nick_name) LIKE ?', ["%{$value}%"])
        );
    }
}
