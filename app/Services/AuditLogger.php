<?php
namespace App\Services;

use App\Core\Auth;
use App\Models\AuditLog;

class AuditLogger
{
    public static function log(
        string $action,
        string $description,
        ?string $entityType = null,
        int|string|null $entityId = null,
        array $details = [],
        ?array $actor = null,
        ?int $divisionId = null,
        ?string $divisionCode = null
    ): void {
        try {
            $actor = $actor ?? Auth::user();
            $detailsJson = $details
                ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)
                : null;

            (new AuditLog())->create([
                'actor_user_id' => isset($actor['id']) ? (int)$actor['id'] : null,
                'actor_name' => $actor['name'] ?? null,
                'actor_email' => $actor['email'] ?? null,
                'actor_role' => $actor['role'] ?? null,
                'division_id' => $divisionId ?? (isset($actor['division_id']) ? (int)$actor['division_id'] : null),
                'division_code' => $divisionCode ?? ($actor['division_code'] ?? null),
                'action' => strtoupper(trim($action)),
                'entity_type' => $entityType,
                'entity_id' => $entityId === null ? null : (string)$entityId,
                'description' => $description,
                'details_json' => $detailsJson,
                'ip_address' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never interrupt the operational workflow.
        }
    }
}
