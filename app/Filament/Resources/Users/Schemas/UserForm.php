<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;


class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('service_number')
                    ->label('Service number')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('rank'),
                TextInput::make('station'),
                TextInput::make('email')->email()->unique(ignoreRecord: true),
                TextInput::make('phone')->tel(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Leave blank when editing to keep the current password.'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
                Toggle::make('is_active')->label('Active')->default(true),
            ]);
    }
}
