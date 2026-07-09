<?php

namespace App\Filament\Resources\WatchlistVehicles\Pages;

use App\Filament\Resources\WatchlistVehicles\WatchlistVehicleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWatchlistVehicle extends CreateRecord
{
    protected static string $resource = WatchlistVehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['created_by'] = auth()->id();
    return $data;
}

    }
