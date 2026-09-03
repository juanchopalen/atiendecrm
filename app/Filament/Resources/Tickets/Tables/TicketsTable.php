<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label(__('tickets.fields.client_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('policy.policy_number')
                    ->label(__('tickets.fields.policy_id'))
                    ->searchable(),
                TextColumn::make('agent.name')
                    ->label(__('tickets.fields.agent_id'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('tickets.fields.type'))
                    ->formatStateUsing(fn (string $state) => __("tickets.types.{$state}"))
                    ->searchable(),
                TextColumn::make('subject')
                    ->label(__('tickets.fields.subject'))
                    ->searchable(),
                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("tickets.priorities.{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                    }),
                TextColumn::make('status')
                    ->label(__('tickets.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("tickets.statuses.{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        'open' => 'info',
                        'in_progress' => 'warning',
                        'waiting_client' => 'gray',
                        'closed' => 'success',
                    }),
                TextColumn::make('closed_at')
                    ->label(__('tickets.fields.closed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tickets.fields.status'))
                    ->options(fn () => __('tickets.statuses')),
                SelectFilter::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->options(fn () => __('tickets.priorities')),
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
