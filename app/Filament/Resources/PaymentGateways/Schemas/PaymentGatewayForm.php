<?php

namespace App\Filament\Resources\PaymentGateways\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                Textarea::make('description')->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('image')->collection('images'),
                Toggle::make('is_active')->default(true),
                Hidden::make('sort')->default(0),
            ]);
    }
}
