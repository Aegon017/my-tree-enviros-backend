<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        $typeOptions = collect(CampaignTypeEnum::cases())
            ->mapWithKeys(fn(CampaignTypeEnum $e) => [$e->value => $e->label()])
            ->all();

        return $schema->components([
            Select::make("type")
                ->label("Type")
                ->options($typeOptions)
                ->required()
                ->native(false)
                ->searchable()
                ->preload(),

            Select::make("location_id")
                ->label("Location")
                ->relationship("location", "name")
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make("name")
                ->label("Name")
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(
                    fn(Set $set, ?string $state): mixed => $set(
                        "slug",
                        Str::slug($state ?? ""),
                    ),
                ),

            TextInput::make("slug")
                ->label("Slug")
                ->unique(table: "campaigns", column: "slug")
                ->required(),

            TextInput::make("amount")
                ->label("Suggested Amount (INR)")
                ->numeric()
                ->minValue(1)
                ->step(0.01)
                ->default(500)
                ->required(),

            Toggle::make("is_active")->label("Active")->default(true),

            DatePicker::make("start_date")
                ->label("Start Date")
                ->native(false)
                ->closeOnDateSelection(),

            DatePicker::make("end_date")
                ->label("End Date")
                ->native(false)
                ->closeOnDateSelection(),

            RichEditor::make("description")
                ->label("Description")
                ->toolbarButtons([
                    "bold",
                    "italic",
                    "underline",
                    "strike",
                    "bulletList",
                    "orderedList",
                    "blockquote",
                    "link",
                    "h2",
                    "h3",
                    "codeBlock",
                ]),

            SpatieMediaLibraryFileUpload::make("thumbnail")
                ->label("Thumbnail")
                ->collection("thumbnails")
                ->image(),

            SpatieMediaLibraryFileUpload::make("images")
                ->label("Gallery Images")
                ->collection("images")
                ->image()
                ->multiple()
                ->reorderable(),
        ]);
    }
}
