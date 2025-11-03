<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Blog extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'short_description',
        'description',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')->singleFile();
        $this->addMediaCollection('images')->singleFile();
    }

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }
}
