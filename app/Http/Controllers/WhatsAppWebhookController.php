<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppWebhookEvent;
use App\Models\WhatsappWebhookEvent;
use App\Services\WhatsApp\WhatsAppChannelResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(protected WhatsAppChannelResolver $channelResolver) {}

    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            && is_string($verifyToken)
            && $verifyToken !== ''
            && hash_equals($verifyToken, (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            return response('Invalid signature', 403);
        }

        foreach ($this->extractEvents($request->all()) as $event) {
            $channel = $event['phone_number_id']
                ? $this->channelResolver->resolveByPhoneNumberId($event['phone_number_id'])
                : null;

            $record = WhatsappWebhookEvent::create([
                'whatsapp_channel_id' => $channel?->id,
                'wamid' => $event['wamid'],
                'type' => $event['type'],
                'payload' => $event['payload'],
                'received_at' => now(),
            ]);

            ProcessWhatsAppWebhookEvent::dispatch($record->id);
        }

        return response('EVENT_RECEIVED', 200);
    }

    protected function hasValidSignature(Request $request): bool
    {
        $secret = config('services.whatsapp.app_secret');

        if (! is_string($secret) || $secret === '') {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{wamid: ?string, type: string, payload: array<string, mixed>, phone_number_id: ?string}>
     */
    protected function extractEvents(array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? 'unknown';
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                foreach ($value['statuses'] ?? [] as $status) {
                    $events[] = [
                        'wamid' => $status['id'] ?? null,
                        'type' => 'status_update',
                        'payload' => $status,
                        'phone_number_id' => $phoneNumberId,
                    ];
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $events[] = [
                        'wamid' => $message['id'] ?? null,
                        'type' => 'inbound_message',
                        'payload' => array_merge($message, [
                            'contacts' => $value['contacts'] ?? [],
                        ]),
                        'phone_number_id' => $phoneNumberId,
                    ];
                }

                if ($field === 'message_template_status_update') {
                    $events[] = [
                        'wamid' => null,
                        'type' => 'template_update',
                        'payload' => $value,
                        'phone_number_id' => $phoneNumberId,
                    ];
                }
            }
        }

        return $events;
    }
}
