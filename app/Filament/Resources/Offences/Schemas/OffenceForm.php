<?php

namespace App\Filament\Resources\Offences\Schemas;

use App\Enums\OffenceStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OffenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference_number'),
                Select::make('officer_id')
                    ->relationship('officer', 'name')
                    ->required(),
                TextInput::make('device_id'),
                TextInput::make('offence_type')
                    ->required(),
                Textarea::make('offence_description')
                    ->columnSpanFull(),
                TextInput::make('vehicle_plate'),
                TextInput::make('vehicle_color'),
                TextInput::make('vehicle_make'),
                TextInput::make('vehicle_type'),
                TextInput::make('driver_gender'),
                TextInput::make('driver_name'),
                Toggle::make('driver_fled')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('location_description'),
                DateTimePicker::make('occurred_at')
                    ->required(),
                DateTimePicker::make('captured_at'),
                DateTimePicker::make('synced_at'),
                Select::make('status')
                    ->options(OffenceStatus::class)
                    ->default('submitted')
                    ->required(),
                TextInput::make('reviewed_by')
                    ->numeric(),
                DateTimePicker::make('reviewed_at'),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
