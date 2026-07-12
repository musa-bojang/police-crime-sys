<?php

namespace App\Filament\Widgets;

use App\Models\Offence;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OffencesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Offences — last 30 days';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // One row per day that had offences; fill the gaps with zeros so the
        // line covers all 30 days.
        $raw = Offence::selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->where('occurred_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data   = [];
        for ($i = 29; $i >= 0; $i--) {
            $day      = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($day)->format('d M');
            $data[]   = (int) ($raw[$day] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Offences',
                    'data'            => $data,
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
