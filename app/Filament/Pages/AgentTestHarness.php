<?php

namespace App\Filament\Pages;

use App\Exceptions\GeminiApiException;
use App\Models\Client;
use App\Services\Agent\AgentOrchestrator;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AgentTestHarness extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Agente (prueba)';

    protected static ?string $title = 'Harness de pruebas del agente';

    protected string $view = 'filament.pages.agent-test-harness';

    /** @var array<int, array{cliente_id: ?int, nombre: string, telefono: string}> */
    public array $clientesDisponibles = [];

    public ?string $telefonoSeleccionado = null;

    /** @var array<int, array{rol: string, texto: string}> */
    public array $historial = [];

    /** @var array<int, array<string, mixed>> */
    public array $debugLog = [];

    public string $mensaje = '';

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        $clientes = Client::query()
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $this->clientesDisponibles = $clientes
            ->map(fn (Client $client): array => [
                'cliente_id' => $client->id,
                'nombre' => $client->name,
                'telefono' => $client->phone ?? '',
            ])
            ->all();

        $this->telefonoSeleccionado = 'no_registrado';
    }

    public function enviarMensaje(AgentOrchestrator $orchestrator): void
    {
        $texto = trim($this->mensaje);

        if ($texto === '') {
            return;
        }

        $telefono = $this->telefonoSeleccionado === 'no_registrado'
            ? '+00000000000'
            : (string) $this->telefonoSeleccionado;

        $this->historial[] = ['rol' => 'usuario', 'texto' => $texto];
        $this->mensaje = '';

        try {
            $resultado = $orchestrator->procesarMensaje($telefono, $texto, 'test');
        } catch (GeminiApiException $e) {
            Notification::make()
                ->danger()
                ->title('El agente no pudo responder')
                ->body($e->getMessage())
                ->send();

            return;
        }

        $this->historial[] = ['rol' => 'agente', 'texto' => $resultado['respuesta_final']['respuesta'] ?? ''];
        $this->debugLog[] = $resultado;
    }

    public function reiniciar(): void
    {
        $this->historial = [];
        $this->debugLog = [];
        $this->mensaje = '';
    }
}
