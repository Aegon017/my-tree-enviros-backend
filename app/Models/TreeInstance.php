<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreeStatusEnum;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TreeInstance extends Model
{
    use GeneratesSku;

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'status' => TreeStatusEnum::class,
    ];

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TreeStatusLog::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(TreeMedia::class);
    }

    protected static function skuPrefix($model = null): string
    {
        return $model && $model->tree ? $model->tree->sku.'-' : 'TRI-';
    }

    protected static function skuPadding(): int
    {
        return 4;
    }

    #[Scope]
    protected function available($query)
    {
        return $query->where('status', 'available');
    }
}
