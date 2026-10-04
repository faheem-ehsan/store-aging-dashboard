<?php
namespace App\Models;

use App\Core\Database;
use App\Services\SecretCipher;

class GradeEmailNotification
{
    public function ensureTables(): void
    {
        $pdo = Database::connection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS grade_email_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            is_enabled TINYINT(1) NOT NULL DEFAULT 0,
            include_division_admins TINYINT(1) NOT NULL DEFAULT 1,
            smtp_host VARCHAR(190) NULL,
            smtp_port INT UNSIGNED NOT NULL DEFAULT 587,
            smtp_encryption ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
            smtp_username VARCHAR(190) NULL,
            smtp_password_enc TEXT NULL,
            from_email VARCHAR(190) NULL,
            from_name VARCHAR(190) NOT NULL DEFAULT 'Qarshi Stock Aging Dashboard',
            global_recipients TEXT NULL,
            test_mode TINYINT(1) NOT NULL DEFAULT 1,
            test_recipient VARCHAR(190) NULL,
            last_run_at DATETIME NULL,
            last_run_status VARCHAR(30) NULL,
            last_run_summary VARCHAR(500) NULL,
            updated_by INT UNSIGNED NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_grade_email_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS grade_email_division_recipients (
            division_id INT UNSIGNED NOT NULL PRIMARY KEY,
            recipient_emails TEXT NULL,
            updated_by INT UNSIGNED NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_grade_email_recipient_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
            CONSTRAINT fk_grade_email_recipient_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB");

        $pdo->exec("CREATE TABLE IF NOT EXISTS grade_email_alert_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            alert_key VARCHAR(190) NOT NULL UNIQUE,
            batch_id BIGINT UNSIGNED NULL,
            division_id INT UNSIGNED NULL,
            product_code VARCHAR(80) NULL,
            product_name VARCHAR(255) NULL,
            batch_no VARCHAR(80) NULL,
            current_grade VARCHAR(10) NULL,
            next_grade VARCHAR(10) NULL,
            transition_date DATE NULL,
            alert_date DATE NULL,
            recipients TEXT NULL,
            subject VARCHAR(255) NULL,
            status ENUM('sent','failed') NOT NULL,
            error_message TEXT NULL,
            attempt_count INT UNSIGNED NOT NULL DEFAULT 1,
            sent_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_grade_email_alert_batch FOREIGN KEY (batch_id) REFERENCES stock_batches(id) ON DELETE SET NULL,
            CONSTRAINT fk_grade_email_alert_division FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
            INDEX idx_grade_email_alert_status (status, alert_date),
            INDEX idx_grade_email_alert_division (division_id, alert_date)
        ) ENGINE=InnoDB");

        // Existing installations created before Test Mode need these columns added safely.
        $columns = [];
        foreach ($pdo->query("SHOW COLUMNS FROM grade_email_settings")->fetchAll() as $column) {
            $columns[strtolower((string)($column['Field'] ?? ''))] = true;
        }
        if (empty($columns['test_mode'])) {
            $pdo->exec("ALTER TABLE grade_email_settings ADD COLUMN test_mode TINYINT(1) NOT NULL DEFAULT 1 AFTER global_recipients");
        }
        if (empty($columns['test_recipient'])) {
            $pdo->exec("ALTER TABLE grade_email_settings ADD COLUMN test_recipient VARCHAR(190) NULL AFTER test_mode");
        }

        $pdo->exec("INSERT IGNORE INTO grade_email_settings (id) VALUES (1)");
    }

    public function settings(): array
    {
        $this->ensureTables();
        $row = Database::connection()->query('SELECT * FROM grade_email_settings WHERE id=1')->fetch() ?: [];
        $row['smtp_password_set'] = !empty($row['smtp_password_enc']);
        unset($row['smtp_password_enc']);
        return $row;
    }

    public function mailerSettings(): array
    {
        $this->ensureTables();
        $row = Database::connection()->query('SELECT * FROM grade_email_settings WHERE id=1')->fetch() ?: [];
        $row['smtp_password'] = !empty($row['smtp_password_enc'])
            ? (new SecretCipher())->decrypt((string)$row['smtp_password_enc'])
            : '';
        return $row;
    }

    public function saveSettings(array $data, int $userId): void
    {
        $this->ensureTables();
        $existing = Database::connection()->query('SELECT smtp_password_enc FROM grade_email_settings WHERE id=1')->fetch() ?: [];
        $passwordEnc = (string)($existing['smtp_password_enc'] ?? '');
        if (array_key_exists('smtp_password', $data) && (string)$data['smtp_password'] !== '') {
            $passwordEnc = (new SecretCipher())->encrypt((string)$data['smtp_password']);
        }

        $stmt = Database::connection()->prepare(
            "UPDATE grade_email_settings SET
                is_enabled=?, include_division_admins=?, smtp_host=?, smtp_port=?, smtp_encryption=?,
                smtp_username=?, smtp_password_enc=?, from_email=?, from_name=?, global_recipients=?,
                test_mode=?, test_recipient=?, updated_by=?
             WHERE id=1"
        );
        $stmt->execute([
            !empty($data['is_enabled']) ? 1 : 0,
            !empty($data['include_division_admins']) ? 1 : 0,
            trim((string)($data['smtp_host'] ?? '')) ?: null,
            (int)($data['smtp_port'] ?? 587),
            (string)($data['smtp_encryption'] ?? 'tls'),
            trim((string)($data['smtp_username'] ?? '')) ?: null,
            $passwordEnc ?: null,
            strtolower(trim((string)($data['from_email'] ?? ''))) ?: null,
            trim((string)($data['from_name'] ?? 'Qarshi Stock Aging Dashboard')) ?: 'Qarshi Stock Aging Dashboard',
            $this->canonicalEmails((string)($data['global_recipients'] ?? '')) ?: null,
            !empty($data['test_mode']) ? 1 : 0,
            strtolower(trim((string)($data['test_recipient'] ?? ''))) ?: null,
            $userId,
        ]);
    }

    public function divisionsWithRecipients(): array
    {
        $this->ensureTables();
        $sql = "SELECT d.id,d.code,d.name,d.is_active,
                       COALESCE(r.recipient_emails,'') recipient_emails,
                       COALESCE((
                           SELECT GROUP_CONCAT(u.email ORDER BY u.name SEPARATOR ', ')
                           FROM users u
                           WHERE u.division_id=d.id AND u.role='division_admin' AND u.is_active=1
                       ),'') division_admin_emails
                FROM divisions d
                LEFT JOIN grade_email_division_recipients r ON r.division_id=d.id
                ORDER BY d.code";
        return Database::connection()->query($sql)->fetchAll();
    }

    public function saveDivisionRecipients(array $emailsByDivision, int $userId): void
    {
        $this->ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "INSERT INTO grade_email_division_recipients (division_id,recipient_emails,updated_by)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE recipient_emails=VALUES(recipient_emails), updated_by=VALUES(updated_by)"
        );
        foreach ($emailsByDivision as $divisionId => $emails) {
            $divisionId = (int)$divisionId;
            if ($divisionId <= 0) continue;
            $canonical = $this->canonicalEmails((string)$emails);
            $stmt->execute([$divisionId, $canonical ?: null, $userId]);
        }
    }

