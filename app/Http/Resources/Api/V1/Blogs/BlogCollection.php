<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Blogs;

use App\Traits\HasPaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class BlogCollection extends ResourceCollection
{
    use HasPaginationMeta;

    public function toArray(Request $request): array
    {
        return [
            'blogs' => BlogListResource::collection($this->collection),
            'meta' => $this->paginationMeta($this->resource),
        ];
    }
}
