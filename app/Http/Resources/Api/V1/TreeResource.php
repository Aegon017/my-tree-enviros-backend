<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

final class TreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Build signed URLs for main image and thumbnail (expires in 60 minutes)
        $mainImage =
            $this->getFirstMedia("images") ??
            $this->getFirstMedia("thumbnails");
        $mainImageUrl = null;
        if ($mainImage) {
            $mainImageUrl = URL::temporarySignedRoute(
                "media.show",
                now()->addMinutes(60),
                ["id" => $mainImage->id],
            );
        }

        $thumbnail =
            $this->getFirstMedia("thumbnails") ??
            $this->getFirstMedia("images");
        $thumbnailUrl = null;
        if ($thumbnail) {
            $thumbnailUrl = URL::temporarySignedRoute(
                "media.show",
                now()->addMinutes(60),
                ["id" => $thumbnail->id],
            );
        }

        return [
            "id" => $this->id,
            "sku" => $this->sku,
            "name" => $this->name,
            "slug" => $this->slug,
            "age" => $this->age,
            "age_unit" => $this->age_unit,
            "age_display" => $this->age . " " . ucfirst($this->age_unit->value),
            "description" => $this->description,
            "is_active" => $this->is_active,
            "main_image_url" => $mainImageUrl,
            "thumbnail" => $thumbnailUrl,
            "images" => $this->getMedia("images")->map(
                fn($media) => [
                    "id" => $media->id,
                    "image_url" => URL::temporarySignedRoute(
                        "media.show",
                        now()->addMinutes(60),
                        ["id" => $media->id],
                    ),
                ],
            ),
            "available_instances_count" => $this->whenCounted(
                "instances",
                function () {
                    return $this->instances()
                        ->where("status", "available")
                        ->count();
                },
            ),
            "plan_prices" => TreePlanPriceResource::collection(
                $this->whenLoaded("planPrices"),
            ),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "locations" => \App\Http\Resources\Api\V1\LocationResource::collection(
                $this->whenLoaded(
                    "instances",
                    fn() => $this->instances
                        ->pluck("location")
                        ->filter()
                        ->unique("id"),
                ),
            ),
        ];
    }
}
