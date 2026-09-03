<?php

namespace App\Filament\Resources\WhatsappBusinessAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WhatsappBusinessAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('waba_id')
                    ->label(__('whatsapp_business_accounts.fields.waba_id'))
                    ->helperText(__('whatsapp_business_accounts.helpers.waba_id'))
                    ->required(),
                Select::make('business_verification_status')
                    ->label(__('whatsapp_business_accounts.fields.business_verification_status'))
                    ->options([
                        'pending' => __('whatsapp_business_accounts.statuses.pending'),
                        'verified' => __('whatsapp_business_accounts.statuses.verified'),
                    ])
                    ->required(),
                TextInput::make('access_token')
                    ->label(__('whatsapp_business_accounts.fields.access_token'))
                    ->helperText(__('whatsapp_business_accounts.helpers.access_token'))
                    ->password()
                    ->revealable(),
            ]);
    }
}
