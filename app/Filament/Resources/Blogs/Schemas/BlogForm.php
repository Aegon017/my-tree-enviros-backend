<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\BlogCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->schema([
                    Section::make('Details')->schema([
                        Flex::make([
                            Select::make('blog_category_id')
                                ->label('Category')
                                ->options(BlogCategory::query()->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->preload()
                                ->columns(8)
                                ->required(),
                        ]),
                        Grid::make()
                            ->columns(1)
                            ->schema([
                                TextInput::make('title')->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state): mixed => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->readOnly()
                                    ->required(),
                            ]),
                        Textarea::make('short_description')->required(),
                        RichEditor::make('description')->required(),
                    ])->columnSpan(8),
                    Section::make('Media')->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnail')->collection('thumbnails')->required(),
                        SpatieMediaLibraryFileUpload::make('image')->collection('images')->required(),
                    ])->columnSpan(4),

                ])->columns(12)->columnSpanFull(),
            ]);
    }
}
