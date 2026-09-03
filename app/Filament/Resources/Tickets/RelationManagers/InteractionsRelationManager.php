<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('interactions.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('channel')
                    ->label(__('interactions.fields.channel'))
                    ->options(fn () => __('interactions.channels'))
                    ->required(),
                Textarea::make('message')
                    ->label(__('interactions.fields.message'))
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                TextColumn::make('channel')
                    ->label(__('interactions.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("interactions.channels.{$state}")),
                TextColumn::make('user.name')
                    ->label(__('interactions.fields.user_id')),
                TextColumn::make('message')
                    ->label(__('interactions.fields.message'))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('interactions.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('interactions.empty_state'))
            ->emptyStateDescription(__('interactions.empty_state_description'))
            ->headerActions([
                CreateAction::make()
                    ->label(__('filament-actions::create.single.label', ['label' => __('interactions.label')]))
                    ->modalHeading(__('filament-actions::create.single.modal.heading', ['label' => __('interactions.label')]))
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] ??= auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
