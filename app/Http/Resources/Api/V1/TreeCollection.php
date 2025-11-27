<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Trees\TreeListResource;
use App\Traits\HasPaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class TreeCollection extends ResourceCollection
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
