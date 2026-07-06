<?php

namespace App\Filament\Resources\Offences;

use App\Filament\Resources\Offences\Pages\CreateOffence;
use App\Filament\Resources\Offences\Pages\EditOffence;
use App\Filament\Resources\Offences\Pages\ListOffences;
use App\Filament\Resources\Offences\Pages\ViewOffence;
use App\Filament\Resources\Offences\Schemas\OffenceForm;
use App\Filament\Resources\Offences\Schemas\OffenceInfolist;
use App\Filament\Resources\Offences\Tables\OffencesTable;
use App\Models\Offence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class OffenceResource extends Resource
{
    protected static ?string $model = Offence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return OffenceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OffenceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffencesTable::configure($table);
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
            'index' => ListOffences::route('/'),
            'create' => CreateOffence::route('/create'),
            'view' => ViewOffence::route('/{record}'),
            'edit' => EditOffence::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // key restrictions: only officers can create, and only supervisors/admins can edit or delete
    public static function canCreate(): bool
        {
            return false;
        }

        public static function canEdit(Model $record): bool
        {
            return false;
        }

        public static function canDelete(Model $record): bool
        {
            return false;
        }
}
