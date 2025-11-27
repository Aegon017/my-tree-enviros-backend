<?php

declare(strict_types=1);

namespace App\Filters\ProductFilters;

final class SearchFilter
{
    public static function apply($query, $value): void
    {
        $value = mb_strtolower((string) $value);
        $query->where(
            fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [sprintf('%%%s%%', $value)])
                ->orWhereRaw('LOWER(botanical_name) LIKE ?', [sprintf('%%%s%%', $value)])
                ->orWhereRaw('LOWER(nick_name) LIKE ?', [sprintf('%%%s%%', $value)])
        );
    }
}
