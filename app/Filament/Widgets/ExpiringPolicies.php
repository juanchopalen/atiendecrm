<?php

namespace App\Filament\Widgets;

use App\Models\Policy;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiringPolicies extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.expiring_policies'))
            ->query(
                fn (): Builder => Policy::query()
                    ->where('status', 'active')
                    ->whereBetween('expiration_date', [now(), now()->addDays(30)])
            )
            ->columns([
                TextColumn::make('client.name')
                    ->label(__('policies.fields.client_id')),
                TextColumn::make('policy_number')
                    ->label(__('policies.fields.policy_number')),
                TextColumn::make('insurer')
                    ->label(__('policies.fields.insurer')),
                TextColumn::make('expiration_date')
                    ->label(__('policies.fields.expiration_date'))
                    ->date()
                    ->badge()
                    ->color('warning')
                    ->sortable(),
            ])
            ->defaultSort('expiration_date')
            ->paginated(false);
    }
}
