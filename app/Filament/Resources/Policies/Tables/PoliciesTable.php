<?php

namespace App\Filament\Resources\Policies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label(__('policies.fields.client_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('policy_number')
                    ->label(__('policies.fields.policy_number'))
                    ->searchable(),
                TextColumn::make('line_of_business')
                    ->label(__('policies.fields.line_of_business'))
                    ->formatStateUsing(fn (string $state) => __("policies.lines_of_business.{$state}"))
                    ->searchable(),
                TextColumn::make('insurer')
                    ->label(__('policies.fields.insurer'))
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label(__('policies.fields.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('expiration_date')
                    ->label(__('policies.fields.expiration_date'))
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->expiration_date->isPast() => 'danger',
                        $record->expiration_date->lte(now()->addDays(30)) => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('status')
                    ->label(__('policies.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("policies.statuses.{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                    }),
                TextColumn::make('premium')
                    ->label(__('policies.fields.premium'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_frequency')
                    ->label(__('policies.fields.payment_frequency'))
                    ->formatStateUsing(fn (string $state) => __("policies.payment_frequencies.{$state}"))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('policies.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('policies.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('policies.fields.status'))
                    ->options(fn () => __('policies.statuses')),
                Filter::make('expiring_soon')
                    ->label(__('policies.filters.expiring_soon'))
                    ->query(fn (Builder $query) => $query->whereBetween('expiration_date', [now(), now()->addDays(30)])),
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
