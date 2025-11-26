<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Product Categories",
 *     description="Endpoints for browsing product categories"
 * )
 */
final class ProductCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/product-categories",
     *     tags={"Product Categories"},
     *     summary="List product categories",
     *     description="Get all product categories available for filtering products in the store.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Indoor Plants"),
     *                         @OA\Property(property="slug", type="string", example="indoor-plants")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $categories = ProductCategory::query()
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image_url' => $category->getFirstMedia('images')?->getFullUrl(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
            ],
        ]);
    }
}
