<?php

namespace App\Filament\Pages;

use App\Exceptions\GeminiApiException;
use App\Models\Client;
use App\Services\Agent\AgentOrchestrator;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class AgentTestHarness extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Agente (prueba)';

    protected static ?string $title = 'Harness de pruebas del agente';

    protected string $view = 'filament.pages.agent-test-harness';

    /** @var array<int, array{cliente_id: ?int, nombre: string, telefono: string}> */
    public array $clientesDisponibles = [];

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<int, array{rol: string, texto: string, hora: string}> */
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

        $this->form->fill([
            'telefonoSeleccionado' => 'no_registrado',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $opciones = collect($this->clientesDisponibles)
            ->mapWithKeys(fn (array $cliente): array => [
                $cliente['telefono'] => "{$cliente['nombre']} ({$cliente['telefono']})",
            ])
            ->prepend('Número no registrado', 'no_registrado')
            ->all();

        return $schema
            ->components([
                Select::make('telefonoSeleccionado')
                    ->hiddenLabel()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options($opciones)
                    ->default('no_registrado')
                    ->live(),
            ])
            ->statePath('data');
    }

    public function enviarMensaje(AgentOrchestrator $orchestrator): void
    {
        $texto = trim($this->mensaje);

        if ($texto === '') {
            return;
        }

        $telefonoSeleccionado = $this->data['telefonoSeleccionado'] ?? 'no_registrado';

        $telefono = $telefonoSeleccionado === 'no_registrado'
            ? '+00000000000'
            : (string) $telefonoSeleccionado;

        $this->historial[] = ['rol' => 'usuario', 'texto' => $texto, 'hora' => now()->format('H:i')];
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

        $this->historial[] = [
            'rol' => 'agente',
            'texto' => $resultado['respuesta_final']['respuesta'] ?? '',
            'hora' => now()->format('H:i'),
        ];
        $this->debugLog[] = $resultado;
    }

    public function reiniciar(): void
    {
        $this->historial = [];
        $this->debugLog = [];
        $this->mensaje = '';
    }

    public function clienteSeleccionado(): ?array
    {
        $telefonoSeleccionado = $this->data['telefonoSeleccionado'] ?? null;

        if ($telefonoSeleccionado === null || $telefonoSeleccionado === 'no_registrado') {
            return null;
        }

        foreach ($this->clientesDisponibles as $cliente) {
            if ($cliente['telefono'] === $telefonoSeleccionado) {
                return $cliente;
            }
        }

        return null;
    }

    public function confianzaColor(mixed $confianza): string
    {
        if (! is_numeric($confianza)) {
            return 'gray';
        }

        return match (true) {
            $confianza >= 0.75 => 'success',
            $confianza >= 0.5 => 'warning',
            default => 'danger',
        };
    }

    public function intencionColor(?string $tipoIntencion): string
    {
        return match ($tipoIntencion) {
            'fuera_de_alcance' => 'danger',
            'consulta_cliente' => 'info',
            'kb_categoria' => 'warning',
            null => 'gray',
            default => 'primary',
        };
    }
}
