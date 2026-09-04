<?php

namespace App\Filament\Resources\AgentAuditLogs;

use App\Filament\Resources\AgentAuditLogs\Pages\ListAgentAuditLogs;
use App\Filament\Resources\AgentAuditLogs\Pages\ViewAgentAuditLog;
use App\Filament\Resources\AgentAuditLogs\Tables\AgentAuditLogsTable;
use App\Models\AgentAuditLog;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Recurso de solo lectura: estos registros los genera AgentOrchestrator, no
 * tienen alta ni edición manual desde el panel.
 */
class AgentAuditLogResource extends Resource
{
    protected static ?string $model = AgentAuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return __('agent_audit_logs.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agent_audit_logs.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('agent_audit_logs.navigation_label');
    }

    public static function table(Table $table): Table
    {
        return AgentAuditLogsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')
                ->label(__('agent_audit_logs.fields.created_at'))
                ->dateTime(),
            TextEntry::make('canal')
                ->label(__('agent_audit_logs.fields.canal')),
            TextEntry::make('telefono')
                ->label(__('agent_audit_logs.fields.telefono')),
            TextEntry::make('client.name')
                ->label(__('agent_audit_logs.fields.client_id'))
                ->default('—'),
            TextEntry::make('mensaje')
                ->label(__('agent_audit_logs.fields.mensaje'))
                ->columnSpanFull(),
            TextEntry::make('tipo_intencion')
                ->label(__('agent_audit_logs.fields.tipo_intencion'))
                ->default('—'),
            TextEntry::make('confianza')
                ->label(__('agent_audit_logs.fields.confianza'))
                ->default('—'),
            TextEntry::make('fuente')
                ->label(__('agent_audit_logs.fields.fuente'))
                ->default('—'),
            IconEntry::make('requiere_seguimiento_humano')
                ->label(__('agent_audit_logs.fields.requiere_seguimiento_humano'))
                ->boolean(),
            KeyValueEntry::make('tool_calls_resumen')
                ->label(__('agent_audit_logs.fields.tool_calls'))
                ->state(fn (AgentAuditLog $record): array => collect($record->tool_calls ?? [])
                    ->mapWithKeys(fn (array $call, int $i) => [
                        ($i + 1).'. '.($call['nombre'] ?? '?') => json_encode($call['resultado'] ?? [], JSON_UNESCAPED_UNICODE),
                    ])
                    ->all())
                ->columnSpanFull(),
            TextEntry::make('error')
                ->label(__('agent_audit_logs.fields.error'))
                ->color('danger')
                ->visible(fn (AgentAuditLog $record): bool => filled($record->error))
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentAuditLogs::route('/'),
            'view' => ViewAgentAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
