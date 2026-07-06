<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OffenceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    /**
     * Stream a single evidence photo from the private disk.
     *
     * Reached only via a short-lived signed URL (see the route) AND a valid
     * panel session. Every successful view is written to the audit trail for
     * chain of custody.
     */
    public function show(Request $request, OffenceImage $image): StreamedResponse
    {
        $user = $request->user();

        // Only active supervisors/admins may view evidence — never officers.
        if (! $user || ! $user->is_active || ! $user->hasAnyRole(['admin', 'supervisor'])) {
            abort(403);
        }

        // Only verified images have a stored file; quarantined/pending don't.
        if (! $image->file_path || ! Storage::disk('local')->exists($image->file_path)) {
            abort(404);
        }

        AuditLog::record('image.viewed', $image);

        return Storage::disk('local')->response($image->file_path);
    }
}
