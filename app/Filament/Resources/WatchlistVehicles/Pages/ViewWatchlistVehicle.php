<?php

namespace App\Filament\Resources\WatchlistVehicles\Pages;

use App\Filament\Resources\WatchlistVehicles\WatchlistVehicleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWatchlistVehicle extends ViewRecord
{
    protected static string $resource = WatchlistVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
