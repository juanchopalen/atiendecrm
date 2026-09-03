<?php

namespace App\Filament\Resources\Inboxes\Schemas;

use App\Models\User;
use App\Models\WhatsappChannel;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InboxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('whatsapp_channel_id')
                    ->label(__('inboxes.fields.whatsapp_channel_id'))
                    ->options(fn () => WhatsappChannel::query()
                        ->where('tenant_id', Filament::getTenant()?->id)
                        ->get()
                        ->mapWithKeys(fn (WhatsappChannel $channel) => [
                            $channel->id => "{$channel->departamento} — {$channel->numero_visible}",
                        ]))
                    ->required()
                    ->searchable(),
                TextInput::make('nombre_visible')
                    ->label(__('inboxes.fields.nombre_visible'))
                    ->required(),
                Select::make('agentes')
                    ->label(__('inboxes.fields.agentes'))
                    ->relationship('agentes', 'name')
                    ->options(fn () => User::query()
                        ->where('tenant_id', Filament::getTenant()?->id)
                        ->pluck('name', 'id'))
                    ->multiple()
                    ->searchable(),
            ]);
    }
}
