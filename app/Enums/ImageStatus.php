<?php

namespace App\Enums;

/**
 * Sync + integrity states for an evidence image.
 * Stored as plain strings in the DB, validated via this enum in code.
 */
enum ImageStatus: string
{
    case Pending     = 'pending';      // row created, file not uploaded yet
    case Uploading   = 'uploading';    // file transfer in progress
    case Uploaded    = 'uploaded';     // file received, hash not yet checked
    case Verified    = 'verified';     // server re-hash matched — trustworthy
    case Failed      = 'failed';       // upload failed, client should retry
    case Quarantined = 'quarantined';  // hash mismatch — do NOT trust
}
