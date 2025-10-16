<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TreePlanPrice extends Model
{
    use GeneratesSku;

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreePricePlan::class, 'tree_price_plan_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    private static function skuPrefix($model = null): string
    {
        if ($model && $model->tree && $model->plan) {
            return $model->tree->sku . '-' . $model->plan->sku . '-';
        }

        return 'TPP-';
    }

    private static function skuPadding(): int
    {
        return 4;
    }
}
