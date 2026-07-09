<?php

namespace App\Enums;

enum WatchlistStatus: string
{
    case Active  = 'active';   // live — synced to devices and matchable
    case Cleared = 'cleared';  // resolved — no longer an active alert
}
