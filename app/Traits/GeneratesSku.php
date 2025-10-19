<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

trait GeneratesSku
{
    protected static function bootGeneratesSku()
    {
        static::creating(function ($model): void {
            if (! $model->sku) {
                $model->sku = static::generateSku($model);
            }
        });
    }

    protected static function generateSku($model): string
    {
        $prefix = static::skuPrefix($model);

        $lastRecord = static::withoutGlobalScopes()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $lastNumber = 0;

        if ($lastRecord && preg_match('/(\d+)$/', (string) $lastRecord->sku, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = mb_str_pad((string) ($lastNumber + 1), static::skuPadding(), '0', STR_PAD_LEFT);

        return $prefix.$nextNumber;
    }

    protected static function skuPrefix($model = null): string
    {
        return mb_strtoupper(Str::substr(class_basename(static::class), 0, 3)).'-';
    }

    protected static function skuPadding(): int
    {
        return 4;
    }
}
