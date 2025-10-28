<?php

namespace App\Models;

use App\Enums\CampaignTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Campaign extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        "location_id",
        "type",
        "name",
        "slug",
        "description",
        "amount",
        "start_date",
        "end_date",
        "is_active",
    ];

    protected $casts = [
        "type" => CampaignTypeEnum::class,
        "amount" => "decimal:2",
        "start_date" => "date",
        "end_date" => "date",
        "is_active" => "boolean",
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection("thumbnails")->singleFile();
        $this->addMediaCollection("images");
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where("is_active", true);
    }
}
