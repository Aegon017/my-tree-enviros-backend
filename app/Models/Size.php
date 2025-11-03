<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SizeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([SizeObserver::class])]
final class Size extends Model
{
    protected $fillable = ['name'];

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
