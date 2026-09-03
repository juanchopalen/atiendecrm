<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Populates the "Osvaldo Avilán SC" corretaje with realistic-looking demo
 * data: 20 clients, each with one or two policies, a handful of tickets and
 * their related payments, so the app can be demoed with data that doesn't
 * look empty or synthetic.
 */
class OsvaldoAvilanSeeder extends Seeder
{
    use WithoutModelEvents;

    private const NOMBRES = [
        'María José Rodríguez', 'Carlos Alberto Pérez', 'Ana Gabriela Torres', 'Luis Fernando Gómez',
        'Daniela Carolina Suárez', 'José Miguel Hernández', 'Andreína Sofía Marín', 'Pedro Pablo Rangel',
        'Valentina Alejandra Ríos', 'Rafael Eduardo Blanco', 'Gabriela Isabel Contreras', 'Miguel Ángel Delgado',
        'Camila Victoria Salazar', 'Jesús Alberto Mendoza', 'Fabiola del Valle Guerra', 'Ricardo José Núñez',
        'Adriana Carolina Fuentes', 'Alejandro David Castillo', 'Mariana Beatriz Ochoa', 'Francisco Javier Uzcátegui',
    ];

    private const ASEGURADORAS = [
        'Seguros Caracas', 'Mercantil Seguros', 'Seguros Mercantil', 'MAPFRE La Seguridad',
        'Seguros La Previsora', 'Seguros Pirámide', 'Seguros Universitas', 'Seguros Banesco',
        'Multinacional de Seguros', 'C.A. Venezolana de Seguros',
    ];

    private const RAMOS = ['auto', 'salud', 'vida', 'hogar', 'otro'];

    private const FRECUENCIAS = ['mensual', 'trimestral', 'semestral', 'anual'];

    private const TIPOS_TICKET = ['siniestro', 'consulta', 'reclamo', 'renovacion'];

    private const PRIORIDADES = ['low', 'medium', 'high', 'urgent'];

