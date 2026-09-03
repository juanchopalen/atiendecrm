<?php

namespace App\Filament\Resources\WhatsappChannels\Pages;

use App\Filament\Resources\WhatsappChannels\WhatsappChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappChannel extends EditRecord
{
    protected static string $resource = WhatsappChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
