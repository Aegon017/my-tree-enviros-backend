<?php

declare(strict_types=1);

namespace App\Traits;

trait HasLabelOptions
{
    abstract public function label(): string;

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->toArray();
    }

    public function option(): array
    {
        return [$this->value => $this->label()];
    }
}
