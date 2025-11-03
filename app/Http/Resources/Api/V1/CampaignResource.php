<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

use function method_exists;

/**
 * Campaign API Resource
 *
 * Shapes the campaign output for the frontend:
 * - Flattens enum fields (type, type_label)
 * - Adds media URLs (main_image_url, thumbnail_url, image_urls)
 * - Embeds minimal location information
 */
final class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $mainImage =
            $this->getFirstMediaUrlSafe('thumbnails') ?:
            $this->getFirstMediaUrlSafe('images') ?:
            null;

        $thumbnailUrl = $this->getFirstMediaUrlSafe('thumbnails') ?: null;

        $imageUrls = $this->getMediaUrlsSafe('images');

        return [
            'id' => $this->id,
            'location_id' => $this->location_id,

            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),

            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'description' => $this->description,

            // Suggested/default contribution amount
            'amount' => $this->amount !== null ? (float) $this->amount : null,

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => (bool) $this->is_active,

            // Media
            'main_image_url' => $mainImage,
            'thumbnail_url' => $thumbnailUrl,
            'image_urls' => $imageUrls,

            // Minimal location payload (when relation is loaded)
            'location' => $this->whenLoaded('location', function (): array {
                $loc = $this->location;

                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'parent_id' => $loc->parent_id,
                    'is_active' => (bool) $loc->is_active,
                    'created_at' => $loc->created_at?->toIso8601String(),
                    'updated_at' => $loc->updated_at?->toIso8601String(),
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Safely get first media URL for a collection if medialibrary is available.
     */
    private function getFirstMediaUrlSafe(string $collection): ?string
    {
        if (method_exists($this->resource, 'getFirstMediaUrl')) {
            $url = $this->resource->getFirstMediaUrl($collection);

            return $url ?: null;
        }

        return null;
    }

    /**
     * Safely get all media URLs for a collection if medialibrary is available.
     *
     * @return array<int, string>
     */
    private function getMediaUrlsSafe(string $collection): array
    {
        if (method_exists($this->resource, 'getMedia')) {
            try {
                return $this->resource
                    ->getMedia($collection)
                    ->map(fn ($media) => $media->getUrl())
                    ->filter()
                    ->values()
                    ->all();
            } catch (Throwable) {
                // If anything goes wrong, return empty array and don't block the API.
            }
        }

        return [];
    }
}
