<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SliderResource;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SliderController extends Controller
{
    /**
     * List sliders.
     *
     * By default, only active sliders are returned. You can override this by passing the `active` query param.
     * You can also limit the number of results and control the sort order.
     *
     * Query params:
     * - active: boolean (default: true)
     * - limit: integer (optional, 1-100)
     * - order: string (asc|desc, default: desc)
     *
     * @OA\Get(
     *   path="/api/sliders",
     *   operationId="listSliders",
     *   tags={"Sliders"},
     *   summary="List sliders",
     *   description="Returns a list of sliders for the homepage. Active-only by default.",
     *
     *   @OA\Parameter(
     *     name="active",
     *     in="query",
     *     required=false,
     *     description="Filter by active status (true/false). Defaults to true.",
     *
     *     @OA\Schema(type="boolean")
     *   ),
     *
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     required=false,
     *     description="Limit the number of returned sliders (1-100).",
     *
     *     @OA\Schema(type="integer", minimum=1, maximum=100)
     *   ),
     *
     *   @OA\Parameter(
     *     name="order",
     *     in="query",
     *     required=false,
     *     description="Sort order by ID (asc or desc). Defaults to desc.",
     *
     *     @OA\Schema(type="string", enum={"asc","desc"})
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *
     *         @OA\Items(ref="#/components/schemas/Slider")
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Slider::query();

        // Active filter - default to only active sliders
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        } else {
            $query->where('is_active', true);
        }

        // Sort order
        $order = mb_strtolower((string) $request->get('order', 'desc'));
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        $query->orderBy('id', $order);

        // Optional limit
        $limit = $request->integer('limit');
        if ($limit !== 0 && $limit !== null) {
            $limit = max(1, min(100, $limit));
            $query->limit($limit);
        }

        return SliderResource::collection($query->get());
    }

    /**
     * Show a single slider by ID.
     *
     * @OA\Get(
     *   path="/api/sliders/{id}",
     *   operationId="getSlider",
     *   tags={"Sliders"},
     *   summary="Get slider by ID",
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="Slider ID",
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(property="data", ref="#/components/schemas/Slider")
     *     )
     *   ),
     *
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): SliderResource
    {
        $slider = Slider::query()->findOrFail($id);

        return new SliderResource($slider);
    }
}
