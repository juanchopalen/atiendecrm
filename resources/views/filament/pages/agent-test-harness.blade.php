<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <x-filament::section>
                <x-slot name="heading">Cliente simulado</x-slot>

                <select wire:model="telefonoSeleccionado" class="fi-select-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="no_registrado">Número no registrado</option>
                    @foreach ($clientesDisponibles as $cliente)
                        <option value="{{ $cliente['telefono'] }}">{{ $cliente['nombre'] }} ({{ $cliente['telefono'] }})</option>
                    @endforeach
                </select>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Conversación de prueba</x-slot>

                <div class="space-y-3 max-h-96 overflow-y-auto mb-4">
                    @forelse ($historial as $turno)
                        <div class="flex {{ $turno['rol'] === 'usuario' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm {{ $turno['rol'] === 'usuario' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700' }}">
                                {{ $turno['texto'] }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sin mensajes todavía.</p>
                    @endforelse
                </div>

                <form wire:submit="enviarMensaje" class="flex gap-2">
                    <input
                        type="text"
                        wire:model="mensaje"
                        placeholder="Escribe una pregunta de prueba..."
                        class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                    />
                    <x-filament::button type="submit">Enviar</x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="reiniciar">Reiniciar</x-filament::button>
                </form>
            </x-filament::section>
        </div>

        <div>
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Panel de depuración</x-slot>

                <div class="space-y-4 max-h-[36rem] overflow-y-auto">
                    @forelse (array_reverse($debugLog) as $entrada)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-xs space-y-1">
                            <p><span class="font-semibold">tipo_intencion:</span> {{ $entrada['clasificacion']['tipo_intencion'] ?? '-' }}</p>
                            <p><span class="font-semibold">confianza:</span> {{ $entrada['clasificacion']['confianza'] ?? '-' }}</p>
                            <p><span class="font-semibold">fuente:</span> {{ $entrada['respuesta_final']['fuente'] ?? '-' }}</p>
                            <p><span class="font-semibold">seguimiento humano:</span> {{ ($entrada['respuesta_final']['requiere_seguimiento_humano'] ?? false) ? 'sí' : 'no' }}</p>
                            <details>
                                <summary class="cursor-pointer font-semibold">tool_calls ({{ count($entrada['tool_calls'] ?? []) }})</summary>
                                <pre class="whitespace-pre-wrap break-all mt-1">{{ json_encode($entrada['tool_calls'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sin actividad todavía.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
