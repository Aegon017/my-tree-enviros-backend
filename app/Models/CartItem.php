<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'tree_instance_id',
        'tree_plan_price_id',
        'quantity',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function treeInstance()
    {
        return $this->belongsTo(TreeInstance::class);
    }

    public function treePlanPrice()
    {
        return $this->belongsTo(TreePlanPrice::class);
    }

    public function isProduct()
    {
        return ! is_null($this->product_variant_id);
    }

    public function isTree()
    {
        return ! is_null($this->tree_instance_id);
    }
}