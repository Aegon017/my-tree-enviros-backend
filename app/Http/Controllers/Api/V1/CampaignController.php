<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\CampaignTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\Campaign;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Campaigns",
 *     description="Browse and view tree campaigns (feed, protect, plant)"
 * )
 *
 * @OA\Schema(
 *     schema="Campaign",
 *     type="object",
 *     title="Campaign",
 *     description="Campaign model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="location_id", type="integer", example=10),
 *     @OA\Property(property="type", type="string", enum={"feed", "protect", "plant"}, nullable=true, example="feed"),
 *     @OA\Property(property="type_label", type="string", nullable=true, example="Feed"),
 *     @OA\Property(property="name", type="string", example="Feed Trees in Bangalore"),
 *     @OA\Property(property="slug", type="string", example="feed-trees-in-bangalore"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Help us feed and nourish urban trees."),
 *     @OA\Property(property="amount", type="number", format="float", nullable=true, example=500.00, description="Suggested/default contribution amount"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true, example="2025-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="main_image_url", type="string", nullable=true, example="https://cdn.example.com/media/campaigns/1/main.jpg"),
 *     @OA\Property(property="thumbnail_url", type="string", nullable=true, example="https://cdn.example.com/media/campaigns/1/thumb.jpg"),
 *     @OA\Property(
 *         property="image_urls",
 *         type="array",
 *
 *         @OA\Items(type="string", example="https://cdn.example.com/media/campaigns/1/gallery-1.jpg")
 *     ),
 *
 *     @OA\Property(
 *         property="location",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=10),
 *         @OA\Property(property="name", type="string", example="Bangalore Urban"),
 *         @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *         @OA\Property(property="is_active", type="boolean", example=true),
 *         @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *         @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 */
final class CampaignController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/campaigns",
     *     summary="List campaigns",
     *     description="Get a paginated list of active campaigns with optional filters",
     *     tags={"Campaigns"},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by campaign type",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"feed", "protect", "plant"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by campaign name or description",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="feed")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"created_at","name"}, default="created_at")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="desc")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="campaigns",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Campaign")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=72),
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
        $query = Campaign::query()
            ->with('location')
            ->active();

        if ($request->filled('type')) {
            $type = $request->string('type')->toString();
            $allowedTypes = array_map(fn (CampaignTypeEnum $e) => $e->value, CampaignTypeEnum::cases());
            if (in_array($type, $allowedTypes, true)) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', (int) $request->input('location_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('description', 'like', sprintf('%%%s%%', $search));
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSortFields = ['created_at', 'name'];
        if (! in_array($sortBy, $allowedSortFields, true)) {
            $sortBy = 'created_at';
        }

        if (! in_array(mb_strtolower((string) $sortOrder), ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $campaigns = $query->paginate($perPage);

        return $this->success([
            'campaigns' => CampaignResource::collection($campaigns->items()),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
                'from' => $campaigns->firstItem(),
                'to' => $campaigns->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/campaigns/{id}",
     *     summary="Get campaign details",
     *     description="Get detailed information about a specific campaign",
     *     tags={"Campaigns"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="campaign", ref="#/components/schemas/Campaign")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Resource not found")
     *         )
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::with('location')
            ->active()
            ->find($id);

        if (! $campaign) {
            return $this->notFound('Campaign not found');
        }

        return $this->success([
            'campaign' => new CampaignResource($campaign),
        ]);
    }
}
