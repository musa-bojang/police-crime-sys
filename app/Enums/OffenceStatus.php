<?php

namespace App\Enums;

/**
 * Review workflow states for an offence record.
 * Stored as plain strings in the DB, validated via this enum in code.
 */
enum OffenceStatus: string
{
    case Draft       = 'draft';         // created on device, not yet submitted
    case Submitted   = 'submitted';     // synced to server, awaiting review
    case UnderReview = 'under_review';  // a supervisor is reviewing it
    case Confirmed   = 'confirmed';     // verified/upheld
    case Dismissed   = 'dismissed';     // thrown out on review
}
