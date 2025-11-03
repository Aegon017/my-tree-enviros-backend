<?php

namespace App\Models;

use App\Observers\PlanterObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


#[ObservedBy([PlanterObserver::class])]
class Planter extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
