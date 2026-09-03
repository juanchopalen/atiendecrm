<?php

namespace App\Http\Controllers;

use App\Exceptions\WhatsAppApiException;
use App\Models\Tenant;
use App\Services\WhatsApp\EmbeddedSignupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the callback from the WhatsApp Embedded Signup JS SDK once a
 * corretaje has picked/created their WABA and phone number, and completes
 * the token exchange + webhook subscription server-side.
 */
class WhatsAppEmbeddedSignupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected EmbeddedSignupService $signupService) {}

    public function callback(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'waba_id' => ['required', 'string'],
            'phone_number_id' => ['required', 'string'],
            'numero_visible' => ['required', 'string'],
            'departamento' => ['nullable', 'string'],
            'modo' => ['nullable', 'in:dedicated,coexistence'],
        ]);

        try {
            $channel = $this->signupService->completeSignup($tenant, $validated['code'], [
                'waba_id' => $validated['waba_id'],
                'phone_number_id' => $validated['phone_number_id'],
                'numero_visible' => $validated['numero_visible'],
                'departamento' => $validated['departamento'] ?? 'General',
                'modo' => $validated['modo'] ?? 'dedicated',
            ]);
        } catch (WhatsAppApiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['whatsapp_channel_id' => $channel->id], 201);
    }
}
