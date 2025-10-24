<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Slider",
 *     type="object",
 *     title="Slider",
 *     description="Homepage slider model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", nullable=true, example="Plant More Trees"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Join our green mission and plant a tree today."),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(
 *         property="main_image_url",
 *         type="string",
 *         format="uri",
 *         nullable=true,
 *         example="https://api.example.com/media/12345",
 *         description="Direct URL to access the slider image"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-02T12:34:56.000000Z")
 * )
 */
final class SliderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The model defines collection 'image' (single file).
        // Some admin forms might have used 'images' — gracefully fall back.
        $media =
            $this->getFirstMedia("image") ?: $this->getFirstMedia("images");

        $mainImageUrl = null;
        if ($media) {
            $mainImageUrl = $media->getFullUrl();
        }

        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "is_active" => (bool) $this->is_active,
            "main_image_url" => $mainImageUrl,
            "created_at" => $this->created_at?->toISOString(),
            "updated_at" => $this->updated_at?->toISOString(),
        ];
    }
}
