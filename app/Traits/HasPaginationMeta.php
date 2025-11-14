<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait HasPaginationMeta
{
    protected function paginationMeta($resource): array
    {
        if (! $resource instanceof LengthAwarePaginator) {
            return [];
        }

        return [
            'current_page' => $resource->currentPage(),
            'last_page'    => $resource->lastPage(),
            'per_page'     => $resource->perPage(),
            'total'        => $resource->total(),
            'from'         => $resource->firstItem(),
            'to'           => $resource->lastItem(),
        ];
    }
}