    public function recipientMap(bool $includeDivisionAdmins): array
    {
        $map = [];
        foreach ($this->divisionsWithRecipients() as $row) {
            $emails = $this->splitEmails((string)$row['recipient_emails']);
            if ($includeDivisionAdmins) {
                $emails = array_merge($emails, $this->splitEmails((string)$row['division_admin_emails']));
            }
            $map[(int)$row['id']] = $this->uniqueValidEmails($emails);
        }
        return $map;
    }

    public function globalRecipients(array $settings): array
    {
        return $this->uniqueValidEmails($this->splitEmails((string)($settings['global_recipients'] ?? '')));
    }

    public function isSent(string $alertKey): bool
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare("SELECT 1 FROM grade_email_alert_log WHERE alert_key=? AND status='sent' LIMIT 1");
        $stmt->execute([$alertKey]);
        return (bool)$stmt->fetchColumn();
    }

    public function recordAlert(array $data): void
    {
        $this->ensureTables();
        $sql = "INSERT INTO grade_email_alert_log
                    (alert_key,batch_id,division_id,product_code,product_name,batch_no,current_grade,next_grade,
                     transition_date,alert_date,recipients,subject,status,error_message,sent_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    recipients=VALUES(recipients), subject=VALUES(subject), status=VALUES(status),
                    error_message=VALUES(error_message), sent_at=VALUES(sent_at), attempt_count=attempt_count+1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            $data['alert_key'],
            $data['batch_id'] ?: null,
            $data['division_id'] ?: null,
            $data['product_code'] ?? null,
            $data['product_name'] ?? null,
            $data['batch_no'] ?? null,
            $data['current_grade'] ?? null,
            $data['next_grade'] ?? null,
            $data['transition_date'] ?? null,
            $data['alert_date'] ?? null,
            $data['recipients'] ?? null,
            $data['subject'] ?? null,
            $data['status'],
            $data['error_message'] ?? null,
            $data['status'] === 'sent' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function recentAlerts(int $limit = 50): array
    {
        $this->ensureTables();
        $limit = max(1, min(200, $limit));
        $sql = "SELECT l.*,d.code division_code,d.name division_name
                FROM grade_email_alert_log l
                LEFT JOIN divisions d ON d.id=l.division_id
                ORDER BY l.id DESC LIMIT " . $limit;
        return Database::connection()->query($sql)->fetchAll();
    }

    public function updateRunStatus(string $status, string $summary): void
    {
        $this->ensureTables();
        $stmt = Database::connection()->prepare(
            'UPDATE grade_email_settings SET last_run_at=NOW(),last_run_status=?,last_run_summary=? WHERE id=1'
        );
        $stmt->execute([$status, substr($summary, 0, 500)]);
    }

    public function activeBatchesForAlerting(string $asOfDate): array
    {
        $sql = "SELECT sb.id,sb.division_id,sb.batch_no,sb.manufacture_date,sb.expiry_date,sb.current_qty,sb.stock_value,sb.uom,
                       m.material_code product_code,m.description product_name,
                       d.code division_code,d.name division_name
                FROM stock_batches sb
                JOIN materials m ON m.id=sb.material_id
                JOIN divisions d ON d.id=sb.division_id
                WHERE d.is_active=1 AND sb.current_qty>0 AND sb.expiry_date>=?
                ORDER BY d.code,m.description,sb.batch_no";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$asOfDate]);
        return $stmt->fetchAll();
    }

    public function validateEmailText(string $value): array
    {
        $invalid = [];
        foreach ($this->splitEmails($value) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $invalid[] = $email;
        }
        return $invalid;
    }

    private function canonicalEmails(string $value): string
    {
        return implode(', ', $this->uniqueValidEmails($this->splitEmails($value)));
    }

    private function splitEmails(string $value): array
    {
        $parts = preg_split('/[;,\r\n]+/', $value) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn(string $v): bool => $v !== ''));
    }

    private function uniqueValidEmails(array $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $email = strtolower(trim((string)$email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $out[$email] = $email;
        }
        return array_values($out);
    }
}
