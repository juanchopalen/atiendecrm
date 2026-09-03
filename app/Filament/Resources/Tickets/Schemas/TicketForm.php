<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label(__('tickets.fields.client_id'))
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('policy_id')
                    ->label(__('tickets.fields.policy_id'))
                    ->relationship(
                        'policy',
                        'policy_number',
                        fn (Builder $query, $get) => $query->where('client_id', $get('client_id')),
                    )
                    ->searchable()
                    ->preload(),
                Select::make('agent_id')
                    ->label(__('tickets.fields.agent_id'))
                    ->relationship(
                        'agent',
                        'name',
                        fn (Builder $query) => $query->where('tenant_id', Filament::getTenant()?->id),
                    )
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label(__('tickets.fields.type'))
                    ->options(fn () => __('tickets.types'))
                    ->required(),
                TextInput::make('subject')
                    ->label(__('tickets.fields.subject'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('tickets.fields.description'))
                    ->columnSpanFull(),
                Select::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->options(fn () => __('tickets.priorities'))
                    ->default('medium')
                    ->required(),
                Select::make('status')
                    ->label(__('tickets.fields.status'))
                    ->options(fn () => __('tickets.statuses'))
                    ->default('open')
                    ->required()
                    ->live(),
                DateTimePicker::make('closed_at')
                    ->label(__('tickets.fields.closed_at'))
                    ->visible(fn ($get) => $get('status') === 'closed'),
                SpatieMediaLibraryFileUpload::make('attachments')
                    ->label(__('tickets.fields.attachments'))
                    ->collection('attachments')
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }
}
