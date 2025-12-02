<?php

declare(strict_types=1);

namespace App\Services;

use App\Filters\ProductFilters\CategoryFilter;
use App\Filters\ProductFilters\InStockFilter;
use App\Filters\ProductFilters\SearchFilter;
use App\Filters\ProductFilters\SortFilter;
use App\Http\Resources\Api\V1\ProductCollection;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\ProductVariantResource;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

final readonly class ProductService
{
    public function __construct(private ProductRepository $repo) {}

    public function paginate($request): ProductCollection
    {
        $query = $this->repo->baseQuery();

        foreach (
            [
                'search' => SearchFilter::class,
                'category_id' => CategoryFilter::class,
                'in_stock' => InStockFilter::class,
            ] as $key => $filter
        ) {
            if ($request->filled($key)) {
                $filter::apply($query, $request->$key);
            }
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        SortFilter::apply($query, $sortBy, $sortOrder);

        $products = $query->whereHas('inventory.productVariants')->paginate(min((int) $request->get('per_page', 15), 50));
        $this->attachVariantWishlistFlags($request->user(), $products->getCollection());

        return new ProductCollection($products);
    }

    public function variants($request, string $id): ?array
    {
        $product = $this->repo->find($id);
        if (! $product) {
            return null;
        }

        $variants = $product->inventory?->productVariants ?? collect();
        $this->attachVariantWishlistFlags($request->user(), $variants);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variants' => ProductVariantResource::collection($variants),
        ];
    }

    public function byCategory($request, $categoryId): array
    {
        $products = $this->repo->baseQuery()
            ->where('product_category_id', $categoryId)
            ->paginate(min($request->get('per_page', 15), 50));

        $this->attachVariantWishlistFlags($request->user(), $products->getCollection());

        return [
            'products' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ];
    }

    public function featured($request): ProductCollection
    {
        $products = $this->repo->baseQuery()
            ->whereHas('inventory.productVariants', fn ($q) => $q->where('is_instock', true))
            ->inRandomOrder()
            ->limit(min($request->get('limit', 10), 20))
            ->get();

        $this->attachVariantWishlistFlags($request->user(), $products);

        return new ProductCollection($products);
    }

    public function findByIdOrSlugWithWishlist(Request $request, string $identifier)
    {
        $product = Product::query()
            ->with([
                'productCategory:id,name,slug',
                'inventory:id,product_id',
                'inventory.media',
                'inventory.productVariants:id,inventory_id,variant_id,sku,original_price,selling_price,stock_quantity,is_instock',
                'inventory.productVariants.variant:id,color_id,size_id,planter_id',
                'inventory.productVariants.variant.color:id,name,code',
                'inventory.productVariants.variant.size:id,name',
                'inventory.productVariants.variant.planter:id,name',
            ])
            ->when(is_numeric($identifier), fn ($q) => $q->where('id', $identifier))
            ->orWhere('slug', $identifier)
            ->first();

        if (! $product) {
            return null;
        }

        $this->attachVariantWishlistFlags($request->user(), collect([$product]));

        return $product;
    }

    private function attachVariantWishlistFlags(?User $user, $items): void
    {
        if (! $user || $items->isEmpty()) {
            return;
        }

        $variantIds = Wishlist::query()
            ->where('user_id', $user->id)
            ->join('wishlist_items', 'wishlists.id', '=', 'wishlist_items.wishlist_id')
            ->pluck('wishlist_items.product_variant_id')
            ->filter()
            ->toArray();

        foreach ($items as $item) {
            $variants = $item->inventory?->productVariants ?? collect();
            if ($variants->isEmpty() && isset($item->variants)) {
                $variants = collect($item->variants);
            }

            foreach ($variants as $variant) {
                $variant->setAttribute('in_wishlist', in_array($variant->id, $variantIds));
            }

            if (isset($item->default_variant)) {
                $item->default_variant->setAttribute('in_wishlist', in_array($item->default_variant->id, $variantIds));
            }
        }
    }
}
