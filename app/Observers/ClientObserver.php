<?php

namespace App\Observers;

use App\Models\Client;
use App\Notifications\ClientWelcome;

class ClientObserver
{
    public function created(Client $client): void
    {
        $client->notify(new ClientWelcome($client));
    }
}
