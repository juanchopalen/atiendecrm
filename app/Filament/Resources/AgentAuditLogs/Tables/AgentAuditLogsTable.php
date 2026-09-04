<?php

namespace App\Filament\Resources\AgentAuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentAuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('agent_audit_logs.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('canal')
                    ->label(__('agent_audit_logs.fields.canal'))
                    ->badge(),
                TextColumn::make('telefono')
                    ->label(__('agent_audit_logs.fields.telefono'))
                    ->searchable(),
                TextColumn::make('client.name')
                    ->label(__('agent_audit_logs.fields.client_id'))
                    ->default('—')
                    ->searchable(),
                TextColumn::make('mensaje')
                    ->label(__('agent_audit_logs.fields.mensaje'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('tipo_intencion')
                    ->label(__('agent_audit_logs.fields.tipo_intencion'))
                    ->badge()
                    ->default('—'),
                TextColumn::make('fuente')
                    ->label(__('agent_audit_logs.fields.fuente'))
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'error' ? 'danger' : 'gray')
                    ->default('—'),
                IconColumn::make('requiere_seguimiento_humano')
                    ->label(__('agent_audit_logs.fields.requiere_seguimiento_humano'))
                    ->boolean(),
                IconColumn::make('tiene_error')
                    ->label(__('agent_audit_logs.fields.tiene_error'))
                    ->state(fn ($record): bool => filled($record->error))
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo_intencion')
                    ->label(__('agent_audit_logs.fields.tipo_intencion'))
                    ->options([
                        'faq' => 'FAQ',
                        'kb_categoria' => 'KB categoría',
                        'consulta_cliente' => 'Consulta cliente',
                        'fuera_de_alcance' => 'Fuera de alcance',
                    ]),
                TernaryFilter::make('requiere_seguimiento_humano')
                    ->label(__('agent_audit_logs.fields.requiere_seguimiento_humano')),
                Filter::make('con_error')
                    ->label(__('agent_audit_logs.filters.con_error'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('error')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
