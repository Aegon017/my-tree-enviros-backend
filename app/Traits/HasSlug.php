<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            if (!$model->slug) {
                $model->slug = self::makeUniqueSlug($model);
            }
        });

        static::updating(function (Model $model): void {
            $sourceFields = (array) self::slugSourceFields($model);

            if ($model->isDirty($sourceFields) && !$model->isDirty('slug')) {
                $model->slug = self::makeUniqueSlug($model);
            }
        });
    }

    protected static function slugSourceFields(Model $model): array
    {
        return property_exists($model, 'slugFrom')
            ? (array) $model->slugFrom
            : ['name'];
    }

    protected static function slugSourceValue(Model $model): string
    {
        $fields = self::slugSourceFields($model);

        return collect($fields)
            ->map(fn($field) => (string) ($model->{$field} ?? ''))
            ->implode(' ');
    }

    protected static function makeUniqueSlug(Model $model): string
    {
        $base = Str::slug(self::slugSourceValue($model));
        $slug = $base;
        $i = 1;

        $query = $model->newQuery()
            ->where('slug', $slug)
            ->when($model->exists, fn($q) => $q->where('id', '!=', $model->getKey()));

        while ($query->exists()) {
            $slug = "{$base}-{$i}";
            $i++;

            $query = $model->newQuery()
                ->where('slug', $slug)
                ->when($model->exists, fn($q) => $q->where('id', '!=', $model->getKey()));
        }

        return $slug;
    }
}
