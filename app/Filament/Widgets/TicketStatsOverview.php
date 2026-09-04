<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Policies\PolicyResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Policy;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $openStatuses = ['open', 'in_progress', 'waiting_client'];

        $openTickets = Ticket::query()->whereIn('status', $openStatuses)->count();
        $urgentTickets = Ticket::query()->where('priority', 'urgent')->whereIn('status', $openStatuses)->count();
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
                ->color('info')
                ->url(TicketResource::getUrl('index', [
                    'filters' => ['status' => ['values' => $openStatuses]],
                ])),
            Stat::make(__('dashboard.urgent_open_tickets'), $urgentTickets)
                ->color($urgentTickets > 0 ? 'danger' : 'success')
                ->url(TicketResource::getUrl('index', [
                    'filters' => [
                        'status' => ['values' => $openStatuses],
                        'priority' => ['value' => 'urgent'],
                    ],
                ])),
            Stat::make(__('dashboard.expiring_policies_stat'), $expiringPolicies)
                ->color($expiringPolicies > 0 ? 'warning' : 'success')
                ->url(PolicyResource::getUrl('index', [
                    'filters' => [
                        'status' => ['value' => 'active'],
                        'expiring_soon' => ['isActive' => true],
                    ],
                ])),
            Stat::make(
                __('dashboard.average_response_time'),
                $avgResponseMinutes ? round($avgResponseMinutes).' '.__('dashboard.minutes') : __('dashboard.not_available'),
            ),
        ];
    }
}
