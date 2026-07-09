<?php

namespace App\Filament\Resources\WatchlistVehicles;

use App\Filament\Resources\WatchlistVehicles\Pages\CreateWatchlistVehicle;
use App\Filament\Resources\WatchlistVehicles\Pages\EditWatchlistVehicle;
use App\Filament\Resources\WatchlistVehicles\Pages\ListWatchlistVehicles;
use App\Filament\Resources\WatchlistVehicles\Pages\ViewWatchlistVehicle;
use App\Filament\Resources\WatchlistVehicles\Schemas\WatchlistVehicleForm;
use App\Filament\Resources\WatchlistVehicles\Schemas\WatchlistVehicleInfolist;
use App\Filament\Resources\WatchlistVehicles\Tables\WatchlistVehiclesTable;
use App\Models\WatchlistVehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WatchlistVehicleResource extends Resource
{
    protected static ?string $model = WatchlistVehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'plate';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Watchlist';

    public static function form(Schema $schema): Schema
    {
        return WatchlistVehicleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WatchlistVehicleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WatchlistVehiclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWatchlistVehicles::route('/'),
            'create' => CreateWatchlistVehicle::route('/create'),
            'view' => ViewWatchlistVehicle::route('/{record}'),
            'edit' => EditWatchlistVehicle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
            public static function canAccess(): bool
        {
            return auth()->user()?->hasAnyRole(['admin', 'supervisor']) ?? false;
        }
}
