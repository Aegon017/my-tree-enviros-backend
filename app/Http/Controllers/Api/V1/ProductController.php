<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;
use App\Traits\ResponseHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="E-commerce product management endpoints"
 * )
 */
final class ProductController extends Controller
{
    use ResponseHelpers;

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="List all products",
     *     description="Get paginated list of all active products with optional filters",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search products by name, botanical name, or nick name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="in_stock",
     *         in="query",
     *         description="Filter by stock availability (1=in stock, 0=out of stock)",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field (name, created_at, price)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "created_at", "price"}, default="name")
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
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
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
        $query = Product::query()
            ->with([
                "productCategory",
                "inventory.productVariants.variant.color",
                "inventory.productVariants.variant.size",
                "inventory.productVariants.variant.planter"
            ])
            ->where("is_active", true);

        // Search by name, botanical name, or nick name
        if ($request->has("search")) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%" . $search . "%")
                    ->orWhere("botanical_name", "like", "%" . $search . "%")
                    ->orWhere("nick_name", "like", "%" . $search . "%");
            });
        }

        // Filter by category
        if ($request->has("category_id")) {
            $query->where("product_category_id", $request->category_id);
        }

        // Filter by stock availability
        if ($request->has("in_stock")) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            $query->whereHas("inventory", function ($q) use ($inStock) {
                $q->where("is_instock", $inStock);
            });
        }

        // Sort options
        $sortBy = $request->input("sort_by", "name");
        $sortOrder = $request->input("sort_order", "asc");

        $allowedSortFields = ["name", "created_at"];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->input("per_page", 15), 50);
        $products = $query->paginate($perPage);

        // Check wishlist status if user is authenticated
        if ($request->user()) {
            $wishlist = Wishlist::where(
                "user_id",
                $request->user()->id,
            )->first();
            if ($wishlist) {
                $wishlistProductIds = $wishlist
                    ->items()
                    ->pluck("product_id")
                    ->toArray();
                $products
                    ->getCollection()
                    ->transform(function ($product) use ($wishlistProductIds) {
                        $product->in_wishlist = in_array(
                            $product->id,
                            $wishlistProductIds,
                        );
                        return $product;
                    });
            }
        }

        return $this->success([
            "data" => ProductResource::collection($products->items()),
            "meta" => [
                "current_page" => $products->currentPage(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
                "from" => $products->firstItem(),
                "to" => $products->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     summary="Get product details",
     *     description="Get detailed information about a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
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
     *                 @OA\Property(property="product", ref="#/components/schemas/Product")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::with([
            "productCategory",
            "inventory.productVariants" => function ($query) {
                $query->with('variant.color', 'variant.size', 'variant.planter');
            },
        ])
            ->where("is_active", true)
            ->find($id);

        if (!$product) {
            return $this->notFound("Product not found");
        }

        // Check wishlist status if user is authenticated
        if ($request->user()) {
            $wishlist = Wishlist::where(
                "user_id",
                $request->user()->id,
            )->first();
            if ($wishlist) {
                $product->in_wishlist = $wishlist->hasProduct($product->id);
            }
        }

        return $this->success([
            "product" => new ProductResource($product),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/category/{categoryId}",
     *     summary="Get products by category",
     *     description="Get all products in a specific category",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
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
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function byCategory(
        Request $request,
        string $categoryId,
    ): JsonResponse {
        $query = Product::with([
            "productCategory",
            "inventory.productVariants.variant.color",
            "inventory.productVariants.variant.size",
            "inventory.productVariants.variant.planter"
        ])
            ->where("is_active", true)
            ->where("product_category_id", $categoryId);

        $perPage = min($request->input("per_page", 15), 50);
        $products = $query->paginate($perPage);

        // Check wishlist status if user is authenticated
        if ($request->user()) {
            $wishlist = Wishlist::where(
                "user_id",
                $request->user()->id,
            )->first();
            if ($wishlist) {
                $wishlistProductIds = $wishlist
                    ->items()
                    ->pluck("product_id")
                    ->toArray();
                $products
                    ->getCollection()
                    ->transform(function ($product) use ($wishlistProductIds) {
                        $product->in_wishlist = in_array(
                            $product->id,
                            $wishlistProductIds,
                        );
                        return $product;
                    });
            }
        }

        return $this->success([
            "products" => ProductResource::collection($products->items()),
            "meta" => [
                "current_page" => $products->currentPage(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}/variants",
     *     summary="Get product variants",
     *     description="Get all variants of a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
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
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="product_name", type="string", example="Organic Fertilizer"),
     *                 @OA\Property(
     *                     property="variants",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/ProductVariant")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function variants(Request $request, string $id): JsonResponse
    {
        $product = Product::with([
            "inventory.productVariants.variant.color",
            "inventory.productVariants.variant.size",
            "inventory.productVariants.variant.planter"
        ])->find($id);

        if (!$product) {
            return $this->notFound("Product not found");
        }

        $variants = $product->inventory?->productVariants ?? collect();

        // Check wishlist status if user is authenticated
        if ($request->user()) {
            $wishlist = Wishlist::where(
                "user_id",
                $request->user()->id,
            )->first();
            if ($wishlist) {
                $wishlistVariantIds = $wishlist
                    ->items()
                    ->pluck("product_variant_id")
                    ->toArray();
                $variants->transform(function ($variant) use (
                    $wishlistVariantIds,
                ) {
                    $variant->in_wishlist = in_array(
                        $variant->id,
                        $wishlistVariantIds,
                    );
                    return $variant;
                });
            }
        }

        return $this->success([
            "product_id" => $product->id,
            "product_name" => $product->name,
            "variants" => \App\Http\Resources\Api\V1\ProductVariantResource::collection(
                $variants,
            ),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/featured",
     *     summary="Get featured products",
     *     description="Get list of featured/recommended products",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of products to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=20)
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
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min($request->input("limit", 10), 20);

        $products = Product::with([
            "productCategory",
            "inventory.productVariants.variant.color",
            "inventory.productVariants.variant.size",
            "inventory.productVariants.variant.planter"
        ])
            ->where("is_active", true)
            ->whereHas("inventory.productVariants", function ($q) {
                $q->where("is_instock", true);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        // Check wishlist status if user is authenticated
        if ($request->user()) {
            $wishlist = Wishlist::where(
                "user_id",
                $request->user()->id,
            )->first();
            if ($wishlist) {
                $wishlistProductIds = $wishlist
                    ->items()
                    ->pluck("product_id")
                    ->toArray();
                $products->transform(function ($product) use (
                    $wishlistProductIds,
                ) {
                    $product->in_wishlist = in_array(
                        $product->id,
                        $wishlistProductIds,
                    );
                    return $product;
                });
            }
        }

        return $this->success([
            "products" => ProductResource::collection($products),
        ]);
    }
}
