<?php

namespace App\Filament\Resources\WatchlistVehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

use App\Enums\Severity;
use App\Enums\WatchlistStatus;
use App\Models\WatchlistVehicle;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class WatchlistVehiclesTable
{
    public static function configure(Table $table): Table
    {
            return $table
        ->columns([
            TextColumn::make('plate')->searchable()->sortable(),
            TextColumn::make('severity')->badge()->color(fn ($state) =>
                match ($state instanceof Severity ? $state->value : $state) {
                    'dangerous' => 'danger', 'wanted' => 'warning', default => 'gray',
                }),
            TextColumn::make('status')->badge()->color(fn ($state) =>
                ($state instanceof WatchlistStatus ? $state->value : $state) === 'active'
                    ? 'success' : 'gray'),
            TextColumn::make('vehicle_color')->label('Colour')->toggleable(),
            TextColumn::make('reason')->limit(40)->wrap(),
            TextColumn::make('sightings_count')->counts('sightings')->label('Sightings')->badge(),
            TextColumn::make('creator.name')->label('Created by')->toggleable(),
            TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('status')->options(['active' => 'Active', 'cleared' => 'Cleared']),
            SelectFilter::make('severity')->options([
                'caution' => 'Caution', 'wanted' => 'Wanted', 'dangerous' => 'Dangerous',
            ]),
        ])
        ->recordActions([
            ViewAction::make(),
            EditAction::make(),
            Action::make('clear')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Clear this alert?')
                ->visible(fn (WatchlistVehicle $record) =>
                    $record->status === WatchlistStatus::Active)
                ->action(function (WatchlistVehicle $record) {
                    $record->update([
                        'status'     => WatchlistStatus::Cleared,
                        'cleared_by' => auth()->id(),
                        'cleared_at' => now(),
                    ]);
                    AuditLog::record('watchlist.cleared', $record);
                    Notification::make()->title('Alert cleared')->success()->send();
                }),
        ]);
    }
}
