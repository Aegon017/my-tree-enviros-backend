<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Blogs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->getFirstMedia('images')->getFullUrl(),
            'category' => new BlogCategoryResource($this->whenLoaded('blogCategory')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
