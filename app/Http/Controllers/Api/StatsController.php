<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Personal activity figures for the authenticated officer. Scoped to their
     * own records only — an officer never sees another officer's numbers.
     *
     * The app caches the last response so the figures still display offline,
     * labelled with the time they were fetched.
     */
    public function me(Request $request): JsonResponse
    {
        $officerId = $request->user()->id;

        $base = fn () => Offence::where('officer_id', $officerId);

        return response()->json([
            'today'      => $base()->whereDate('occurred_at', today())->count(),
            'this_week'  => $base()->where('occurred_at', '>=', now()->startOfWeek())->count(),
            'total'      => $base()->count(),
            'fetched_at' => now()->toIso8601String(),
        ]);
    }
}
