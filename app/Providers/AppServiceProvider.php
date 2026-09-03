<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Ticket;
use App\Notifications\Channels\WhatsAppChannel;
use App\Observers\ClientObserver;
use App\Observers\TicketObserver;
use App\Policies\RolePolicy;
use App\Services\Gemini\GeminiClient;
use App\Services\WhatsApp\EmbeddedSignupService;
use App\Services\WhatsApp\WhatsAppChannelResolver;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppClientFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Legacy single-channel client, kept as a fallback for tenants that
        // have not yet been migrated to a whatsapp_channels record.
        $this->app->singleton(WhatsAppClient::class, fn () => new WhatsAppClient(
            apiVersion: config('services.whatsapp.api_version'),
            phoneNumberId: config('services.whatsapp.phone_number_id') ?? '',
            accessToken: config('services.whatsapp.access_token') ?? '',
        ));

        $this->app->singleton(WhatsAppClientFactory::class, fn () => new WhatsAppClientFactory(
            apiVersion: config('services.whatsapp.api_version'),
        ));

        $this->app->singleton(WhatsAppChannelResolver::class);

        $this->app->singleton(EmbeddedSignupService::class, fn () => new EmbeddedSignupService(
            apiVersion: config('services.whatsapp.api_version'),
        ));

        $this->app->singleton(GeminiClient::class, fn () => new GeminiClient(
            apiKey: config('services.gemini.api_key') ?? '',
            model: config('services.gemini.model'),
            timeout: (int) config('services.gemini.timeout', 20),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('whatsapp', fn ($app) => $app->make(WhatsAppChannel::class));

        Client::observe(ClientObserver::class);
        Ticket::observe(TicketObserver::class);

        Gate::policy(Role::class, RolePolicy::class);
    }
}
