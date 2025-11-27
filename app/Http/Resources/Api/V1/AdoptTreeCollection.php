<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Traits\HasPaginationMeta;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class AdoptTreeCollection extends ResourceCollection
{
    use HasPaginationMeta;

    public function toArray($request)
    {
        return [
            'trees' => SponsorTreeListResource::collection($this->collection),
            'meta' => $this->paginationMeta($this->resource),
        ];
    }
}
