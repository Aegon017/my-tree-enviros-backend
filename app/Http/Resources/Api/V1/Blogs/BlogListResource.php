<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Blogs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumbnail_url' => $this->getFirstMedia('thumbnails')->getFullUrl(),
            'short_description' => $this->short_description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
