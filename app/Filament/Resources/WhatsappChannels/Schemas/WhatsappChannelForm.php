<?php

namespace App\Filament\Resources\WhatsappChannels\Schemas;

use App\Models\WhatsappBusinessAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WhatsappChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('whatsapp_business_account_id')
                    ->label(__('whatsapp_channels.fields.whatsapp_business_account_id'))
                    ->options(fn () => WhatsappBusinessAccount::query()
                        ->where('tenant_id', Filament::getTenant()?->id)
                        ->pluck('waba_id', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('phone_number_id')
                    ->label(__('whatsapp_channels.fields.phone_number_id'))
                    ->helperText(__('whatsapp_channels.helpers.phone_number_id'))
                    ->required(),
                TextInput::make('numero_visible')
                    ->label(__('whatsapp_channels.fields.numero_visible'))
                    ->required(),
                TextInput::make('departamento')
                    ->label(__('whatsapp_channels.fields.departamento'))
                    ->helperText(__('whatsapp_channels.helpers.departamento'))
                    ->required(),
                Select::make('modo')
                    ->label(__('whatsapp_channels.fields.modo'))
                    ->options([
                        'dedicated' => __('whatsapp_channels.modos.dedicated'),
                        'coexistence' => __('whatsapp_channels.modos.coexistence'),
                    ])
                    ->required(),
                Select::make('estado')
                    ->label(__('whatsapp_channels.fields.estado'))
                    ->options([
                        'active' => __('whatsapp_channels.estados.active'),
                        'pending_verification' => __('whatsapp_channels.estados.pending_verification'),
                        'disabled' => __('whatsapp_channels.estados.disabled'),
                    ])
                    ->required(),
                Select::make('calidad')
                    ->label(__('whatsapp_channels.fields.calidad'))
                    ->options([
                        'green' => __('whatsapp_channels.calidades.green'),
                        'yellow' => __('whatsapp_channels.calidades.yellow'),
                        'red' => __('whatsapp_channels.calidades.red'),
                        'unknown' => __('whatsapp_channels.calidades.unknown'),
                    ])
                    ->required(),
                Toggle::make('solo_demo')
                    ->label(__('whatsapp_channels.fields.solo_demo'))
                    ->helperText(__('whatsapp_channels.helpers.solo_demo')),
            ]);
    }
}
