<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Hosts Meta's WhatsApp Embedded Signup JS SDK so a corretaje can connect
 * its own WABA/number without an Ademia admin touching Meta Business Suite.
 * See especificacion_multi_tenant_whatsapp.md §5.
 */
class ConnectWhatsAppChannel extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Conectar número de WhatsApp';

    protected static ?string $title = 'Conectar número de WhatsApp';

    protected string $view = 'filament.pages.connect-whatsapp-channel';

    public function getMetaAppId(): string
    {
        return (string) config('services.whatsapp.embedded_signup.meta_app_id');
    }

    public function getConfigId(): string
    {
        return (string) config('services.whatsapp.embedded_signup.config_id');
    }

    public function isConfigured(): bool
    {
        return $this->getMetaAppId() !== '' && $this->getConfigId() !== '';
    }

    public function getCallbackUrl(): string
    {
        return route('whatsapp.embedded-signup.callback', ['tenant' => Filament::getTenant()]);
    }

    public function getApiVersion(): string
    {
        return (string) config('services.whatsapp.api_version', 'v22.0');
    }
}
