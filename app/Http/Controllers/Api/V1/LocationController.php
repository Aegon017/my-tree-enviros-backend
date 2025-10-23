<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LocationResource;
use App\Models\Location;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Locations",
 *     description="Location management endpoints"
 * )
 */
final class LocationController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/locations",
     *     summary="Get all active locations",
     *     description="Retrieve list of all active locations with optional parent filtering",
     *     tags={"Locations"},
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="Filter by parent location ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by location type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"country", "state", "city", "area"})
     *     ),
     *     @OA\Parameter(
     *         name="with_children",
     *         in="query",
     *         description="Include children locations",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="locations",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Location")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Location::query()->where('is_active', true);

        // Filter by type (city, state, country, area)
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by parent location
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Include children if requested
        if ($request->boolean('with_children')) {
            $query->with(['children' => function ($q) {
                $q->where('is_active', true);
            }]);
        }

        // Include parent if requested
        if ($request->boolean('with_parent')) {
            $query->with('parent');
        }

        // Order by name
        $query->orderBy('name', 'asc');

        $locations = $query->get();

        return $this->success([
            'locations' => LocationResource::collection($locations),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/root",
     *     summary="Get root locations",
     *     description="Retrieve all top-level locations (without parent)",
     *     tags={"Locations"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="locations",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Location")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function root(): JsonResponse
    {
        $locations = Location::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();

        return $this->success([
            'locations' => LocationResource::collection($locations),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}",
     *     summary="Get location by ID",
     *     description="Retrieve a specific location with its details",
     *     tags={"Locations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with_children",
     *         in="query",
     *         description="Include children locations",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="with_parent",
     *         in="query",
     *         description="Include parent location",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="location", ref="#/components/schemas/Location")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $query = Location::query()->where('is_active', true);

        if ($request->boolean('with_children')) {
            $query->with(['children' => function ($q) {
                $q->where('is_active', true);
            }]);
        }

        if ($request->boolean('with_parent')) {
            $query->with('parent');
        }

        $location = $query->find($id);

        if (!$location) {
            return $this->notFound('Location not found');
        }

        return $this->success([
            'location' => new LocationResource($location),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}/children",
     *     summary="Get location children",
     *     description="Retrieve all child locations of a specific location",
     *     tags={"Locations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Parent location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="locations",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Location")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
     */
    public function children(int $id): JsonResponse
    {
        $location = Location::where('is_active', true)->find($id);

        if (!$location) {
            return $this->notFound('Location not found');
        }

        $children = $location->children()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return $this->success([
            'locations' => LocationResource::collection($children),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}/tree-count",
     *     summary="Get available tree count for location",
     *     description="Get count of available trees in a specific location",
     *     tags={"Locations"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Location ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="total_trees", type="integer", example=150),
     *                 @OA\Property(property="available_trees", type="integer", example=45)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
     */
    public function treeCount(int $id): JsonResponse
    {
        $location = Location::where('is_active', true)->find($id);

        if (!$location) {
            return $this->notFound('Location not found');
        }

        $totalTrees = $location->treeInstances()->count();
        $availableTrees = $location->treeInstances()
            ->where('status', 'available')
            ->count();

        return $this->success([
            'location_id' => $location->id,
            'total_trees' => $totalTrees,
            'available_trees' => $availableTrees,
        ]);
    }
}
