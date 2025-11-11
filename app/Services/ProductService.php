<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Filters\ProductFilters\{
    SearchFilter,
    CategoryFilter,
    InStockFilter,
    SortFilter
};
use App\Http\Resources\Api\V1\{ProductCollection, ProductResource, ProductVariantResource};
use App\Models\Product;
use App\Models\Wishlist;

class ProductService
{
    public function __construct(private readonly ProductRepository $repo) {}

    public function paginate($request)
    {
        $query = $this->repo->baseQuery();

        foreach (
            [
                'search' => SearchFilter::class,
                'category_id' => CategoryFilter::class,
                'in_stock' => InStockFilter::class,
            ] as $key => $filter
        ) {
            if ($request->filled($key)) $filter::apply($query, $request->$key);
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        SortFilter::apply($query, $sortBy, $sortOrder);

        $products = $query->paginate(min((int)$request->get('per_page', 15), 50));
        $this->wishlist($request->user(), $products->getCollection());

        return new ProductCollection($products);
    }

    public function find($request, $id)
    {
        $product = $this->repo->find($id);
        if (! $product) return null;
        $this->wishlist($request->user(), collect([$product]));
        return $product;
    }

    public function variants($request, $id)
    {
        $product = $this->repo->find($id);
        if (! $product) return null;

        $variants = $product->inventory?->productVariants ?? collect();
        $this->wishlistVariants($request->user(), $variants);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variants' => ProductVariantResource::collection($variants)
        ];
    }

    public function byCategory($request, $categoryId)
    {
        $products = $this->repo->baseQuery()
            ->where('product_category_id', $categoryId)
            ->paginate(min($request->get('per_page', 15), 50));

        $this->wishlist($request->user(), $products->getCollection());

        return [
            'products' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total()
            ],
        ];
    }

    public function featured($request)
    {
        $products = $this->repo->baseQuery()
            ->whereHas('inventory.productVariants', fn($q) => $q->where('is_instock', true))
            ->inRandomOrder()
            ->limit(min($request->get('limit', 10), 20))
            ->get();

        $this->wishlist($request->user(), $products);
        return new ProductCollection($products);
    }

    private function wishlist($user, $products)
    {
        if (! $user || $products->isEmpty()) return;

        $ids = Wishlist::where('user_id', $user->id)
            ->join('wishlist_items', 'wishlists.id', '=', 'wishlist_items.wishlist_id')
            ->pluck('wishlist_items.product_id')
            ->toArray();

        $products->each(fn($p) => $p->in_wishlist = in_array($p->id, $ids));
    }

    private function wishlistVariants($user, $variants)
    {
        if (! $user || $variants->isEmpty()) return;
        $ids = Wishlist::where('user_id', $user->id)->join('wishlist_items', 'wishlists.id', '=', 'wishlist_items.wishlist_id')->pluck('wishlist_items.product_variant_id')->toArray();
        $variants->each(fn($v) => $v->in_wishlist = in_array($v->id, $ids));
    }

    public function findByIdOrSlug(string $identifier)
    {
        return Product::query()
            ->when(is_numeric($identifier), fn($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();
    }
}
