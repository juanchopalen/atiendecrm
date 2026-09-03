<?php

namespace App\Filament\Resources\KnowledgeDocuments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class KnowledgeDocumentForm
{
    /**
     * Longitud máxima recomendada de "contenido" por tipo de documento, para
     * mantener la precisión de la búsqueda por palabra clave y del contexto
     * que recibe Gemini al generar la respuesta final.
     *
     * @var array<string, int>
     */
    protected const MAX_CONTENIDO_LENGTH = [
        'faq' => 800,
        'articulo_kb' => 2000,
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo')
                    ->label(__('knowledge_documents.fields.tipo'))
                    ->options(fn () => __('knowledge_documents.tipos'))
                    ->default('faq')
                    ->live()
                    ->required(),
                TextInput::make('categoria')
                    ->label(__('knowledge_documents.fields.categoria'))
                    ->helperText(__('knowledge_documents.helpers.categoria')),
                TextInput::make('titulo')
                    ->label(__('knowledge_documents.fields.titulo'))
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('contenido')
                    ->label(__('knowledge_documents.fields.contenido'))
                    ->required()
                    ->rows(6)
                    ->live(debounce: 500)
                    ->maxLength(fn (Get $get): int => static::maxContenidoLength($get('tipo')))
                    ->hint(fn (Get $get, ?string $state): string => __('knowledge_documents.helpers.caracteres_restantes', [
                        'restantes' => static::maxContenidoLength($get('tipo')) - mb_strlen($state ?? ''),
                    ]))
                    ->hintColor(fn (Get $get, ?string $state): string => match (true) {
                        (static::maxContenidoLength($get('tipo')) - mb_strlen($state ?? '')) < 0 => 'danger',
                        (static::maxContenidoLength($get('tipo')) - mb_strlen($state ?? '')) <= 50 => 'warning',
                        default => 'gray',
                    })
                    ->columnSpanFull(),
            ]);
    }

    protected static function maxContenidoLength(?string $tipo): int
    {
        return static::MAX_CONTENIDO_LENGTH[$tipo] ?? max(static::MAX_CONTENIDO_LENGTH);
    }
}
