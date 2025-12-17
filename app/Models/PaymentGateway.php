<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy([ActiveScope::class])]
class PaymentGateway extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;

    protected string $slugFrom = 'name';

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
    }
}
