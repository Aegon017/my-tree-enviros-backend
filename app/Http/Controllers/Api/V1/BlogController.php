<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlogResource;
use App\Models\Blog;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/blogs",
     *     summary="List blogs",
     *     description="Get paginated list of blogs with optional filters",
     *     tags={"Blogs"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search blogs by title or short description",
     *         required=false,
     *         @OA\Schema(type="string", example="mango")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by blog category id",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "title"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=12, maximum=50)
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
     *                     property="blogs",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Blog")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=12),
     *                     @OA\Property(property="total", type="integer", example=60),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="to", type="integer", example=12)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Blog::query()
            ->with(['blogCategory']);

        // Search by title or short_description
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%');
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('blog_category_id', (int) $request->input('category_id'));
        }

        // Sorting
        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSortFields = ['created_at', 'title'];
        if (!in_array($sortBy, $allowedSortFields, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min((int) $request->input('per_page', 12), 50);
        $blogs = $query->paginate($perPage);

        return $this->success([
            'blogs' => BlogResource::collection($blogs->items()),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'from' => $blogs->firstItem(),
                'to' => $blogs->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/blogs/{id}",
     *     summary="Get blog details",
     *     description="Get detailed blog data",
     *     tags={"Blogs"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Blog ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
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
     *                 @OA\Property(property="blog", ref="#/components/schemas/Blog")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Blog not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $blog = Blog::query()
            ->with(['blogCategory'])
            ->find($id);

        if (!$blog) {
            return $this->notFound('Blog not found');
        }

        return $this->success([
            'blog' => new BlogResource($blog),
        ]);
    }
}
