<?php

declare(strict_types=1);

namespace App\Filament\Resources\Campaigns;

use App\Enums\CampaignTypeEnum;
use App\Filament\Resources\Campaigns\CampaignResource\Pages;
use App\Filament\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Schemas\Schema;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = "Campaigns";

    protected static ?string $modelLabel = "Campaign";

    protected static ?string $pluralModelLabel = "Campaigns";

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::schema($table);
    }

    public static function getRelations(): array
    {
        return [
                // Add RelationManagers here as needed
            ];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListCampaigns::route("/"),
            "create" => Pages\CreateCampaign::route("/create"),
            "edit" => Pages\EditCampaign::route("/{record}/edit"),
        ];
    }
}
