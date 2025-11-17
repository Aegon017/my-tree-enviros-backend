<?php

namespace App\Http\Resources\Api\V1;

use App\Traits\HasPaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    use HasPaginationMeta;

    public function toArray(Request $request): array
    {
        return [
            'products' => ProductListResource::collection($this->collection),
            'meta' => $this->paginationMeta($this->resource),
        ];
    }
}