    private const ASUNTOS = [
        'siniestro' => [
            'Choque por alcance en autopista' => 'El vehículo del cliente fue impactado por detrás; solicita apertura de siniestro y grúa.',
            'Robo parcial de vehículo' => 'Sustrajeron las llantas y el radio del vehículo asegurado, se anexa denuncia policial.',
            'Rotura de vidrio por granizo' => 'El parabrisas presenta daños tras la tormenta de granizo del fin de semana.',
            'Inundación en vivienda asegurada' => 'Filtración de agua por lluvias intensas afectó el mobiliario de la sala.',
        ],
        'consulta' => [
            'Consulta sobre cobertura de grúa' => 'El cliente desea saber si su póliza incluye servicio de grúa las 24 horas.',
            'Duda sobre deducible aplicable' => 'Pregunta cuál es el monto del deducible en caso de pérdida total.',
            'Consulta sobre exclusiones de la póliza' => 'Quiere confirmar si eventos naturales están cubiertos bajo su plan actual.',
            'Consulta sobre red de clínicas afiliadas' => 'Solicita el listado actualizado de clínicas afiliadas en su ciudad.',
        ],
        'reclamo' => [
            'Reclamo por demora en pago de siniestro' => 'El cliente indica que han pasado 30 días sin recibir el pago del siniestro reportado.',
            'Reclamo por rechazo de cobertura' => 'No está de acuerdo con el rechazo de la reclamación y solicita revisión.',
            'Reclamo por monto de indemnización' => 'Considera que el monto ofrecido por la aseguradora es inferior al valor real del bien.',
        ],
        'renovacion' => [
            'Solicitud de renovación anticipada' => 'El cliente desea renovar su póliza antes de la fecha de vencimiento.',
            'Renovación con cambio de plan' => 'Solicita renovar la póliza pero migrando a un plan de mayor cobertura.',
            'Consulta sobre prima de renovación' => 'Pregunta si la prima de renovación tendrá algún incremento este año.',
        ],
    ];

    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'osvaldo-avilan-sc'],
            [
                'name' => 'Osvaldo Avilán SC',
                'tax_id' => 'J-30123456-7',
                'is_active' => true,
                'es_demo' => false,
                'features' => [
                    'notifications' => [
                        'days_to_pay' => 15,
                    ],
                ],
            ]
        );

        $agentes = collect(['Osvaldo Avilán', 'Carolina Méndez', 'Andrés Figueroa'])
            ->map(function (string $nombre, int $index) use ($tenant) {
                $email = str($nombre)->slug('.').'@osvaldoavilansc.com';

                $user = User::query()->firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $nombre,
                        'email_verified_at' => now(),
                        'password' => bcrypt('password'),
                        'tenant_id' => $tenant->id,
                    ]
                );

                if (! $user->hasAnyRole(['admin', 'agente'])) {
                    $user->assignRole($index === 0 ? 'admin' : 'agente');
                }

                return $user;
            });

        $ticketCounter = 1;

        foreach (self::NOMBRES as $index => $nombre) {
            $numero = $index + 1;
            $cedula = 'V-'.random_int(8_000_000, 27_000_000);
            $telefono = '+58 4'.collect([12, 14, 16, 24, 26])->random().' '.random_int(1000000, 9999999);

            $client = Client::create([
                'tenant_id' => $tenant->id,
                'name' => $nombre,
                'national_id' => $cedula,
                'phone' => $telefono,
                'email' => str($nombre)->slug('.').'@gmail.com',
                'address' => $this->direccionAleatoria(),
            ]);

            $numPolizas = random_int(1, 2);

            for ($p = 1; $p <= $numPolizas; $p++) {
                $ramo = self::RAMOS[array_rand(self::RAMOS)];
                $inicio = Carbon::now()->subMonths(random_int(1, 11))->subDays(random_int(0, 27));
                $vencimiento = $inicio->copy()->addYear();
                $estado = match (true) {
                    $vencimiento->isPast() => 'expired',
                    random_int(1, 20) === 1 => 'cancelled',
                    default => 'active',
                };

                $policy = Policy::create([
                    'tenant_id' => $tenant->id,
                    'client_id' => $client->id,
                    'policy_number' => sprintf('OASC-%s-%04d', strtoupper(substr($ramo, 0, 3)), ($numero * 10) + $p),
                    'line_of_business' => $ramo,
                    'insurer' => self::ASEGURADORAS[array_rand(self::ASEGURADORAS)],
                    'start_date' => $inicio,
                    'expiration_date' => $vencimiento,
                    'status' => $estado,
                    'premium' => $this->primaPara($ramo),
                    'payment_frequency' => self::FRECUENCIAS[array_rand(self::FRECUENCIAS)],
                ]);

                $numPagos = random_int(1, 3);

                for ($pg = 1; $pg <= $numPagos; $pg++) {
                    $fechaPago = $inicio->copy()->addMonths($pg - 1);
                    $pagoFuturo = $fechaPago->isFuture();

                    Payment::create([
                        'tenant_id' => $tenant->id,
                        'client_id' => $client->id,
                        'policy_id' => $policy->id,
                        'monto' => $policy->premium,
                        'fecha_pago' => $pagoFuturo ? null : $fechaPago,
                        'estado' => $pagoFuturo ? 'pendiente' : (random_int(1, 15) === 1 ? 'vencido' : 'pagado'),
                        'metodo' => collect(['transferencia', 'tarjeta', 'pago_movil', 'zelle'])->random(),
                    ]);
                }

                if (random_int(1, 3) === 1) {
                    $tipo = self::TIPOS_TICKET[array_rand(self::TIPOS_TICKET)];
                    [$asunto, $descripcion] = $this->asuntoAleatorio($tipo);
                    $estadoTicket = collect(['open', 'in_progress', 'waiting_client', 'closed'])->random();

                    Ticket::create([
                        'tenant_id' => $tenant->id,
                        'client_id' => $client->id,
                        'policy_id' => $policy->id,
                        'agent_id' => $agentes->random()->id,
                        'type' => $tipo,
                        'subject' => $asunto,
                        'description' => $descripcion,
                        'priority' => self::PRIORIDADES[array_rand(self::PRIORIDADES)],
                        'status' => $estadoTicket,
                        'closed_at' => $estadoTicket === 'closed' ? now()->subDays(random_int(1, 20)) : null,
                    ]);
                    $ticketCounter++;
                }
            }

            // Every client gets at least one ticket not tied to a specific policy.
            $tipo = self::TIPOS_TICKET[array_rand(self::TIPOS_TICKET)];
            [$asunto, $descripcion] = $this->asuntoAleatorio($tipo);
            $estadoTicket = collect(['open', 'in_progress', 'waiting_client', 'closed'])->random();

            Ticket::create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'policy_id' => null,
                'agent_id' => $agentes->random()->id,
                'type' => $tipo,
                'subject' => $asunto,
                'description' => $descripcion,
                'priority' => self::PRIORIDADES[array_rand(self::PRIORIDADES)],
                'status' => $estadoTicket,
                'closed_at' => $estadoTicket === 'closed' ? now()->subDays(random_int(1, 20)) : null,
            ]);
            $ticketCounter++;
        }
    }

    private function asuntoAleatorio(string $tipo): array
    {
        $opciones = self::ASUNTOS[$tipo];
        $asunto = array_rand($opciones);

        return [$asunto, $opciones[$asunto]];
    }

    private function primaPara(string $ramo): float
    {
        return match ($ramo) {
            'auto' => random_int(300, 1200),
            'salud' => random_int(500, 2500),
            'vida' => random_int(150, 800),
            'hogar' => random_int(200, 900),
            default => random_int(100, 600),
        };
    }

    private function direccionAleatoria(): string
    {
        $urbanizaciones = [
            'Urb. Los Palos Grandes, Caracas', 'Urb. La California Norte, Caracas', 'Urb. Las Mercedes, Caracas',
            'Urb. El Cafetal, Caracas', 'Av. Bolívar Norte, Valencia', 'Urb. La Trigaleña, Valencia',
            'Urb. Tierra Negra, Maracaibo', 'Urb. La Lago, Maracaibo', 'Urb. Base Aragua, Maracay',
            'Av. Las Delicias, Maracay', 'Urb. La Viña, Valencia', 'Urb. Santa Fe, Caracas',
        ];

        return sprintf('Calle %d, %s', random_int(1, 40), $urbanizaciones[array_rand($urbanizaciones)]);
    }
}
