<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy([ActiveScope::class])]
final class PaymentGateway extends Model implements HasMedia
{
    use HasSlug;
    use InteractsWithMedia;

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    private string $slugFrom = 'name';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
    }
}
