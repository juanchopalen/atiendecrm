<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('clients.fields.name'))
                    ->required(),
                TextInput::make('national_id')
                    ->label(__('clients.fields.national_id')),
                TextInput::make('phone')
                    ->label(__('clients.fields.phone'))
                    ->tel(),
                TextInput::make('email')
                    ->label(__('clients.fields.email'))
                    ->email(),
                Textarea::make('address')
                    ->label(__('clients.fields.address'))
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('attachments')
                    ->label(__('clients.fields.attachments'))
                    ->collection('attachments')
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }
}
