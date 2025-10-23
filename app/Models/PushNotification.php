<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PushNotification extends Model implements HasMedia
{
    use InteractsWithMedia;


    protected $fillable = [
        'title',
        'text',
    ];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
