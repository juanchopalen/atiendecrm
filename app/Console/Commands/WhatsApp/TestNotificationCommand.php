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
 * Sends a real WhatsApp template to an existing client with randomly
 * generated body parameters, so a template can be smoke-tested end-to-end
 * (channel resolution, shared-number fallback, Meta's response) without
 * writing a one-off tinker script each time.
 */
class TestNotificationCommand extends Command
{
    protected $signature = 'whatsapp:test-notification
        {client? : ID del cliente al que se enviará la plantilla de prueba}
        {template? : Nombre exacto de la plantilla, tal como está aprobada en Meta}
        {--lang= : Código de idioma de la plantilla (por defecto el configurado en la app)}
        {--params=2 : Cantidad de parámetros de cuerpo a rellenar con datos aleatorios}
        {--department=General : Departamento usado para resolver el canal/número de envío}
        {--force : Enviar sin pedir confirmación}';

    protected $description = 'Envía una plantilla de WhatsApp de prueba a un cliente existente, con parámetros aleatorios';

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
        $parameters = $this->randomParameters($paramCount);

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
     * @return array<int, string>
     */
    protected function randomParameters(int $count): array
    {
        $generators = [
            fn () => fake()->name(),
            fn () => now()->addDays(fake()->numberBetween(1, 30))->format('d/m/Y'),
            fn () => fake()->words(3, true),
            fn () => (string) fake()->numberBetween(100, 9999),
            fn () => fake()->city(),
        ];

        if ($count <= 0) {
            return [];
        }

        return Collection::times($count, fn (int $i) => $generators[($i - 1) % count($generators)]())
            ->all();
    }
}
