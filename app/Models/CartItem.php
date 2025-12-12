<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

final class CartItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function treeInstance(): BelongsTo
    {
        return $this->belongsTo(TreeInstance::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function initiativeSite(): BelongsTo
    {
        return $this->belongsTo(InitiativeSite::class);
    }

    public function dedication(): MorphOne
    {
        return $this->morphOne(TreeDedication::class, 'dedicatable');
    }
}
