<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->unique(table: 'product_categories', column: 'slug')
                    ->readOnly()
                    ->required(),
                SpatieMediaLibraryFileUpload::make('image')->collection('images')->required(),
            ]);
    }
}
