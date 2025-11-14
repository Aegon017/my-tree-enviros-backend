<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\LocationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([LocationObserver::class])]
final class Location extends Model
{
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function allDescendants()
    {
        return $this->children()
            ->with('allDescendants')
            ->get()
            ->flatMap(
                fn($child) => collect([$child])->merge(
                    $child->allDescendants(),
                ),
            );
    }

    public function allAncestors()
    {
        $ancestors = collect();
        $parent = $this->parent()->select('id', 'parent_id')->first();

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent()->select('id', 'parent_id')->first();
        }

        return $ancestors;
    }

    public function depth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            ++$depth;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function treeInstances(): HasMany
    {
        return $this->hasMany(TreeInstance::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }
}
