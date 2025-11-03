<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BlogResource extends JsonResource
{
    /**
     * @OA\Schema(
     *   schema="Blog",
     *   type="object",
     *   title="Blog",
     *   description="Blog post resource",
     *
     *   @OA\Property(property="id", type="integer", example=1),
     *   @OA\Property(property="blog_category_id", type="integer", example=2),
     *   @OA\Property(
     *     property="blog_category",
     *     type="object",
     *     nullable=true,
     *     @OA\Property(property="id", type="integer", example=2),
     *     @OA\Property(property="name", type="string", example="Environment")
     *   ),
     *   @OA\Property(property="title", type="string", example="Greening the City: A Mango Tree Story"),
     *   @OA\Property(property="slug", type="string", example="greening-the-city-a-mango-tree-story"),
     *   @OA\Property(property="short_description", type="string", example="How urban planting can transform communities."),
     *   @OA\Property(property="description", type="string", example="<p>Full HTML content here...</p>"),
     *   @OA\Property(
     *     property="thumbnail_url",
     *     type="string",
     *     format="uri",
     *     nullable=true,
     *     example="http://localhost:8000/media/123"
     *   ),
     *   @OA\Property(
     *     property="images",
     *     type="array",
     *
     *     @OA\Items(
     *       type="object",
     *
     *       @OA\Property(property="id", type="integer", example=123),
     *       @OA\Property(
     *         property="image_url",
     *         type="string",
     *         format="uri",
     *         example="http://localhost:8000/media/124"
     *       )
     *     )
     *   ),
     *   @OA\Property(
     *     property="image_urls",
     *     type="array",
     *
     *     @OA\Items(type="string", format="uri")
     *   ),
     *
     *   @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-22T13:55:09.000000Z"),
     *   @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-22T13:55:09.000000Z")
     * )
     */
    public function toArray(Request $request): array
    {
        // Direct thumbnail URL (if exists)
        $thumbnail = $this->getFirstMedia('thumbnails');
        $thumbnailUrl = null;
        if ($thumbnail) {
            $thumbnailUrl = $thumbnail->getFullUrl();
        }

        // Direct image URLs for gallery
        $image = $this->getFirstMedia('images');
        $imageUrl = null;
        if ($image) {
            $imageUrl = $image->getFullUrl();
        }

        return [
            'id' => $this->id,
            'blog_category_id' => $this->blog_category_id,
            'blog_category' => $this->whenLoaded('blogCategory', fn (): array => [
                'id' => $this->blogCategory->id,
                'name' => $this->blogCategory->name,
            ]),
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'thumbnail_url' => $thumbnailUrl,
            'image_url' => $imageUrl,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
