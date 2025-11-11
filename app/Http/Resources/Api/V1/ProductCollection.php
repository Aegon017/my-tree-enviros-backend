<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * @return array
     */
    public function toArray(Request $request): array
    {
        $meta = [];
        if ($this->resource instanceof LengthAwarePaginator) {
            $meta = [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ];
        }

        return [
            'products' => ProductListResource::collection($this->collection),
            'meta' => $meta,
        ];
    }
}
