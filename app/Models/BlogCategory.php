<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }
}
