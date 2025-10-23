<?php

namespace App\Filament\Resources\BlogCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Set $set, ?string $state): mixed => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->readOnly()
                    ->required(),
            ]);
    }
}
