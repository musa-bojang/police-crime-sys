<?php

namespace App\Filament\Resources\WatchlistVehicles\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class WatchlistVehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('plate')
                ->required()
                ->maxLength(20)
                ->helperText('Any format — it is normalised automatically for matching.'),
                TextInput::make('vehicle_make')->label('Make'),
                TextInput::make('vehicle_color')->label('Colour'),
                Select::make('vehicle_type')->label('Type')->options([
                    'car' => 'Car', 'motorcycle' => 'Motorcycle',
                    'truck' => 'Truck', 'bus' => 'Bus', 'other' => 'Other',
                ]),
                Select::make('severity')
                    ->required()
                    ->default('wanted')
                    ->options(['caution' => 'Caution', 'wanted' => 'Wanted', 'dangerous' => 'Dangerous']),
                Textarea::make('reason')->required()->rows(2),
                Textarea::make('instructions')
                    ->rows(2)
                    ->helperText('What should an officer do on a match? e.g. Do not approach; call dispatch.'),
                            ]);
    }
}
