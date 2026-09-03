<?php

namespace Tests\Unit;

use App\Exceptions\WhatsAppApiException;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppClientTest extends TestCase
{
    public function test_send_template_returns_wamid_on_success(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.ABC123']],
            ], 200),
        ]);

        $client = new WhatsAppClient('v22.0', '1234567890', 'test-token');

        $wamid = $client->sendTemplate('5215512345678', 'cita_confirmada', 'es_MX', ['Juan', '28/08/2026']);

        $this->assertSame('wamid.ABC123', $wamid);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v22.0/1234567890/messages'
                && $request['template']['name'] === 'cita_confirmada'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan';
        });
    }

    public function test_send_template_throws_non_retryable_exception_on_client_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['code' => 132001, 'message' => 'Template not approved'],
            ], 400),
        ]);

        $client = new WhatsAppClient('v22.0', '1234567890', 'test-token');

        try {
            $client->sendTemplate('5215512345678', 'plantilla_no_aprobada', 'es_MX');
            $this->fail('Expected WhatsAppApiException to be thrown.');
        } catch (WhatsAppApiException $e) {
            $this->assertFalse($e->retryable);
            $this->assertSame('132001', $e->apiErrorCode);
        }
    }

    public function test_send_template_throws_retryable_exception_on_server_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Internal error']], 500),
        ]);

        $client = new WhatsAppClient('v22.0', '1234567890', 'test-token');

        try {
            $client->sendTemplate('5215512345678', 'cita_confirmada', 'es_MX');
            $this->fail('Expected WhatsAppApiException to be thrown.');
        } catch (WhatsAppApiException $e) {
            $this->assertTrue($e->retryable);
        }
    }

    public function test_send_text_returns_wamid_on_success(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.SESSION1']],
            ], 200),
        ]);

        $client = new WhatsAppClient('v22.0', '1234567890', 'test-token');

        $wamid = $client->sendText('5215512345678', 'Tu póliza POL-0001 está activa.');

        $this->assertSame('wamid.SESSION1', $wamid);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v22.0/1234567890/messages'
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Tu póliza POL-0001 está activa.';
        });
    }

    public function test_send_text_throws_non_retryable_exception_outside_service_window(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['code' => 131047, 'message' => 'Re-engagement message'],
            ], 400),
        ]);

        $client = new WhatsAppClient('v22.0', '1234567890', 'test-token');

        try {
            $client->sendText('5215512345678', 'Hola de nuevo');
            $this->fail('Expected WhatsAppApiException to be thrown.');
        } catch (WhatsAppApiException $e) {
            $this->assertFalse($e->retryable);
            $this->assertSame('131047', $e->apiErrorCode);
        }
    }
}
