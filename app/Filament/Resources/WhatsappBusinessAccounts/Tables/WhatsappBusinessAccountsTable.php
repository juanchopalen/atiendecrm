<?php

namespace App\Filament\Resources\WhatsappBusinessAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsappBusinessAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('waba_id')
                    ->label(__('whatsapp_business_accounts.fields.waba_id'))
                    ->searchable(),
                BadgeColumn::make('business_verification_status')
                    ->label(__('whatsapp_business_accounts.fields.business_verification_status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                    ]),
                TextColumn::make('channels_count')
                    ->label(__('whatsapp_business_accounts.fields.channels_count'))
                    ->counts('channels'),
                TextColumn::make('created_at')
                    ->label(__('whatsapp_business_accounts.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
