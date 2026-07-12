<?php

namespace App\Filament\Widgets;

use App\Enums\OffenceStatus;
use App\Enums\WatchlistStatus;
use App\Models\Offence;
use App\Models\Sighting;
use App\Models\User;
use App\Models\WatchlistVehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    // Recompute every 30s instead of the default 5s — kinder to a small server.
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today     = Offence::whereDate('occurred_at', today())->count();
        $thisWeek  = Offence::where('occurred_at', '>=', now()->startOfWeek())->count();
        $lastWeek  = Offence::whereBetween('occurred_at', [
            now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek(),
        ])->count();

        $weekTrend = $thisWeek - $lastWeek;

        $pendingReview = Offence::whereIn('status', [
            OffenceStatus::Submitted, OffenceStatus::UnderReview,
        ])->count();

        $activeWatchlist = WatchlistVehicle::where('status', WatchlistStatus::Active)->count();
        $sightings7d     = Sighting::where('sighted_at', '>=', now()->subDays(7))->count();

        $activeOfficers = User::whereHas('roles', fn ($q) => $q->where('name', 'officer'))
            ->where('is_active', true)
            ->count();

        // Officers who recorded at least one offence in the last 7 days.
        $officersInField = Offence::where('occurred_at', '>=', now()->subDays(7))
            ->distinct('officer_id')
            ->count('officer_id');

        return [
            Stat::make('Offences this week', $thisWeek)
                ->description($weekTrend >= 0
                    ? abs($weekTrend).' more than last week'
                    : abs($weekTrend).' fewer than last week')
                ->descriptionIcon($weekTrend >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($weekTrend >= 0 ? 'warning' : 'success'),

            Stat::make('Offences today', $today)
                ->description('Recorded since midnight')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Awaiting review', $pendingReview)
                ->description('Submitted or under review')
                ->descriptionIcon('heroicon-m-inbox')
                ->color($pendingReview > 0 ? 'warning' : 'success'),

            Stat::make('Active watchlist', $activeWatchlist)
                ->description($sightings7d.' sighting(s) in the last 7 days')
                ->descriptionIcon('heroicon-m-flag')
                ->color($activeWatchlist > 0 ? 'danger' : 'gray'),

            Stat::make('Officers in the field', $officersInField)
                ->description("of {$activeOfficers} active officers reported this week")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
