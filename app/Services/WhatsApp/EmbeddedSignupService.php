<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use App\Models\Tenant;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappChannel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Completes Meta's WhatsApp Embedded Signup flow for a tenant: exchanges the
 * short-lived code the JS SDK hands back for a token scoped to the tenant's
 * WABA, subscribes Ademia's app to that WABA's webhooks, and persists the
 * resulting whatsapp_business_accounts / whatsapp_channels records.
 *
 * See especificacion_multi_tenant_whatsapp.md §5 for the end-to-end flow.
 */
class EmbeddedSignupService
{
    public function __construct(protected string $apiVersion) {}

    /**
     * @param  array{waba_id: string, phone_number_id: string, numero_visible: string, departamento?: string, modo?: string}  $signupData
     */
    public function completeSignup(Tenant $tenant, string $code, array $signupData): WhatsappChannel
    {
        $accessToken = $this->exchangeCodeForToken($code);

        $account = WhatsappBusinessAccount::query()->updateOrCreate(
            ['waba_id' => $signupData['waba_id']],
            [
                'tenant_id' => $tenant->id,
                'business_verification_status' => 'pending',
                'access_token' => $accessToken,
            ],
        );

        $this->subscribeAppToWaba($signupData['waba_id'], $accessToken);

        return WhatsappChannel::query()->updateOrCreate(
            ['phone_number_id' => $signupData['phone_number_id']],
            [
                'whatsapp_business_account_id' => $account->id,
                'tenant_id' => $tenant->id,
                'numero_visible' => $signupData['numero_visible'],
                'departamento' => $signupData['departamento'] ?? 'General',
                'modo' => $signupData['modo'] ?? 'dedicated',
                'estado' => 'pending_verification',
                'calidad' => 'unknown',
                'solo_demo' => false,
            ],
        );
    }

    /**
     * Exchanges the Embedded Signup `code` for a long-lived access token
     * scoped to the corretaje's WABA, granted to Ademia's system user.
     */
    protected function exchangeCodeForToken(string $code): string
    {
        try {
            $response = Http::baseUrl("https://graph.facebook.com/{$this->apiVersion}")
                ->get('/oauth/access_token', [
                    'client_id' => config('services.whatsapp.embedded_signup.meta_app_id'),
                    'client_secret' => config('services.whatsapp.embedded_signup.meta_app_secret'),
                    'code' => $code,
                ]);
        } catch (ConnectionException $e) {
            throw new WhatsAppApiException($e->getMessage(), retryable: true);
        }

        if (! $response->successful()) {
            throw new WhatsAppApiException(
                (string) ($response->json('error.message') ?? 'Embedded Signup token exchange failed'),
                apiErrorCode: (string) $response->json('error.code'),
                retryable: $response->serverError(),
            );
        }

        return (string) $response->json('access_token');
    }

    /**
     * Registers Ademia's app to receive webhooks for the corretaje's WABA.
     */
    protected function subscribeAppToWaba(string $wabaId, string $accessToken): void
    {
        $response = Http::withToken($accessToken)
            ->baseUrl("https://graph.facebook.com/{$this->apiVersion}")
            ->post("/{$wabaId}/subscribed_apps");

        if (! $response->successful()) {
            throw new WhatsAppApiException(
                (string) ($response->json('error.message') ?? 'Failed to subscribe app to WABA'),
                apiErrorCode: (string) $response->json('error.code'),
                retryable: $response->serverError(),
            );
        }
    }
}
