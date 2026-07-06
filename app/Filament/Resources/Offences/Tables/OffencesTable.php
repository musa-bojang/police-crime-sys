<?php

namespace App\Filament\Resources\Offences\Tables;

use App\Enums\OffenceStatus;
use App\Models\AuditLog;
use App\Models\Offence;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OffencesTable
{
    public static function configure(Table $table): Table
    {
       return $table
    ->columns([
        TextColumn::make('reference_number')->label('Reference')->searchable()->sortable(),
        TextColumn::make('offence_type')->label('Offence')->badge()->searchable(),
        TextColumn::make('vehicle_plate')->label('Plate')->searchable(),
        TextColumn::make('officer.name')->label('Officer')->searchable(),
        TextColumn::make('status')
            ->badge()
            ->color(fn ($state) => match ($state instanceof OffenceStatus ? $state->value : $state) {
                'confirmed'    => 'success',
                'dismissed'    => 'danger',
                'under_review' => 'warning',
                default        => 'gray',
            }),
        TextColumn::make('occurred_at')->label('Occurred')->dateTime()->sortable(),
    ])
    ->defaultSort('occurred_at', 'desc')
    ->filters([
        SelectFilter::make('status')->options(
            collect(OffenceStatus::cases())
                ->mapWithKeys(fn ($c) => [$c->value => ucfirst(str_replace('_', ' ', $c->value))])
                ->all()
        ),
    ])
    ->recordActions([
        ViewAction::make(),

        Action::make('confirm')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirm this offence?')
            ->visible(fn (Offence $record) => in_array(
                $record->status, [OffenceStatus::Submitted, OffenceStatus::UnderReview], true
            ))
            ->action(function (Offence $record) {
                $record->forceFill([
                    'status'      => OffenceStatus::Confirmed,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'version'     => $record->version + 1,
                ])->save();

                AuditLog::record('offence.confirmed', $record);
                Notification::make()->title('Offence confirmed')->success()->send();
            }),

        Action::make('dismiss')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Dismiss this offence?')
            ->visible(fn (Offence $record) => in_array(
                $record->status, [OffenceStatus::Submitted, OffenceStatus::UnderReview], true
            ))
            ->action(function (Offence $record) {
                $record->forceFill([
                    'status'      => OffenceStatus::Dismissed,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'version'     => $record->version + 1,
                ])->save();

                AuditLog::record('offence.dismissed', $record);
                Notification::make()->title('Offence dismissed')->send();
            }),
    ]);
    }
}
