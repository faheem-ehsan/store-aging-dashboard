<?php
namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;

/**
 * Brute-force protection for the login form, driven by the LOGIN_FAILED rows
 * that AuditLogger already writes to audit_logs.
 *
 * - Per email: MAX_PER_EMAIL failures inside the window locks that email.
 *   A successful sign-in for the email resets its counter.
 * - Per IP: MAX_PER_IP failures inside the window locks the source address
 *   (set generously because office users may share one NAT address).
 *
 * If the audit_logs table is not installed, throttling is skipped (fails open).
 */
class LoginThrottle
{
    public const WINDOW_MINUTES = 15;
    public const MAX_PER_EMAIL = 5;
    public const MAX_PER_IP = 25;

    public function isBlocked(string $email, string $ip): bool
    {
        try {
            if (!(new AuditLog())->tableExists()) return false;
            $pdo = Database::connection();
            $window = self::WINDOW_MINUTES;

            if ($email !== '') {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM audit_logs
                     WHERE action='LOGIN_FAILED' AND actor_email=?
                       AND created_at >= NOW() - INTERVAL {$window} MINUTE
                       AND created_at > COALESCE((
                           SELECT MAX(s.created_at) FROM audit_logs s
                           WHERE s.action='LOGIN_SUCCESS' AND s.actor_email=?
                       ), '1970-01-02')"
                );
                $stmt->execute([$email, $email]);
                if ((int)$stmt->fetchColumn() >= self::MAX_PER_EMAIL) return true;
            }

            if ($ip !== '') {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM audit_logs
                     WHERE action='LOGIN_FAILED' AND ip_address=?
                       AND created_at >= NOW() - INTERVAL {$window} MINUTE"
                );
                $stmt->execute([$ip]);
                if ((int)$stmt->fetchColumn() >= self::MAX_PER_IP) return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}
