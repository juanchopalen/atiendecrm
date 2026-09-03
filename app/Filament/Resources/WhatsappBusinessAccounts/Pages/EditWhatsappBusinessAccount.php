<?php

namespace App\Filament\Resources\WhatsappBusinessAccounts\Pages;

use App\Filament\Resources\WhatsappBusinessAccounts\WhatsappBusinessAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappBusinessAccount extends EditRecord
{
    protected static string $resource = WhatsappBusinessAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
