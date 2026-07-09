<?php

namespace App\Filament\Resources\WatchlistVehicles\Pages;

use App\Filament\Resources\WatchlistVehicles\WatchlistVehicleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWatchlistVehicle extends EditRecord
{
    protected static string $resource = WatchlistVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
