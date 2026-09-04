<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_template_filled_with_the_clients_and_tenants_real_data(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.CMDTEST1']]]),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);

        $this->artisan('whatsapp:test-notification', [
            'client' => $client->id,
            'template' => 'plantilla_prueba',
            '--params' => 2,
            '--force' => true,
        ])->assertSuccessful();

        $record = WhatsappNotification::where('template', 'plantilla_prueba')->first();

        $this->assertNotNull($record);
        $this->assertSame('sent', $record->status);
        $this->assertSame('wamid.CMDTEST1', $record->wamid);
        // Matches exactly what a real ClientWelcome notification would send.
        $this->assertSame(['Juan Perez', 'Acme'], $record->variables);

        Http::assertSent(fn ($request) => $request['template']['name'] === 'plantilla_prueba'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan Perez'
            && $request['template']['components'][0]['parameters'][1]['text'] === 'Acme');
    }

    public function test_it_pads_extra_parameters_beyond_the_clients_real_data(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.CMDTEST2']]]),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);

        $this->artisan('whatsapp:test-notification', [
            'client' => $client->id,
            'template' => 'plantilla_larga',
            '--params' => 4,
            '--force' => true,
        ])->assertSuccessful();

        $record = WhatsappNotification::where('template', 'plantilla_larga')->first();

        $this->assertCount(4, $record->variables);
        // The first two slots are still the client's real data, not random.
        $this->assertSame('Juan Perez', $record->variables[0]);
        $this->assertSame('Acme', $record->variables[1]);
    }

    public function test_it_fails_gracefully_when_the_client_does_not_exist(): void
    {
        $this->artisan('whatsapp:test-notification', [
            'client' => 999999,
            'template' => 'plantilla_prueba',
            '--force' => true,
        ])->assertFailed();

        $this->assertDatabaseCount('whatsapp_notifications', 0);
    }

    public function test_it_fails_gracefully_when_the_client_has_no_phone(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Sin Telefono']);

        $this->artisan('whatsapp:test-notification', [
            'client' => $client->id,
            'template' => 'plantilla_prueba',
            '--force' => true,
        ])->assertFailed();
    }

    public function test_it_reports_meta_rejection(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['code' => 132001, 'message' => 'Template not approved'],
            ], 400),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);

        $this->artisan('whatsapp:test-notification', [
            'client' => $client->id,
            'template' => 'plantilla_no_aprobada',
            '--params' => 0,
            '--force' => true,
        ])->assertFailed();

        $record = WhatsappNotification::where('template', 'plantilla_no_aprobada')->first();
        $this->assertSame('failed', $record->status);
        $this->assertSame('132001', $record->error_code);
    }
}
