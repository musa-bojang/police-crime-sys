<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

        protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->id === auth()->id() && ($data['is_active'] ?? true) == false) {
            $data['is_active'] = true;

            \Filament\Notifications\Notification::make()
                ->title('You cannot deactivate your own account')
                ->warning()
                ->send();
        }

        return $data;
    }
}
