<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Append-only audit trail. Records are written once and never changed.
 * The table has only created_at (no updated_at), so timestamps are managed
 * manually here, and updates/deletes are blocked at the model level.
 *
 * Write entries with the static helper, e.g.:
 *   AuditLog::record('image.viewed', $image, ['reason' => 'case export']);
 */
class AuditLog extends Model
{
    // Only created_at exists on this table.
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device_id',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'context'    => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Audit logs are append-only.'));
        static::deleting(fn () => throw new RuntimeException('Audit logs cannot be deleted.'));
    }

    /**
     * Convenience writer. Captures the current user and request context.
     */
    public static function record(string $action, ?Model $subject = null, array $context = []): self
    {
        return static::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id'   => $subject?->getKey(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'device_id'      => request()->header('X-Device-Id'),
            'context'        => $context ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
