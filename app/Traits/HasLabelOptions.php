<?php

namespace App\Traits;

trait HasLabelOptions
{
    abstract public function label(): string;

    public function option(): array
    {
        return [$this->value => $this->label()];
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
