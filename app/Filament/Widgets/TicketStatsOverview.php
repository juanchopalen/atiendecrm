<?php

namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $openTickets = Ticket::query()->whereNotIn('status', ['closed'])->count();
        $urgentTickets = Ticket::query()->where('priority', 'urgent')->whereNotIn('status', ['closed'])->count();
        $expiringPolicies = Policy::query()
            ->where('status', 'active')
            ->whereBetween('expiration_date', [now(), now()->addDays(30)])
            ->count();

        $avgResponseMinutes = Ticket::query()
            ->whereHas('interactions')
            ->with(['interactions' => fn ($query) => $query->oldest()->limit(1)])
            ->get()
            ->map(fn (Ticket $ticket) => $ticket->interactions->first()?->created_at?->diffInMinutes($ticket->created_at))
            ->filter()
            ->average();

        return [
            Stat::make(__('dashboard.open_tickets'), $openTickets)
                ->color('info'),
            Stat::make(__('dashboard.urgent_open_tickets'), $urgentTickets)
                ->color($urgentTickets > 0 ? 'danger' : 'success'),
            Stat::make(__('dashboard.expiring_policies_stat'), $expiringPolicies)
                ->color($expiringPolicies > 0 ? 'warning' : 'success'),
            Stat::make(
                __('dashboard.average_response_time'),
                $avgResponseMinutes ? round($avgResponseMinutes).' '.__('dashboard.minutes') : __('dashboard.not_available'),
            ),
        ];
    }
}
