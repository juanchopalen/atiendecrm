<?php

namespace App\Services\Gemini;

use App\Exceptions\GeminiApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected int $timeout = 8,
    ) {}

    /**
     * Call Gemini with a JSON response schema and return the decoded payload.
     *
     * @param  array<int, array{role: string, parts: array<int, array{text: string}>}>  $contents
     * @param  array<string, mixed>|null  $responseSchema
     * @return array<string, mixed>
     *
     * @throws GeminiApiException
     */
    public function generateJson(
        string $systemInstruction,
        array $contents,
        ?array $responseSchema = null,
        float $temperature = 0.0,
    ): array {
        $generationConfig = [
            'temperature' => $temperature,
            'responseMimeType' => 'application/json',
        ];

        if ($responseSchema !== null) {
            $generationConfig['responseSchema'] = $responseSchema;
        }

        try {
            $response = Http::baseUrl('https://generativelanguage.googleapis.com/v1beta')
                ->connectTimeout(3)
                ->timeout($this->timeout)
                // Fuerza IPv4: en producción el servidor no tiene ruta IPv6
                // en absoluto ("Network is unreachable"), y esta API resuelve
                // a direcciones IPv6. Debe ir por la opción de Guzzle, no por
                // CURLOPT_IPRESOLVE directo: Guzzle gestiona esa opción de
                // curl internamente y rechaza que se pase sin pasar por él.
                ->withOptions(['force_ip_resolve' => 'v4'])
                // Un 429 de cuota (tier gratuito) suele ser una ráfaga breve,
                // no un agotamiento total: 2 reintentos (3 intentos en total)
                // con espera corta absorben eso sin degradar la respuesta.
                ->retry(3, 3000, fn ($e) => $e instanceof RequestException && $e->response->status() === 429, throw: false)
                ->post("/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemInstruction]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => $generationConfig,
                ]);
        } catch (ConnectionException $e) {
            throw new GeminiApiException($e->getMessage(), retryable: true);
        }

        if (! $response->successful()) {
            throw new GeminiApiException(
                (string) ($response->json('error.message') ?? 'Unknown Gemini API error'),
                retryable: $response->serverError() || $response->status() === 429,
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new GeminiApiException('Gemini response did not contain text output.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new GeminiApiException('Gemini response was not valid JSON.');
        }

        return $decoded;
    }
}
