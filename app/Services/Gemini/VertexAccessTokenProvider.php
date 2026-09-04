<?php

namespace App\Services\Gemini;

use App\Exceptions\GeminiApiException;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;

class VertexAccessTokenProvider
{
    protected const OAUTH_SCOPE = 'https://www.googleapis.com/auth/cloud-platform';

    protected const CACHE_KEY = 'vertex_ai_access_token';

    public function __construct(protected string $credentialsPath) {}

    /**
     * Los tokens de acceso de Google duran ~1 hora; se cachean para no pedir
     * uno nuevo en cada llamada (clasificación + generación por mensaje).
     *
     * @throws GeminiApiException
     */
    public function token(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), function (): string {
            $credentials = new ServiceAccountCredentials(self::OAUTH_SCOPE, $this->resolvedCredentialsPath());
            $token = $credentials->fetchAuthToken();

            if (! is_array($token) || ! isset($token['access_token'])) {
                throw new GeminiApiException('No se pudo obtener un token de acceso de Vertex AI.');
            }

            return $token['access_token'];
        });
    }

    /**
     * Una ruta relativa en GEMINI_VERTEX_CREDENTIALS depende del directorio
     * de trabajo del proceso PHP en ese momento, que no siempre es la raíz
     * del proyecto (artisan sí lo es; PHP-FPM/el servidor web no siempre).
     * Se resuelve contra la raíz de la app para que funcione sin importar
     * quién invoque a PHP.
     */
    protected function resolvedCredentialsPath(): string
    {
        return str_starts_with($this->credentialsPath, DIRECTORY_SEPARATOR)
            ? $this->credentialsPath
            : base_path($this->credentialsPath);
    }
}
