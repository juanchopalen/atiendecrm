<?php

namespace Tests\Unit;

use App\Exceptions\GeminiApiException;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\VertexAccessTokenProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class GeminiClientTest extends TestCase
{
    protected function fakeTokenProvider(): VertexAccessTokenProvider
    {
        return new class extends VertexAccessTokenProvider
        {
            public function __construct() {}

            public function token(): string
            {
                return 'fake-access-token';
            }
        };
    }

    public function test_generate_json_returns_decoded_payload_on_success(): void
    {
        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode(['respuesta' => 'hola'])]]],
                ]],
            ]),
        ]);

        $client = new GeminiClient('test-project', 'us-central1', $this->fakeTokenProvider(), 'gemini-3.6-flash');

        $this->assertSame(['respuesta' => 'hola'], $client->generateJson('instrucción', [
            ['role' => 'user', 'parts' => [['text' => 'hola']]],
        ]));

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && str_contains($request->url(), '/projects/test-project/locations/us-central1/publishers/google/models/gemini-3.6-flash:generateContent'));
    }

    public function test_generate_json_retries_a_429_and_succeeds(): void
    {
        Sleep::fake();

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Quota exceeded']], 429)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => json_encode(['respuesta' => 'ok'])]]],
                    ]],
                ]),
        ]);

        $client = new GeminiClient('test-project', 'us-central1', $this->fakeTokenProvider(), 'gemini-3.6-flash');

        $resultado = $client->generateJson('instrucción', [
            ['role' => 'user', 'parts' => [['text' => 'hola']]],
        ]);

        $this->assertSame(['respuesta' => 'ok'], $resultado);
        Http::assertSentCount(2);
    }

    public function test_generate_json_throws_retryable_exception_when_quota_stays_exhausted(): void
    {
        Sleep::fake();

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Quota exceeded for metric: generate_content_free_tier_requests']],
                429,
            ),
        ]);

        $client = new GeminiClient('test-project', 'us-central1', $this->fakeTokenProvider(), 'gemini-3.6-flash');

        try {
            $client->generateJson('instrucción', [
                ['role' => 'user', 'parts' => [['text' => 'hola']]],
            ]);
            $this->fail('Expected GeminiApiException to be thrown.');
        } catch (GeminiApiException $e) {
            $this->assertTrue($e->retryable);
            $this->assertStringContainsString('Quota exceeded', $e->getMessage());
        }

        // Intento inicial + 2 reintentos configurados en GeminiClient.
        Http::assertSentCount(3);
    }
}
