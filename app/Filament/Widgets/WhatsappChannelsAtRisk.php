<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappChannel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WhatsappChannelsAtRisk extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.whatsapp_channels_at_risk_table'))
            ->query(
                fn (): Builder => WhatsappChannel::query()
                    ->where('solo_demo', false)
                    ->where(fn ($query) => $query
                        ->whereIn('calidad', ['red', 'yellow'])
                        ->orWhereIn('estado', ['disabled', 'pending_verification']))
            )
            ->columns([
                TextColumn::make('numero_visible')
                    ->label(__('whatsapp_channels.fields.numero_visible')),
                TextColumn::make('departamento')
                    ->label(__('whatsapp_channels.fields.departamento'))
                    ->badge(),
                TextColumn::make('estado')
                    ->label(__('whatsapp_channels.fields.estado'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'disabled' => 'danger',
                        'pending_verification' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => __("whatsapp_channels.estados.{$state}")),
                TextColumn::make('calidad')
                    ->label(__('whatsapp_channels.fields.calidad'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'red' => 'danger',
                        'yellow' => 'warning',
                        'green' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => __("whatsapp_channels.calidades.{$state}")),
            ])
            ->paginated(false);
    }
}
