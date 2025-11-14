<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Enums\TreeTypeEnum;
use App\Filament\Resources\Plans\PlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Sponsor' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', TreeTypeEnum::SPONSOR->value)),
            'Adopt' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', TreeTypeEnum::ADOPT->value)),
        ];
    }
}
