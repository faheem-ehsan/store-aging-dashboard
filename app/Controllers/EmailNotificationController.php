<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\GradeEmailNotification;
use App\Services\AuditLogger;
use App\Services\GradeAlertService;

class EmailNotificationController
{
    public function index(): void
    {
        $user = Auth::requireSuperAdmin();
        $model = new GradeEmailNotification();
        $model->ensureTables();

        $phpCli = PHP_BINDIR . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
        if (!is_file($phpCli)) $phpCli = PHP_BINARY;

        View::render('admin/email_notifications', [
            'title' => 'Grade Email Alerts',
            'user' => $user,
            'settings' => $model->settings(),
            'divisions' => $model->divisionsWithRecipients(),
            'recentAlerts' => $model->recentAlerts(50),
            'schedulerCommand' => '"' . $phpCli . '" "' . ROOT_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'send_grade_alerts.php' . '"',
        ]);
    }

    public function save(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $model = new GradeEmailNotification();
        $model->ensureTables();

        $port = (int)($_POST['smtp_port'] ?? 587);
        $encryption = strtolower(trim((string)($_POST['smtp_encryption'] ?? 'tls')));
        $fromEmail = strtolower(trim((string)($_POST['from_email'] ?? '')));
        $global = trim((string)($_POST['global_recipients'] ?? ''));
        $testMode = isset($_POST['test_mode']);
        $testRecipient = strtolower(trim((string)($_POST['test_recipient'] ?? '')));

        if ($port < 1 || $port > 65535) {
            flash('error', 'SMTP port must be between 1 and 65535.');
            redirect('/admin/email-notifications');
        }
        if (!in_array($encryption, ['none','tls','ssl'], true)) {
            flash('error', 'Select a valid SMTP encryption method.');
            redirect('/admin/email-notifications');
        }
        if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'From Email is not valid.');
            redirect('/admin/email-notifications');
        }
        if ($testMode && !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid Test Recipient email while Test Mode is enabled.');
            redirect('/admin/email-notifications');
        }

        $invalidGlobal = $model->validateEmailText($global);
        if ($invalidGlobal) {
            flash('error', 'Invalid Value Chain / Supply Marketing email: ' . $invalidGlobal[0]);
            redirect('/admin/email-notifications');
        }

        $divisionEmails = $_POST['division_recipients'] ?? [];
        if (!is_array($divisionEmails)) $divisionEmails = [];
        foreach ($divisionEmails as $divisionId => $emails) {
            $invalid = $model->validateEmailText((string)$emails);
            if ($invalid) {
                flash('error', 'Invalid Division Head email: ' . $invalid[0]);
                redirect('/admin/email-notifications');
            }
        }

        $model->saveSettings([
            'is_enabled' => isset($_POST['is_enabled']),
            'include_division_admins' => isset($_POST['include_division_admins']),
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $port,
            'smtp_encryption' => $encryption,
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'from_email' => $fromEmail,
            'from_name' => $_POST['from_name'] ?? '',
            'global_recipients' => $global,
            'test_mode' => $testMode,
            'test_recipient' => $testRecipient,
        ], (int)$user['id']);
        $model->saveDivisionRecipients($divisionEmails, (int)$user['id']);

        AuditLogger::log(
            'GRADE_EMAIL_SETTINGS_UPDATE',
            'Updated grade email alert settings and recipients.',
            'grade_email_settings',
            1,
            [
                'enabled' => isset($_POST['is_enabled']),
                'include_division_admins' => isset($_POST['include_division_admins']),
                'test_mode' => $testMode,
                'test_recipient' => $testRecipient,
            ],
            $user
        );

        flash('success', 'Grade email alert settings saved.');
        redirect('/admin/email-notifications');
    }

    public function test(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $email = strtolower(trim((string)($_POST['test_email'] ?? '')));
        try {
            (new GradeAlertService())->sendTest($email);
            AuditLogger::log('GRADE_EMAIL_TEST', 'Sent SMTP test email to ' . $email . '.', 'grade_email_settings', 1, [], $user);
            flash('success', 'Test email sent successfully to ' . $email . '.');
        } catch (\Throwable $e) {
            flash('error', 'Test email failed: ' . $e->getMessage());
        }
        redirect('/admin/email-notifications');
    }

    public function sample(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        $email = strtolower(trim((string)($_POST['test_email'] ?? '')));
        try {
            (new GradeAlertService())->sendSampleGradeAlert($email);
            AuditLogger::log('GRADE_EMAIL_SAMPLE_TEST', 'Sent sample grade alert email to ' . $email . '.', 'grade_email_settings', 1, [], $user);
            flash('success', 'Sample grade alert sent successfully to ' . $email . '.');
        } catch (\Throwable $e) {
            flash('error', 'Sample grade alert failed: ' . $e->getMessage());
        }
        redirect('/admin/email-notifications');
    }

    public function runNow(): void
    {
        verify_csrf();
        $user = Auth::requireSuperAdmin();
        try {
            $result = (new GradeAlertService())->run(new \DateTimeImmutable('today'));
            $message = 'Grade alert check completed. Due: ' . $result['due_batches']
                . ', sent: ' . $result['sent_batches'] . ', failed: ' . $result['failed_batches'] . '.';
            AuditLogger::log('GRADE_EMAIL_CHECK_RUN', $message, 'grade_email_settings', 1, $result, $user);
            flash('success', $message);
        } catch (\Throwable $e) {
            flash('error', 'Grade alert check failed: ' . $e->getMessage());
        }
        redirect('/admin/email-notifications');
    }
}
