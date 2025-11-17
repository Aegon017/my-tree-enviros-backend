<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Traits\HasPaginationMeta;

class AdoptTreeCollection extends ResourceCollection
{
    use HasPaginationMeta;

    public function toArray($request)
    {
        return [
            'trees' => SponsorTreeListResource::collection($this->collection),
            'meta'  => $this->paginationMeta($this->resource),
        ];
    }
}