<?php

namespace Database\Seeders;

use App\Models\Inbox;
use App\Models\Tenant;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappChannel;
use Illuminate\Database\Seeder;

/**
 * Seeds the single WhatsApp number shared by every corretaje for sales/demo
 * environments (especificacion_multi_tenant_whatsapp.md §9). Its channel is
 * flagged solo_demo so production notification flows refuse to use it.
 */
class DemoWhatsAppSeeder extends Seeder
{
    public function run(): void
    {
        $demoTenant = Tenant::query()->firstOrCreate(
            ['slug' => 'ademia-demo'],
            [
                'name' => 'Ademia Demo',
                'tax_id' => null,
                'is_active' => true,
                'es_demo' => true,
                'features' => [],
            ]
        );

        $account = WhatsappBusinessAccount::query()->firstOrCreate(
            ['waba_id' => 'DEMO-WABA'],
            [
                'tenant_id' => $demoTenant->id,
                'business_verification_status' => 'verified',
                'access_token' => null,
            ]
        );

        $channel = WhatsappChannel::query()->firstOrCreate(
            ['phone_number_id' => 'DEMO-PHONE-NUMBER-ID'],
            [
                'whatsapp_business_account_id' => $account->id,
                'tenant_id' => $demoTenant->id,
                'numero_visible' => '+58 000 0000000',
                'departamento' => 'Demo',
                'modo' => 'dedicated',
                'estado' => 'active',
                'calidad' => 'unknown',
                'solo_demo' => true,
            ]
        );

        Inbox::query()->firstOrCreate(
            ['whatsapp_channel_id' => $channel->id],
            ['tenant_id' => $demoTenant->id, 'nombre_visible' => 'Demo (compartido)']
        );
    }
}
