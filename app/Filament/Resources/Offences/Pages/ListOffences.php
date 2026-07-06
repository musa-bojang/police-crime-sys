<?php

namespace App\Filament\Resources\Offences\Pages;

use App\Filament\Resources\Offences\OffenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOffences extends ListRecords
{
    protected static string $resource = OffenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
