<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CartItem extends Model
{
    public function tree(){
        return $this->belongsTo(Tree::class);
    }
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

    public function planPrice()
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function dedication(): MorphOne
    {
        return $this->morphOne(TreeDedication::class, 'dedicatable');
    }
}
