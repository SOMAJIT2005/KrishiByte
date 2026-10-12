<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLoggerService
{
    public static function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $notes = null,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null
    ): AuditLog {
        $actorId = $userId ?? auth()->id();

        return AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => substr(Request::userAgent() ?? 'CLI/System', 0, 500),
            'notes' => $notes,
            'before_payload' => $before,
            'after_payload' => $after,
            'created_at' => now(),
        ]);
    }
}
