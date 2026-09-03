<?php

namespace App\Filament\Resources\WhatsappBusinessAccounts\Pages;

use App\Filament\Resources\WhatsappBusinessAccounts\WhatsappBusinessAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappBusinessAccounts extends ListRecords
{
    protected static string $resource = WhatsappBusinessAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
