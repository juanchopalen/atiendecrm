<x-filament-panels::page>
    <style>
        .aht-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
    
        .aht-stack {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
    
        @media (min-width: 64rem) {
            .aht-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
    
            .aht-grid-main {
                grid-column: span 2 / span 2;
            }
        }
    
        .aht-select-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
        }
    
        .aht-select-row > :first-child {
            flex: 1 1 16rem;
            min-width: 0;
        }
    
        .aht-chat {
            display: flex;
            height: 28rem;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
            border-radius: .75rem;
            background: var(--gray-50);
            padding: 1rem;
        }
    
        :root.dark .aht-chat {
            background: color-mix(in oklab, var(--gray-800) 50%, transparent);
        }
    
        .aht-empty-wrap {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
        }
    
        .aht-row {
            display: flex;
            align-items: flex-end;
            gap: .5rem;
        }
    
        .aht-row-user {
            flex-direction: row-reverse;
        }
    
        .aht-avatar {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
        }
    
        .aht-avatar-user {
            background: var(--primary-600);
            color: #fff;
        }
    
        .aht-avatar-agent {
            background: var(--gray-200);
            color: var(--gray-600);
        }
    
        :root.dark .aht-avatar-agent {
            background: var(--gray-700);
            color: var(--gray-300);
        }
    
        .aht-bubble-col {
            display: flex;
            max-width: 75%;
            flex-direction: column;
            gap: .25rem;
        }
    
        .aht-bubble-col-user {
            align-items: flex-end;
        }
    
        .aht-bubble-col-agent {
            align-items: flex-start;
        }
    
        .aht-bubble {
            border-radius: 1rem;
            padding: .5rem 1rem;
            font-size: .875rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
        }
    
        .aht-bubble p {
            margin: 0;
            white-space: pre-wrap;
        }
    
        .aht-bubble-user {
            border-bottom-right-radius: .25rem;
            background: var(--primary-600);
            color: #fff;
        }
    
        .aht-bubble-agent {
            border-bottom-left-radius: .25rem;
            background: #fff;
            color: var(--gray-800);
        }
    
        :root.dark .aht-bubble-agent {
            background: var(--gray-700);
            color: var(--gray-100);
        }
    
        .aht-bubble-empty {
            font-style: italic;
            color: var(--gray-400);
        }
    
        .aht-time {
            padding: 0 .25rem;
            font-size: .75rem;
            color: var(--gray-400);
        }
    
        .aht-typing {
            display: flex;
            align-items: center;
            gap: .25rem;
            border-radius: 1rem;
            border-bottom-left-radius: .25rem;
            background: #fff;
            padding: .75rem 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
        }
    
        :root.dark .aht-typing {
            background: var(--gray-700);
        }
    
        .aht-typing span {
            width: .375rem;
            height: .375rem;
            border-radius: 9999px;
            background: var(--gray-400);
            animation: aht-bounce 1s infinite;
        }
    
        .aht-typing span:nth-child(2) {
            animation-delay: .15s;
        }
    
        .aht-typing span:nth-child(3) {
            animation-delay: .3s;
        }
    
        @keyframes aht-bounce {
            0%, 80%, 100% {
                transform: translateY(0);
            }
    
            40% {
                transform: translateY(-.25rem);
            }
        }
    
        .aht-composer {
            display: flex;
            gap: .5rem;
            margin-top: 1rem;
        }
    
        .aht-composer input {
            min-width: 0;
            flex: 1 1 auto;
        }
    
        .aht-debug-list {
            display: flex;
            max-height: 40rem;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
        }
    
        .aht-debug-card {
            border-radius: .5rem;
            border: 1px solid var(--gray-200);
            padding: .75rem;
            font-size: .75rem;
        }
    
        :root.dark .aht-debug-card {
            border-color: var(--gray-700);
        }
    
        .aht-debug-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .5rem;
        }
    
        .aht-debug-turn {
            font-weight: 600;
            color: var(--gray-500);
        }
    
        :root.dark .aht-debug-turn {
            color: var(--gray-400);
        }
    
        .aht-badge-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .375rem;
        }
    
        .aht-tools summary {
            display: flex;
            margin-top: .5rem;
            cursor: pointer;
            list-style: none;
            align-items: center;
            gap: .25rem;
            font-weight: 600;
            color: var(--gray-600);
        }
    
        :root.dark .aht-tools summary {
            color: var(--gray-300);
        }
    
        .aht-tools summary::-webkit-details-marker {
            display: none;
        }
    
        .aht-tools summary svg {
            transition: transform .15s ease;
        }
    
        .aht-tools[open] summary svg {
            transform: rotate(90deg);
        }
    
        .aht-tools pre {
            margin-top: .25rem;
            max-height: 14rem;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
            border-radius: .375rem;
            background: var(--gray-100);
            padding: .5rem;
            font-size: .6875rem;
            line-height: 1.5;
        }
    
        :root.dark .aht-tools pre {
            background: var(--gray-800);
        }
    </style>

    <div class="aht-grid">
        <div class="aht-grid-main aht-stack">
            <x-filament::section>
                <x-slot name="heading">Cliente simulado</x-slot>
                <x-slot name="description">Elige con qué identidad de WhatsApp probará el agente.</x-slot>

                <div class="aht-select-row">
                    <div>
                        {{ $this->form }}
                    </div>

                    @php $clienteActivo = $this->clienteSeleccionado(); @endphp
                    <x-filament::badge :color="$clienteActivo ? 'success' : 'gray'" icon="heroicon-m-phone">
                        {{ $clienteActivo ? $clienteActivo['telefono'] : 'Número no registrado' }}
                    </x-filament::badge>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Conversación de prueba</x-slot>

                <x-slot name="afterHeader">
                    <x-filament::button
                        type="button"
                        color="gray"
                        size="sm"
                        icon="heroicon-m-arrow-path"
                        wire:click="reiniciar"
                        wire:confirm="¿Reiniciar la conversación de prueba? Se perderá el historial y el panel de depuración."
                        :disabled="empty($historial)"
                    >
                        Reiniciar
                    </x-filament::button>
                </x-slot>

                <div
                    x-data
                    x-init="$watch('$wire.historial', () => $nextTick(() => $el.scrollTop = $el.scrollHeight))"
                    class="aht-chat"
                >
                    @forelse ($historial as $turno)
                        @php $esUsuario = $turno['rol'] === 'usuario'; @endphp
                        <div class="aht-row {{ $esUsuario ? 'aht-row-user' : '' }}">
                            <div class="aht-avatar {{ $esUsuario ? 'aht-avatar-user' : 'aht-avatar-agent' }}">
                                <x-filament::icon
                                    :icon="$esUsuario ? 'heroicon-m-user' : 'heroicon-m-cpu-chip'"
                                />
                            </div>

                            <div class="aht-bubble-col {{ $esUsuario ? 'aht-bubble-col-user' : 'aht-bubble-col-agent' }}">
                                <div class="aht-bubble {{ $esUsuario ? 'aht-bubble-user' : 'aht-bubble-agent' }}">
                                    @if ($turno['texto'] !== '')
                                        <p>{{ $turno['texto'] }}</p>
                                    @else
                                        <p class="aht-bubble-empty">Sin respuesta.</p>
                                    @endif
                                </div>
                                <span class="aht-time">{{ $turno['hora'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="aht-empty-wrap">
                            <x-filament::empty-state
                                icon="heroicon-o-chat-bubble-left-right"
                                heading="Sin mensajes todavía"
                                description="Escribe una pregunta de prueba para ver cómo responde el agente."
                            />
                        </div>
                    @endforelse

                    <div wire:loading wire:target="enviarMensaje" class="aht-row">
                        <div class="aht-avatar aht-avatar-agent">
                            <x-filament::icon icon="heroicon-m-cpu-chip" />
                        </div>
                        <div class="aht-typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>

                <form wire:submit="enviarMensaje" class="aht-composer">
                    <input
                        type="text"
                        wire:model="mensaje"
                        wire:loading.attr="disabled"
                        wire:target="enviarMensaje"
                        autocomplete="off"
                        placeholder="Escribe una pregunta de prueba..."
                        class="fi-input"
                    />
                    <x-filament::button
                        type="submit"
                        icon="heroicon-m-paper-airplane"
                        wire:loading.attr="disabled"
                        wire:target="enviarMensaje"
                    >
                        Enviar
                    </x-filament::button>
                </form>
            </x-filament::section>
        </div>

        <div>
            <x-filament::section>
                <x-slot name="heading">Panel de depuración</x-slot>
                <x-slot name="description">Última clasificación y herramientas usadas por el agente en cada turno.</x-slot>

                <div class="aht-debug-list">
                    @forelse (array_reverse($debugLog, true) as $indice => $entrada)
                        @php
                            $tipoIntencion = $entrada['clasificacion']['tipo_intencion'] ?? null;
                            $confianza = $entrada['clasificacion']['confianza'] ?? null;
                            $fuente = $entrada['respuesta_final']['fuente'] ?? null;
                            $seguimiento = (bool) ($entrada['respuesta_final']['requiere_seguimiento_humano'] ?? false);
                            $herramientas = $entrada['tool_calls'] ?? [];
                        @endphp
                        <div class="aht-debug-card">
                            <div class="aht-debug-head">
                                <span class="aht-debug-turn">Turno #{{ $indice + 1 }}</span>
                                @if ($seguimiento)
                                    <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">
                                        Seguimiento humano
                                    </x-filament::badge>
                                @endif
                            </div>

                            <div class="aht-badge-row">
                                <x-filament::badge :color="$this->intencionColor($tipoIntencion)">
                                    {{ $tipoIntencion ?? 'sin clasificar' }}
                                </x-filament::badge>

                                @if ($confianza !== null)
                                    <x-filament::badge :color="$this->confianzaColor($confianza)">
                                        confianza {{ is_numeric($confianza) ? number_format((float) $confianza, 2) : $confianza }}
                                    </x-filament::badge>
                                @endif

                                @if ($fuente)
                                    <x-filament::badge color="gray" icon="heroicon-m-document-text">
                                        {{ $fuente }}
                                    </x-filament::badge>
                                @endif
                            </div>

                            <details class="aht-tools">
                                <summary>
                                    <x-filament::icon icon="heroicon-m-chevron-right" />
                                    tool_calls ({{ count($herramientas) }})
                                </summary>
                                <pre>{{ json_encode($herramientas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </div>
                    @empty
                        <x-filament::empty-state
                            icon="heroicon-o-bug-ant"
                            heading="Sin actividad todavía"
                            description="Aquí verás la clasificación y las herramientas usadas en cada respuesta."
                            :compact="true"
                        />
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
