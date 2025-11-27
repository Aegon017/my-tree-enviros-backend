<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreeStatusEnum;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class TreeInstance extends Model
{
    use GeneratesSku;

    protected $casts = [
        'status' => TreeStatusEnum::class,
        'planted_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function treeUpdates(): HasMany
    {
        return $this->hasMany(TreeUpdate::class);
    }

    public function sponsorRecords(): HasMany
    {
        return $this->hasMany(SponsorRecord::class);
    }

    public function adoptRecords(): HasMany
    {
        return $this->hasMany(AdoptRecord::class);
    }

    protected static function skuPrefix($model = null): string
    {
        $treeShort = $model->tree?->short_code ?? Str::upper(Str::substr($model->tree?->name ?? 'TREE', 0, 3));
        $locationShort = $model->location?->short_code ?? Str::upper(Str::substr($model->location?->name ?? 'LOC', 0, 3));

        return sprintf('TREE-%s-%s-', $treeShort, $locationShort);
    }

    protected static function skuPadding(): int
    {
        return 4;
    }
}
