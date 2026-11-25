<?php
namespace App\Models;

use App\Core\Database;

/**
 * Small per-user settings store (key → value), e.g. which aging modules a user
 * shows on the Dashboard. Rows are removed automatically with the user.
 */
class UserPreference
{
    private static bool $tableReady = false;

    public function ensureTable(): void
    {
        if (self::$tableReady) return;
        Database::connection()->exec("CREATE TABLE IF NOT EXISTS user_preferences (
            user_id INT UNSIGNED NOT NULL,
            pref_key VARCHAR(64) NOT NULL,
            pref_value TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, pref_key),
            CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$tableReady = true;
    }

    public function get(int $userId, string $key): ?string
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare('SELECT pref_value FROM user_preferences WHERE user_id=? AND pref_key=? LIMIT 1');
        $stmt->execute([$userId, $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string)$value;
    }

    public function set(int $userId, string $key, ?string $value): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            'INSERT INTO user_preferences (user_id, pref_key, pref_value) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE pref_value=VALUES(pref_value)'
        );
        $stmt->execute([$userId, $key, $value]);
    }
}
