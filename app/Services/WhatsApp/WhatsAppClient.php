<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WhatsAppClient
{
    public function __construct(
        protected string $apiVersion,
        protected string $phoneNumberId,
        protected string $accessToken,
    ) {}

    /**
     * Send an approved template message and return the wamid assigned by Meta.
     *
     * @param  array<int, string>  $bodyParameters
     *
     * @throws WhatsAppApiException
     */
    public function sendTemplate(string $to, string $template, string $language, array $bodyParameters = []): string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
            ],
        ];

        if ($bodyParameters !== []) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $value) => ['type' => 'text', 'text' => $value],
                    $bodyParameters,
                ),
            ]];
        }

        return $this->send($payload);
    }

    /**
     * Send a free-text session message. Meta only accepts these within the
     * 24-hour customer service window opened by an inbound message from the
     * recipient — outside that window the API rejects the request and it
     * must fall back to an approved template via sendTemplate().
     *
     * @throws WhatsAppApiException
     */
    public function sendText(string $to, string $body): string
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws WhatsAppApiException
     */
    protected function send(array $payload): string
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->baseUrl("https://graph.facebook.com/{$this->apiVersion}")
                ->post("/{$this->phoneNumberId}/messages", $payload);
        } catch (ConnectionException $e) {
            throw new WhatsAppApiException($e->getMessage(), retryable: true);
        }

        if ($response->successful()) {
            return (string) $response->json('messages.0.id');
        }

        $errorCode = (string) $response->json('error.code');
        $errorMessage = (string) ($response->json('error.message') ?? 'Unknown WhatsApp API error');

        throw new WhatsAppApiException(
            $errorMessage,
            apiErrorCode: $errorCode,
            retryable: $response->serverError(),
        );
    }
}
