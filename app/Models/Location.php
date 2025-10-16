<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Location extends Model
{
    protected $casts = [
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
        return $this->children()->with('allDescendants')->get()
            ->flatMap(fn ($child) => collect([$child])->merge($child->allDescendants()));
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

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }
}
