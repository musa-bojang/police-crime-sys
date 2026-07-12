<?php

namespace App\Filament\Widgets;

use App\Models\Offence;
use Filament\Widgets\ChartWidget;

class OffenceTypesChart extends ChartWidget
{
    protected ?string $heading = 'Offence types — last 30 days';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $types = Offence::selectRaw('offence_type, COUNT(*) as total')
            ->where('occurred_at', '>=', now()->subDays(30))
            ->groupBy('offence_type')
            ->orderByDesc('total')
            ->pluck('total', 'offence_type');

        $labels = $types->keys()
            ->map(fn ($t) => ucfirst(str_replace('_', ' ', $t)))
            ->toArray();

        return [
            'datasets' => [
                [
                    'data'            => $types->values()->toArray(),
                    'backgroundColor' => [
                        '#6366f1', '#f59e0b', '#10b981', '#ef4444',
                        '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
