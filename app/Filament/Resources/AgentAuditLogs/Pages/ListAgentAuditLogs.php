<?php

namespace App\Filament\Resources\AgentAuditLogs\Pages;

use App\Filament\Resources\AgentAuditLogs\AgentAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAgentAuditLogs extends ListRecords
{
    protected static string $resource = AgentAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
