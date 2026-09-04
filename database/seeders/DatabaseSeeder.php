<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\KnowledgeDocument;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['super-admin', 'admin', 'supervisor', 'agente'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        foreach ([
            'manage tenants',
            'manage users',
            'manage clients',
            'manage policies',
            'manage tickets',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@atiendecrm.test',
        ]);
        $superAdmin->assignRole('super-admin');

        $tenant = Tenant::create([
            'name' => 'Correduría Demo',
            'slug' => 'correduria-demo',
            'tax_id' => 'J-00000000-0',
            'is_active' => true,
            'features' => [
                'notifications' => [
                    'days_to_pay' => 15,
                ],
            ],
        ]);

        $admin = User::factory()->create([
            'name' => 'Agente Demo',
            'email' => 'agente@atiendecrm.test',
            'tenant_id' => $tenant->id,
        ]);
        $admin->assignRole('agente');

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Demo',
            'national_id' => 'V-12345678',
            'phone' => '+58 412 0000000',
            'email' => 'cliente@example.com',
        ]);

        $policy = Policy::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'policy_number' => 'POL-0001',
            'line_of_business' => 'auto',
            'insurer' => 'Aseguradora Demo',
            'start_date' => now()->subMonths(6),
            'expiration_date' => now()->addDays(15),
            'status' => 'active',
            'premium' => 500,
            'payment_frequency' => 'mensual',
        ]);

        Ticket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'policy_id' => $policy->id,
            'agent_id' => $admin->id,
            'type' => 'consulta',
            'subject' => 'Consulta sobre cobertura',
            'description' => 'El cliente desea saber si su póliza cubre grúa.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'policy_id' => $policy->id,
            'monto' => 500,
            'fecha_pago' => now()->subDays(10),
            'estado' => 'pagado',
            'metodo' => 'tarjeta',
        ]);

        KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'categoria' => 'auto',
            'titulo' => '¿Qué cubre la póliza de auto?',
            'contenido' => 'La póliza de auto cubre daños a terceros, robo total, pérdida total, y opcionalmente '
                .'grúa y asistencia vial según el plan contratado.',
            'tipo' => 'faq',
        ]);

        KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'categoria' => 'general',
            'titulo' => '¿Cómo puedo cambiar mi método de pago?',
            'contenido' => 'Puedes cambiar tu método de pago contactando a tu asesor o a través del portal de '
                .'clientes en la sección de facturación.',
            'tipo' => 'faq',
        ]);

        KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'categoria' => 'auto',
            'titulo' => 'Procedimiento en caso de siniestro de auto',
            'contenido' => 'Ante un siniestro de auto debes reportarlo dentro de las 24 horas siguientes, tomar '
                .'fotografías del daño y comunicarte con la línea de asistencia de la aseguradora.',
            'tipo' => 'articulo_kb',
        ]);

        // Runs last so the tenant above (the one WhatsApp messages that
        // can't be resolved any other way fall back to) keeps id 1.
        $this->call(DemoWhatsAppSeeder::class);

        $this->call(KnowledgeBaseSeeder::class);
    }
}
