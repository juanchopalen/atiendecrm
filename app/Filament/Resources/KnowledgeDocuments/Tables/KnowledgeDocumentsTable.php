<?php

namespace App\Filament\Resources\KnowledgeDocuments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->label(__('knowledge_documents.fields.tipo'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __("knowledge_documents.tipos.{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        'faq' => 'success',
                        'articulo_kb' => 'info',
                    }),
                TextColumn::make('categoria')
                    ->label(__('knowledge_documents.fields.categoria'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('titulo')
                    ->label(__('knowledge_documents.fields.titulo'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('contenido')
                    ->label(__('knowledge_documents.fields.contenido'))
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('knowledge_documents.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label(__('knowledge_documents.fields.tipo'))
                    ->options(fn () => __('knowledge_documents.tipos')),
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
