<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ProductVariantObserver;
use App\Traits\GeneratesSku;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ProductVariantObserver::class])]
final class ProductVariant extends Model implements HasMedia
{
    use GeneratesSku;
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    private static function skuPrefix(): string
    {
        return 'PRODVAR-';
    }

    private static function skuPadding(): int
    {
        return 4;
    }
}
