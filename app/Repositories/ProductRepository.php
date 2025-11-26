<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository
{
    public function baseQuery(): Builder
    {
        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.short_description',
                'products.product_category_id',
                'products.created_at',
                'products.selling_price',
                'products.original_price',
            ])
            ->where('products.is_active', true)
            ->with([
                'productCategory:id,name,slug',
                'inventory:id,product_id',
                'inventory.media',
                'inventory.productVariants:id,inventory_id,variant_id,sku,original_price,selling_price,stock_quantity,is_instock',
                'inventory.productVariants.variant:id,color_id,size_id,planter_id',
                'inventory.productVariants.variant.color:id,name,code',
                'inventory.productVariants.variant.size:id,name',
                'inventory.productVariants.variant.planter:id,name',
            ]);
    }

    public function find(string $id)
    {
        return $this->baseQuery()->find($id);
    }
}
