<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserTypeEnum;
use Filament\Models\Contracts\FilamentUser as FilamentUserContract;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MichaelRubel\Couponables\Traits\HasCoupons;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements FilamentUserContract, HasMedia
{
    use HasApiTokens;
    use HasCoupons;
    use HasFactory;
    use HasOneTimePasswords;
    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'country_code',
        'phone',
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function routeNotificationForSmsLogin(): string
    {
        return $this->phone;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@mytree.care');
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(ShippingAddress::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')->singleFile();
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    protected function casts(): array
    {
        return [
            'type' => UserTypeEnum::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
