<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class OpenTicketsByPriority extends ChartWidget
{
    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.open_tickets_by_priority');
    }

    protected function getData(): array
    {
        $counts = Ticket::query()
            ->whereNotIn('status', ['closed'])
            ->selectRaw('priority, count(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        $priorities = ['low', 'medium', 'high', 'urgent'];

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.open_tickets'),
                    'data' => collect($priorities)->map(fn ($priority) => $counts[$priority] ?? 0)->all(),
                    'backgroundColor' => ['#9ca3af', '#3b82f6', '#f59e0b', '#ef4444'],
                ],
            ],
            'labels' => collect($priorities)->map(fn ($priority) => __("tickets.priorities.{$priority}"))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
