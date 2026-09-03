<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappChannel;

class WhatsAppClientFactory
{
    public function __construct(protected string $apiVersion) {}

    public function forChannel(WhatsappChannel $channel): WhatsAppClient
    {
        $channel->loadMissing('whatsappBusinessAccount');

        return new WhatsAppClient(
            apiVersion: $this->apiVersion,
            phoneNumberId: $channel->phone_number_id,
            accessToken: (string) $channel->whatsappBusinessAccount->access_token,
        );
    }
}
