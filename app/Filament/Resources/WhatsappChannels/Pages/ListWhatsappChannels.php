<?php

namespace App\Filament\Resources\WhatsappChannels\Pages;

use App\Filament\Resources\WhatsappChannels\WhatsappChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappChannels extends ListRecords
{
    protected static string $resource = WhatsappChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
