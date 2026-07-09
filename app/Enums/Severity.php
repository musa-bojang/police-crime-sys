<?php

namespace App\Enums;

/**
 * How serious a wanted-vehicle alert is — drives how prominent the officer's
 * on-hit alert looks.
 */
enum Severity: string
{
    case Caution   = 'caution';    // note of interest, approach normally
    case Wanted    = 'wanted';     // actively sought
    case Dangerous = 'dangerous';  // treat with heightened caution
}
