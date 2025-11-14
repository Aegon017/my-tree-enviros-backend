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
        'status' => TreeStatusEnum::class,
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }

    public function geotags()
    {
        return $this->hasMany(TreeInstanceGeotag::class);
    }

    public function conditionUpdates()
    {
        return $this->hasMany(TreeConditionUpdate::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history()
    {
        return $this->hasMany(TreeOwnershipHistory::class);
    }
}
