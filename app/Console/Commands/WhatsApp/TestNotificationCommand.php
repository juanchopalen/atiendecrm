<?php

namespace App\Console\Commands\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use App\Models\Client;
use App\Models\WhatsappNotification;
use App\Notifications\AdHocWhatsAppTemplate;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Sends a real WhatsApp template to an existing client, filling body
 * parameters with the client's and its tenant's actual data first (matching
 * how real notifications like ClientWelcome build their parameters) and
 * only padding extra slots with generic placeholder data, so a template can
 * be smoke-tested end-to-end (channel resolution, shared-number fallback,
 * Meta's response) without writing a one-off tinker script each time.
 */
class TestNotificationCommand extends Command
{
    protected $signature = 'whatsapp:test-notification
        {client? : ID del cliente al que se enviará la plantilla de prueba}
        {template? : Nombre exacto de la plantilla, tal como está aprobada en Meta}
        {--lang= : Código de idioma de la plantilla (por defecto el configurado en la app)}
        {--params=2 : Cantidad de parámetros de cuerpo (se rellenan con datos reales del cliente/tenant primero, y con datos genéricos si piden más)}
        {--department=General : Departamento usado para resolver el canal/número de envío}
        {--force : Enviar sin pedir confirmación}';

    protected $description = 'Envía una plantilla de WhatsApp de prueba a un cliente existente, usando sus datos reales';

    public function handle(WhatsAppChannel $channel): int
    {
        $clientId = $this->argument('client') ?? $this->ask('ID del cliente');
        $client = Client::find($clientId);

        if (! $client) {
            $this->error("No existe un cliente con id {$clientId}.");

            return self::FAILURE;
        }

        if (blank($client->phone)) {
            $this->error("El cliente \"{$client->name}\" no tiene teléfono registrado.");

            return self::FAILURE;
        }

        $template = $this->argument('template') ?? $this->ask('Nombre exacto de la plantilla (tal como está en Meta)');

        if (blank($template)) {
            $this->error('Debes indicar el nombre de la plantilla.');

            return self::FAILURE;
        }

        $language = $this->option('lang') ?: config('services.whatsapp.default_language');
        $paramCount = max(0, (int) $this->option('params'));
        $parameters = $this->templateParameters($client, $paramCount);

        $this->table(['Cliente', 'Teléfono', 'Plantilla', 'Idioma', 'Parámetros'], [[
            $client->name,
            $client->routeNotificationForWhatsApp(),
            $template,
            $language,
            $parameters === [] ? '(ninguno)' : implode(' | ', $parameters),
        ]]);

        if (! $this->option('force') && ! $this->confirm('¿Enviar esta plantilla de prueba ahora?', true)) {
            $this->comment('Cancelado.');

            return self::SUCCESS;
        }

        $notification = new AdHocWhatsAppTemplate($template, $language, $parameters, (string) $this->option('department'));

        try {
            $channel->send($client, $notification);
        } catch (WhatsAppApiException $e) {
            $this->error("Falló el envío: {$e->getMessage()}");

            return self::FAILURE;
        }

        $record = WhatsappNotification::query()
            ->where('notifiable_type', $client->getMorphClass())
            ->where('notifiable_id', $client->id)
            ->where('template', $template)
            ->latest('id')
            ->first();

        if (! $record) {
            $this->error('No se generó ningún registro de notificación — revisa la configuración de WhatsApp.');

            return self::FAILURE;
        }

        if ($record->status === 'sent') {
            $this->info("Enviado correctamente. wamid: {$record->wamid}");

            return self::SUCCESS;
        }

        $this->error("Meta rechazó el envío [{$record->error_code}]: {$record->error_message}");

        return self::FAILURE;
    }

    /**
     * Fills body parameters with the client's and its tenant's real data
     * first — this is what actual templates like client_welcome expect
     * (client name, tenant name) — and pads any remaining slots with
     * generic placeholder data.
     *
     * @return array<int, string>
     */
    protected function templateParameters(Client $client, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $client->loadMissing('tenant');

        $realValues = collect([$client->name, $client->tenant?->name])
            ->filter(fn (?string $value) => filled($value))
            ->values()
            ->all();

        if ($count <= count($realValues)) {
            return array_slice($realValues, 0, $count);
        }

        return array_merge($realValues, $this->randomParameters($count - count($realValues)));
    }

    /**
     * @return array<int, string>
     */
    protected function randomParameters(int $count): array
    {
        // Plain PHP on purpose: fakerphp/faker is a require-dev dependency,
        // so it's absent from a production `composer install --no-dev`, and
        // this command needs to work there too.
        $names = ['Juan Pérez', 'María Rodríguez', 'Carlos Gómez', 'Ana Torres', 'Luis Fernández'];
        $words = ['revisión de cobertura', 'confirmación de cita', 'actualización de datos', 'seguimiento de caso'];
        $cities = ['Caracas', 'Valencia', 'Maracaibo', 'Maracay', 'Barquisimeto'];

        $generators = [
            fn () => $names[random_int(0, count($names) - 1)],
            fn () => now()->addDays(random_int(1, 30))->format('d/m/Y'),
            fn () => $words[random_int(0, count($words) - 1)],
            fn () => (string) random_int(100, 9999),
            fn () => $cities[random_int(0, count($cities) - 1)],
        ];

        if ($count <= 0) {
            return [];
        }

        return Collection::times($count, fn (int $i) => $generators[($i - 1) % count($generators)]())
            ->all();
    }
}
