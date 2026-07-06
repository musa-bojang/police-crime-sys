<?php

namespace App\Filament\Resources\Offences\Schemas;

use App\Models\Offence;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OffenceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('reference_number')
                    ->placeholder('-'),
                TextEntry::make('officer.name')
                    ->label('Officer'),
                TextEntry::make('device_id')
                    ->placeholder('-'),
                TextEntry::make('offence_type'),
                TextEntry::make('offence_description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('vehicle_plate')
                    ->placeholder('-'),
                TextEntry::make('vehicle_color')
                    ->placeholder('-'),
                TextEntry::make('vehicle_make')
                    ->placeholder('-'),
                TextEntry::make('vehicle_type')
                    ->placeholder('-'),
                TextEntry::make('driver_gender')
                    ->placeholder('-'),
                TextEntry::make('driver_name')
                    ->placeholder('-'),
                IconEntry::make('driver_fled')
                    ->boolean(),
                TextEntry::make('latitude')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('longitude')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('location_description')
                    ->placeholder('-'),
                TextEntry::make('occurred_at')
                    ->dateTime(),
                TextEntry::make('captured_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('synced_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('reviewed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('version')
                    ->numeric(),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Offence $record): bool => $record->trashed()),
            ]);
    }
}
