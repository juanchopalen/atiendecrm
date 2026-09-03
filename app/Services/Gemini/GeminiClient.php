<?php

namespace App\Services\Gemini;

use App\Exceptions\GeminiApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected int $timeout = 20,
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
                ->connectTimeout(5)
                ->timeout($this->timeout)
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
                retryable: $response->serverError(),
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
