<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappChannel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Per-channel quality/limits snapshot (especificacion_multi_tenant_whatsapp.md §11):
 * monitoring must be by channel, never aggregated blindly across a tenant's
 * numbers, so a low-quality number doesn't mask a healthy one.
 */
class WhatsappChannelQualityOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $channels = WhatsappChannel::query()->where('solo_demo', false)->get();

        $total = $channels->count();
        $atRisk = $channels->whereIn('calidad', ['red', 'yellow'])->count();
        $disabled = $channels->whereIn('estado', ['disabled', 'pending_verification'])->count();

        return [
            Stat::make(__('dashboard.whatsapp_channels_total'), $total)
                ->color('info'),
            Stat::make(__('dashboard.whatsapp_channels_at_risk'), $atRisk)
                ->color($atRisk > 0 ? 'danger' : 'success'),
            Stat::make(__('dashboard.whatsapp_channels_disabled'), $disabled)
                ->color($disabled > 0 ? 'warning' : 'success'),
        ];
    }
}
