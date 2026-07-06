<?php

namespace App\Filament\Resources\Offences\Pages;

use App\Filament\Resources\Offences\OffenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOffence extends ViewRecord
{
    protected static string $resource = OffenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
