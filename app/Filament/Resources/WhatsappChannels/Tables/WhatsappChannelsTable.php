<?php

namespace App\Filament\Resources\WhatsappChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsappChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_visible')
                    ->label(__('whatsapp_channels.fields.numero_visible'))
                    ->searchable(),
                TextColumn::make('departamento')
                    ->label(__('whatsapp_channels.fields.departamento'))
                    ->badge()
                    ->searchable(),
                BadgeColumn::make('modo')
                    ->label(__('whatsapp_channels.fields.modo'))
                    ->colors([
                        'primary' => 'dedicated',
                        'warning' => 'coexistence',
                    ])
                    ->formatStateUsing(fn (string $state) => __("whatsapp_channels.modos.{$state}")),
                BadgeColumn::make('estado')
                    ->label(__('whatsapp_channels.fields.estado'))
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending_verification',
                        'danger' => 'disabled',
                    ])
                    ->formatStateUsing(fn (string $state) => __("whatsapp_channels.estados.{$state}")),
                BadgeColumn::make('calidad')
                    ->label(__('whatsapp_channels.fields.calidad'))
                    ->colors([
                        'success' => 'green',
                        'warning' => 'yellow',
                        'danger' => 'red',
                        'gray' => 'unknown',
                    ])
                    ->formatStateUsing(fn (string $state) => __("whatsapp_channels.calidades.{$state}")),
                IconColumn::make('solo_demo')
                    ->label(__('whatsapp_channels.fields.solo_demo'))
                    ->boolean(),
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
