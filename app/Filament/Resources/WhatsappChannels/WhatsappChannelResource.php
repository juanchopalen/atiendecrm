<?php

namespace App\Filament\Resources\WhatsappChannels;

use App\Filament\Resources\WhatsappChannels\Pages\CreateWhatsappChannel;
use App\Filament\Resources\WhatsappChannels\Pages\EditWhatsappChannel;
use App\Filament\Resources\WhatsappChannels\Pages\ListWhatsappChannels;
use App\Filament\Resources\WhatsappChannels\Schemas\WhatsappChannelForm;
use App\Filament\Resources\WhatsappChannels\Tables\WhatsappChannelsTable;
use App\Models\WhatsappChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhatsappChannelResource extends Resource
{
    protected static ?string $model = WhatsappChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getModelLabel(): string
    {
        return __('whatsapp_channels.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('whatsapp_channels.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('whatsapp_channels.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsappChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsappChannelsTable::configure($table);
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
            'index' => ListWhatsappChannels::route('/'),
            'create' => CreateWhatsappChannel::route('/create'),
            'edit' => EditWhatsappChannel::route('/{record}/edit'),
        ];
    }
}
