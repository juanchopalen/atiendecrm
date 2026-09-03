<?php

namespace App\Filament\Resources\Inboxes;

use App\Filament\Resources\Inboxes\Pages\CreateInbox;
use App\Filament\Resources\Inboxes\Pages\EditInbox;
use App\Filament\Resources\Inboxes\Pages\ListInboxes;
use App\Filament\Resources\Inboxes\Schemas\InboxForm;
use App\Filament\Resources\Inboxes\Tables\InboxesTable;
use App\Models\Inbox;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InboxResource extends Resource
{
    protected static ?string $model = Inbox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    public static function getModelLabel(): string
    {
        return __('inboxes.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inboxes.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('inboxes.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return InboxForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InboxesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInboxes::route('/'),
            'create' => CreateInbox::route('/create'),
            'edit' => EditInbox::route('/{record}/edit'),
        ];
    }
}
