<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Location",
 *     type="object",
 *     title="Location",
 *     description="Location model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Hyderabad"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'depth' => $this->when(
                $request->boolean('with_depth'),
                fn () => $this->depth()
            ),
            'parent' => $this->whenLoaded(
                'parent',
                fn () => new self($this->parent)
            ),
            'children' => $this->whenLoaded(
                'children',
                fn () => self::collection($this->children)
            ),
            'tree_count' => $this->when(
                $request->boolean('with_tree_count'),
                fn () => [
                    'total' => $this->treeInstances()->count(),
                    'available' => $this->treeInstances()
                        ->where('status', 'available')
                        ->count(),
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
