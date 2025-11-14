<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Trees\TreeListResource;
use App\Traits\HasPaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TreeCollection extends ResourceCollection
{
    use HasPaginationMeta;

    public function toArray(Request $request): array
    {
        return [
            'trees' => TreeListResource::collection($this->collection),
            'meta' => $this->paginationMeta($this->resource),
        ];
    }
}
