<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ColorObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ColorObserver::class])]
final class Color extends Model
{
    protected $fillable = ['name', 'code'];

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
