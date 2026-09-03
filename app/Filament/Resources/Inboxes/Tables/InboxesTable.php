<?php

namespace App\Filament\Resources\Inboxes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InboxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_visible')
                    ->label(__('inboxes.fields.nombre_visible'))
                    ->searchable(),
                TextColumn::make('whatsappChannel.numero_visible')
                    ->label(__('inboxes.fields.numero'))
                    ->placeholder(__('inboxes.shared_number')),
                TextColumn::make('whatsappChannel.departamento')
                    ->label(__('inboxes.fields.departamento'))
                    ->badge()
                    ->placeholder(__('inboxes.shared_number')),
                TextColumn::make('agentes_count')
                    ->label(__('inboxes.fields.agentes'))
                    ->counts('agentes'),
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
