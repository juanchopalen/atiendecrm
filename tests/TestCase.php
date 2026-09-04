<?php

namespace Tests;

use App\Services\Gemini\VertexAccessTokenProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Evita que cualquier test resuelva el provider real e intente
        // cargar un archivo de credenciales de Vertex AI que no existe.
        // Los tests de GeminiClient que sí necesitan un token específico
        // pueden atar su propio fake sobre este.
        $this->app->instance(VertexAccessTokenProvider::class, new class extends VertexAccessTokenProvider
        {
            public function __construct() {}

            public function token(): string
            {
                return 'fake-access-token';
            }
        });
    }
}
