<?php

namespace Tests\Feature;

use App\Filament\Widgets\WhatsappChannelQualityOverview;
use App\Filament\Widgets\WhatsappChannelsAtRisk;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappChannel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WidgetSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_overview_widget_renders_channel_counts(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $waba = WhatsappBusinessAccount::create(['tenant_id' => $tenant->id, 'waba_id' => 'W1', 'business_verification_status' => 'verified']);
        WhatsappChannel::create(['whatsapp_business_account_id' => $waba->id, 'tenant_id' => $tenant->id, 'phone_number_id' => 'P1', 'numero_visible' => '+1', 'departamento' => 'Cobranzas', 'modo' => 'dedicated', 'estado' => 'active', 'calidad' => 'red']);
        WhatsappChannel::create(['whatsapp_business_account_id' => $waba->id, 'tenant_id' => $tenant->id, 'phone_number_id' => 'P2', 'numero_visible' => '+2', 'departamento' => 'General', 'modo' => 'dedicated', 'estado' => 'active', 'calidad' => 'green']);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);
        Filament::setTenant($tenant);

        Livewire::test(WhatsappChannelQualityOverview::class)
            ->assertSee('2')
            ->assertSee('1');

        Livewire::test(WhatsappChannelsAtRisk::class)
            ->assertSee('+1')
            ->assertDontSee('+2');
    }
}
