<?php

namespace App\Filament\Resources\WatchlistVehicles\Pages;

use App\Filament\Resources\WatchlistVehicles\WatchlistVehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWatchlistVehicles extends ListRecords
{
    protected static string $resource = WatchlistVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
