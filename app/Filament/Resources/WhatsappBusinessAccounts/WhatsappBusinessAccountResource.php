<?php

namespace App\Filament\Resources\WhatsappBusinessAccounts;

use App\Filament\Resources\WhatsappBusinessAccounts\Pages\CreateWhatsappBusinessAccount;
use App\Filament\Resources\WhatsappBusinessAccounts\Pages\EditWhatsappBusinessAccount;
use App\Filament\Resources\WhatsappBusinessAccounts\Pages\ListWhatsappBusinessAccounts;
use App\Filament\Resources\WhatsappBusinessAccounts\Schemas\WhatsappBusinessAccountForm;
use App\Filament\Resources\WhatsappBusinessAccounts\Tables\WhatsappBusinessAccountsTable;
use App\Models\WhatsappBusinessAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhatsappBusinessAccountResource extends Resource
{
    protected static ?string $model = WhatsappBusinessAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    public static function getModelLabel(): string
    {
        return __('whatsapp_business_accounts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('whatsapp_business_accounts.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('whatsapp_business_accounts.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsappBusinessAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsappBusinessAccountsTable::configure($table);
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
            'index' => ListWhatsappBusinessAccounts::route('/'),
            'create' => CreateWhatsappBusinessAccount::route('/create'),
            'edit' => EditWhatsappBusinessAccount::route('/{record}/edit'),
        ];
    }
}
