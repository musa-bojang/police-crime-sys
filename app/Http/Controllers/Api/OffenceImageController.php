<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImageStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OffenceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OffenceImageController extends Controller
{
    /**
     * Step two of the image flow: receive the binary for a pending image row,
     * re-hash it, and either verify or quarantine it.
     */
    public function upload(Request $request, OffenceImage $image): JsonResponse
    {
        // Ownership: you can only upload files for your own evidence rows.
        if ($image->officer_id !== $request->user()->id) {
            abort(403, 'Not your evidence to upload.');
        }

        // Verified evidence is immutable — never accept a replacement.
        if ($image->status === ImageStatus::Verified) {
            return response()->json([
                'id'     => $image->id,
                'status' => ImageStatus::Verified->value,
                'note'   => 'Already verified.',
            ]);
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'max:15360'], // 15 MB
        ]);

        $file = $request->file('file');

        // The integrity check: does the uploaded file match the hash the device
        // computed at capture? hash_equals avoids timing side-channels.
        $uploadedHash = strtolower(hash_file('sha256', $file->getRealPath()));

        if (! hash_equals($image->sha256_hash, $uploadedHash)) {
            $image->forceFill([
                'status'      => ImageStatus::Quarantined,
                'uploaded_at' => now(),
            ])->save();

            AuditLog::record('image.hash_mismatch', $image, [
                'declared' => $image->sha256_hash,
                'actual'   => $uploadedHash,
            ]);

            return response()->json([
                'id'      => $image->id,
                'status'  => ImageStatus::Quarantined->value,
                'message' => 'Hash mismatch — file rejected.',
            ], 422);
        }

        // Store on the default disk (set by FILESYSTEM_DISK — local in dev,
        // private S3 in production). Never a public disk.
        $ext  = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs('evidence/'.$image->offence_id, $image->id.'.'.$ext);

        [$width, $height, $thumbPath] = $this->deriveImageExtras($file, $image);

        $image->forceFill([
            'file_path'         => $path,
            'thumbnail_path'    => $thumbPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'width'             => $width,
            'height'            => $height,
            'status'            => ImageStatus::Verified,
            'uploaded_at'       => now(),
            'hash_verified_at'  => now(),
        ])->save();

        AuditLog::record('image.verified', $image);

        return response()->json([
            'id'        => $image->id,
            'status'    => ImageStatus::Verified->value,
            'file_size' => $image->file_size,
        ]);
    }

    /**
     * Best-effort dimensions + a 320px thumbnail. Skips silently if GD isn't
     * available, so a missing extension never blocks evidence upload.
     */
    private function deriveImageExtras($file, OffenceImage $image): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return [null, null, null];
        }

        try {
            $src = imagecreatefromstring(file_get_contents($file->getRealPath()));
            if ($src === false) {
                return [null, null, null];
            }

            $width  = imagesx($src);
            $height = imagesy($src);

            $tw    = 320;
            $th    = max(1, (int) round($height * ($tw / $width)));
            $thumb = imagescale($src, $tw, $th);

            $thumbPath = 'evidence/'.$image->offence_id.'/'.$image->id.'_thumb.jpg';
            ob_start();
            imagejpeg($thumb, null, 80);
            Storage::put($thumbPath, ob_get_clean());

            imagedestroy($src);
            imagedestroy($thumb);

            return [$width, $height, $thumbPath];
        } catch (\Throwable) {
            return [null, null, null];
        }
    }
}
