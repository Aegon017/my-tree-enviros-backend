<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TreePlanPriceResource;
use App\Http\Resources\Api\V1\TreeResource;
use App\Models\Location;
use App\Models\Tree;
use App\Models\TreePlanPrice;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Trees",
 *     description="Tree browsing, sponsorship and adoption endpoints"
 * )
 */
final class TreeController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/trees",
     *     summary="List all trees",
     *     description="Get paginated list of all active trees with optional filters and search",
     *     tags={"Trees"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search trees by name",
     *         required=false,
     *         @OA\Schema(type="string", example="oak")
     *     ),
     *     @OA\Parameter(
     *         name="min_age",
     *         in="query",
     *         description="Minimum age filter",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="max_age",
     *         in="query",
     *         description="Maximum age filter",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="age_unit",
     *         in="query",
     *         description="Age unit filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "month", "year"}, example="year")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "age", "created_at"}, default="name")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="trees",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Tree")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=75),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=15)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tree::query()
            ->with(["planPrices.plan"])
            ->where("is_active", true)
            ->withCount([
                "instances" => function ($query) {
                    $query->where("status", "available");
                },
            ]);

        // Search by name
        if ($request->has("search")) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        // Filter by age range
        if ($request->has("min_age")) {
            $query->where("age", ">=", $request->min_age);
        }

        if ($request->has("max_age")) {
            $query->where("age", "<=", $request->max_age);
        }

        // Filter by age unit
        if ($request->has("age_unit")) {
            $query->where("age_unit", $request->age_unit);
        }

        // Sort options
        $sortBy = $request->input("sort_by", "name");
        $sortOrder = $request->input("sort_order", "asc");

        $allowedSortFields = ["name", "age", "created_at"];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->input("per_page", 15), 50);
        $trees = $query->paginate($perPage);

        return $this->success([
            "trees" => TreeResource::collection($trees->items()),
            "meta" => [
                "current_page" => $trees->currentPage(),
                "last_page" => $trees->lastPage(),
                "per_page" => $trees->perPage(),
                "total" => $trees->total(),
                "from" => $trees->firstItem(),
                "to" => $trees->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trees/{id}",
     *     summary="Get tree details",
     *     description="Get detailed information about a specific tree",
     *     tags={"Trees"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tree ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="tree", ref="#/components/schemas/Tree")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tree not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $tree = Tree::query()
            ->with([
                "planPrices.plan",
                "instances" => function ($query) {
                    $query->where("status", "available")->limit(5);
                },
                "instances.location",
            ])
            ->where("is_active", true)
            ->find($id);

        if (!$tree) {
            return $this->notFound("Tree not found");
        }

        return $this->success([
            "tree" => new TreeResource($tree),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trees/sponsorship",
     *     summary="Get sponsorship trees",
     *     description="Get all trees available for sponsorship with optional filters",
     *     tags={"Trees"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search trees by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="trees",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Tree")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function sponsorship(Request $request): JsonResponse
    {
        $query = Tree::query()
            ->with(["planPrices.plan"])
            ->where("is_active", true)
            ->whereHas("planPrices.plan", function ($query) {
                $query->where("type", "sponsorship")->where("is_active", true);
            })
            ->withCount([
                "instances" => function ($query) {
                    $query->where("status", "available");
                },
            ]);

        // Search
        if ($request->has("search")) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        // Filter by location
        if ($request->filled("location_id")) {
            $location = Location::find($request->location_id);

            if ($location) {
                $locationIds = collect([$location->id])
                    ->merge($location->allAncestors()->pluck("id"))
                    ->unique()
                    ->toArray();

                $query->whereHas("instances", function ($q) use ($locationIds) {
                    $q->whereIn("location_id", $locationIds)->where(
                        "status",
                        "available",
                    );
                });
            }
        }

        $perPage = min($request->input("per_page", 15), 50);
        $trees = $query->paginate($perPage);

        return $this->success([
            "trees" => TreeResource::collection($trees->items()),
            "meta" => [
                "current_page" => $trees->currentPage(),
                "last_page" => $trees->lastPage(),
                "per_page" => $trees->perPage(),
                "total" => $trees->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trees/adoption",
     *     summary="Get adoption trees",
     *     description="Get all trees available for adoption with optional filters",
     *     tags={"Trees"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search trees by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="trees",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Tree")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function adoption(Request $request): JsonResponse
    {
        $query = Tree::query()
            ->with(["planPrices.plan"])
            ->where("is_active", true)
            ->whereHas("planPrices.plan", function ($query) {
                $query->where("type", "adoption")->where("is_active", true);
            })
            ->withCount([
                "instances" => function ($query) {
                    $query->where("status", "available");
                },
            ]);

        // Search
        if ($request->has("search")) {
            $query->where("name", "like", "%" . $request->search . "%");
        }

        if ($request->filled("location_id")) {
            $location = Location::find($request->location_id);

            if ($location) {
                $locationIds = collect([$location->id])
                    ->merge($location->allAncestors()->pluck("id"))
                    ->unique()
                    ->toArray();

                $query->whereHas("instances", function ($q) use ($locationIds) {
                    $q->whereIn("location_id", $locationIds)->where(
                        "status",
                        "available",
                    );
                });
            }
        }

        $perPage = min($request->input("per_page", 15), 50);
        $trees = $query->paginate($perPage);

        return $this->success([
            "trees" => TreeResource::collection($trees->items()),
            "meta" => [
                "current_page" => $trees->currentPage(),
                "last_page" => $trees->lastPage(),
                "per_page" => $trees->perPage(),
                "total" => $trees->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trees/{id}/plans",
     *     summary="Get tree pricing plans",
     *     description="Get all pricing plans available for a specific tree",
     *     tags={"Trees"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tree ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by plan type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"sponsorship", "adoption"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="tree_id", type="integer", example=1),
     *                 @OA\Property(property="tree_name", type="string", example="Oak Tree"),
     *                 @OA\Property(
     *                     property="plans",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/TreePlanPrice")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tree not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function plans(string $treeId, Request $request): JsonResponse
    {
        $tree = Tree::where("is_active", true)->find($treeId);

        if (!$tree) {
            return $this->notFound("Tree not found");
        }

        $query = TreePlanPrice::query()
            ->with(["plan", "tree"])
            ->where("tree_id", $treeId)
            ->where("is_active", true)
            ->whereHas("plan", function ($query) {
                $query->where("is_active", true);
            });

        // Filter by type (sponsorship/adoption)
        if ($request->has("type")) {
            $query->whereHas("plan", function ($q) use ($request) {
                $q->where("type", $request->type);
            });
        }

        $planPrices = $query->get();

        return $this->success([
            "tree_id" => $tree->id,
            "tree_name" => $tree->name,
            "plans" => TreePlanPriceResource::collection($planPrices),
        ]);
    }
}
