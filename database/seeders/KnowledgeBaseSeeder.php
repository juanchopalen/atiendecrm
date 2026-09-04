<?php

namespace Database\Seeders;

use App\Models\KnowledgeDocument;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Contenido de FAQ/KB para poder probar el agente con respuestas reales en
 * vez de "sin resultados". Cubre las preguntas de prueba sugeridas para el
 * harness (cobertura de auto, cambio de método de pago, reclamos, siniestro).
 */
class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command?->warn('No hay tenants creados todavía; ejecuta el seeder principal primero.');

            return;
        }

        foreach ($tenants as $tenant) {
            foreach ($this->documentos() as $documento) {
                KnowledgeDocument::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'titulo' => $documento['titulo']],
                    [...$documento, 'tenant_id' => $tenant->id],
                );
            }
        }
    }

    /**
     * @return array<int, array{categoria: string, titulo: string, contenido: string, tipo: string}>
     */
    protected function documentos(): array
    {
        return [
            [
                'categoria' => 'auto',
                'titulo' => '¿Qué cubre la póliza de auto?',
                'contenido' => 'La póliza de auto cubre daños a terceros, pérdida total y robo del vehículo. '
                    .'Según el plan contratado, también puede incluir grúa, asistencia vial 24/7 y auto sustituto. '
                    .'Para conocer el detalle exacto de tu cobertura, indícanos el número de tu póliza.',
                'tipo' => 'faq',
            ],
            [
                'categoria' => 'general',
                'titulo' => '¿Cómo puedo cambiar mi método de pago?',
                'contenido' => 'Puedes cambiar tu método de pago (tarjeta, transferencia o domiciliación) '
                    .'escribiéndole a tu asesor asignado con el nuevo medio de pago, o solicitándolo directamente '
                    .'en nuestras oficinas. El cambio aplica a partir del siguiente recibo.',
                'tipo' => 'faq',
            ],
            [
                'categoria' => 'general',
                'titulo' => '¿Qué documentos necesito para hacer un reclamo?',
                'contenido' => 'Para iniciar un reclamo necesitas: cédula de identidad, copia de la póliza vigente, '
                    .'el informe o parte de tránsito (si aplica) y fotos del daño. Nuestro equipo de siniestros te '
                    .'indicará si se requiere algún documento adicional según el tipo de reclamo.',
                'tipo' => 'faq',
            ],
            [
                'categoria' => 'auto',
                'titulo' => 'Procedimiento en caso de accidente o siniestro de auto',
                'contenido' => "Si tuviste un accidente con tu vehículo, sigue estos pasos:\n"
                    ."1. Verifica que todos estén a salvo y, si es necesario, llama a emergencias.\n"
                    ."2. No muevas el vehículo hasta que llegue el tránsito, salvo que obstaculice la vía.\n"
                    .'3. Llama a la línea de asistencia vial de tu aseguradora para reportar el siniestro dentro '
                    ."de las primeras 24 horas.\n"
                    ."4. Toma fotos del vehículo, los daños y la escena.\n"
                    ."5. Solicita el informe o parte policial si hubo terceros involucrados.\n"
                    .'Con esta información tu asesor abrirá el caso y te indicará los próximos pasos.',
                'tipo' => 'articulo_kb',
            ],
        ];
    }
}
