<?php
namespace App\Services;

use App\Models\GradeEmailNotification;

class GradeAlertService
{
    private GradeEmailNotification $model;
    private GradeService $grader;
    private SmtpMailer $mailer;

    public function __construct()
    {
        $this->model = new GradeEmailNotification();
        $this->grader = new GradeService();
        $this->mailer = new SmtpMailer();
    }

    public function run(?\DateTimeImmutable $asOf = null): array
    {
        $asOf = $asOf ?? new \DateTimeImmutable('today');
        $today = $asOf->format('Y-m-d');
        $settings = $this->model->mailerSettings();

        $testMode = !empty($settings['test_mode']);
        $testRecipient = strtolower(trim((string)($settings['test_recipient'] ?? '')));

        $summary = [
            'enabled' => !empty($settings['is_enabled']),
            'test_mode' => $testMode,
            'test_recipient' => $testMode ? $testRecipient : '',
            'date' => $today,
            'due_batches' => 0,
            'sent_batches' => 0,
            'sent_emails' => 0,
            'skipped_already_sent' => 0,
            'failed_batches' => 0,
            'failed_divisions' => [],
        ];

        if (empty($settings['is_enabled'])) {
            $this->model->updateRunStatus('disabled', 'Grade email alerts are disabled.');
            return $summary;
        }

        if ($testMode && !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Test Mode is enabled but Test Recipient is not configured.');
        }

        $globalRecipients = $this->model->globalRecipients($settings);
        $divisionRecipients = $this->model->recipientMap(!empty($settings['include_division_admins']));
        $groups = [];

        foreach ($this->model->activeBatchesForAlerting($today) as $batch) {
            try {
                $transition = $this->grader->nextTransitionForBatch($batch, $asOf);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$transition || $transition['alert_date'] !== $today) continue;

            $alertKey = implode('|', [
                'grade1d',
                (int)$batch['id'],
                $transition['next_grade'],
                $transition['transition_date'],
            ]);

            if ($this->model->isSent($alertKey)) {
                $summary['skipped_already_sent']++;
                continue;
            }

            $summary['due_batches']++;
            $divisionId = (int)$batch['division_id'];
            $batch['transition'] = $transition;
            $batch['alert_key'] = $alertKey;
            $groups[$divisionId][] = $batch;
        }

        foreach ($groups as $divisionId => $batches) {
            $first = $batches[0];
            $productionRecipients = array_values(array_unique(array_merge(
                $divisionRecipients[$divisionId] ?? [],
                $globalRecipients
            )));
            $recipients = $testMode ? [$testRecipient] : $productionRecipients;

            $subject = ($testMode ? '[TEST] ' : '') . '[Stock Aging Alert] ' . $first['division_code'] . ' — ' . count($batches)
                . ' batch' . (count($batches) === 1 ? '' : 'es') . ' change grade tomorrow';

            if (!$recipients) {
                $error = 'No Division Head or Value Chain / Supply Marketing recipient is configured.';
                foreach ($batches as $batch) $this->record($batch, [], $subject, 'failed', $error, $today);
                $summary['failed_batches'] += count($batches);
                $summary['failed_divisions'][] = $first['division_code'];
                continue;
            }

            [$html, $plain] = $this->buildMessage($first, $batches, $today, $testMode, $productionRecipients);

            try {
                $this->mailer->send($settings, $recipients, $subject, $html, $plain);
                foreach ($batches as $batch) $this->record($batch, $recipients, $subject, 'sent', null, $today);
                $summary['sent_batches'] += count($batches);
                $summary['sent_emails']++;
            } catch (\Throwable $e) {
                $error = substr($e->getMessage(), 0, 1500);
                foreach ($batches as $batch) $this->record($batch, $recipients, $subject, 'failed', $error, $today);
                $summary['failed_batches'] += count($batches);
                $summary['failed_divisions'][] = $first['division_code'];
            }
        }

        $status = $summary['failed_batches'] > 0 ? 'partial' : 'completed';
        $text = 'Due ' . $summary['due_batches'] . ', sent ' . $summary['sent_batches']
            . ', emails ' . $summary['sent_emails'] . ', failed ' . $summary['failed_batches'] . '.';
        $this->model->updateRunStatus($status, $text);
        return $summary;
    }

    public function sendTest(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Test email address is invalid.');
        $settings = $this->model->mailerSettings();
        $subject = '[Stock Aging Dashboard] SMTP Test';
        $html = '<div style="font-family:Arial,sans-serif;color:#172033">'
            . '<h2 style="margin:0 0 12px;color:#8f1117">Email Notification Test</h2>'
            . '<p>Your Stock Aging Dashboard SMTP configuration is working.</p>'
            . '<p style="color:#667085;font-size:12px">Sent ' . htmlspecialchars(format_app_datetime(date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8') . '</p>'
            . '</div>';
        $this->mailer->send($settings, [$email], $subject, $html, 'Your Stock Aging Dashboard SMTP configuration is working.');
    }

    public function sendSampleGradeAlert(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Test email address is invalid.');
        }

        $settings = $this->model->mailerSettings();
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $expiry = date('Y-m-d', strtotime('+90 days'));

        $division = [
            'division_code' => 'TEST',
            'division_name' => 'Sample Division',
        ];
        $batch = [
            'product_code' => 'TEST-1001',
            'product_name' => 'Sample Product',
            'batch_no' => 'TEST-BATCH-01',
            'current_qty' => 1250,
            'uom' => 'CS',
            'stock_value' => 2450000,
            'expiry_date' => $expiry,
            'transition' => [
                'current_grade' => 'A',
                'next_grade' => 'B',
                'transition_date' => $tomorrow,
            ],
        ];

        [$html, $plain] = $this->buildMessage($division, [$batch], $today, true, ['division.head@example.com', 'valuechain@example.com']);
        $subject = '[TEST] [Stock Aging Alert] Sample Division — 1 batch changes grade tomorrow';
        $this->mailer->send($settings, [$email], $subject, $html, $plain);
    }

    private function buildMessage(array $division, array $batches, string $today, bool $testMode = false, array $productionRecipients = []): array
    {
        $rows = '';
        $plainRows = [];

        foreach ($batches as $batch) {
            $t = $batch['transition'];
            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h($batch['product_code']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h($batch['product_name']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h($batch['batch_no']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h($t['current_grade']) . ' → ' . $this->h($t['next_grade']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee;text-align:right">' . $this->h($this->number($batch['current_qty'])) . ' ' . $this->h($batch['uom']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee;text-align:right">Rs/ ' . $this->h($this->number($batch['stock_value'], 2)) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h(format_app_date($t['transition_date'])) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e7e9ee">' . $this->h(format_app_date($batch['expiry_date'])) . '</td>'
                . '</tr>';

            $plainRows[] = $batch['product_code'] . ' | ' . $batch['product_name'] . ' | Batch ' . $batch['batch_no']
                . ' | ' . $t['current_grade'] . ' -> ' . $t['next_grade']
                . ' | Qty ' . $this->number($batch['current_qty']) . ' ' . $batch['uom']
                . ' | Transition ' . format_app_date($t['transition_date']);
        }

        $html = '<div style="font-family:Arial,sans-serif;color:#172033;max-width:1050px;margin:auto">'
            . '<div style="border:1px solid #e4e7ec;border-radius:12px;overflow:hidden">'
            . '<div style="background:#8f1117;color:#fff;padding:18px 20px">'
            . '<div style="font-size:11px;letter-spacing:.08em;font-weight:700">GRADE CHANGE ALERT · 1 DAY BEFORE</div>'
            . '<h2 style="margin:5px 0 0;font-size:20px">' . $this->h($division['division_code'] . ' — ' . $division['division_name']) . '</h2>'
            . '</div>'
            . '<div style="padding:16px 20px">'
            . ($testMode
                ? '<div style="margin:0 0 14px;padding:10px 12px;border:1px solid #f5c451;background:#fff8df;border-radius:8px;color:#8a5a00;font-size:12px"><strong>TEST MODE</strong> — Production recipients are suppressed. Intended recipients: ' . $this->h($productionRecipients ? implode(', ', $productionRecipients) : 'not configured') . '</div>'
                : '')
            . '<p style="margin:0 0 14px;color:#475467">The following stock batches are scheduled to move to the next grade tomorrow based on the configured division grading criteria.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:12px">'
            . '<thead><tr style="background:#fff1f2;color:#8f1117;text-align:left">'
            . '<th style="padding:8px">Product Code</th><th style="padding:8px">Product</th><th style="padding:8px">Batch</th>'
            . '<th style="padding:8px">Grade Change</th><th style="padding:8px;text-align:right">Quantity</th>'
            . '<th style="padding:8px;text-align:right">Stock Value</th><th style="padding:8px">Change Date</th><th style="padding:8px">Expiry</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p style="margin:16px 0 0;font-size:11px;color:#98a2b3">Stock Aging Dashboard · Alert date ' . $this->h(format_app_date($today)) . '</p>'
            . '</div></div></div>';

        $plain = ($testMode ? 'TEST MODE - PRODUCTION RECIPIENTS SUPPRESSED' . "\n" : '')
            . 'GRADE CHANGE ALERT - 1 DAY BEFORE' . "\n"
            . $division['division_code'] . ' - ' . $division['division_name'] . "\n\n"
            . 'The following batches move to the next grade tomorrow:' . "\n"
            . implode("\n", $plainRows);

        return [$html, $plain];
    }

    private function record(array $batch, array $recipients, string $subject, string $status, ?string $error, string $today): void
    {
        $t = $batch['transition'];
        $this->model->recordAlert([
            'alert_key' => $batch['alert_key'],
            'batch_id' => (int)$batch['id'],
            'division_id' => (int)$batch['division_id'],
            'product_code' => $batch['product_code'],
            'product_name' => $batch['product_name'],
            'batch_no' => $batch['batch_no'],
            'current_grade' => $t['current_grade'],
            'next_grade' => $t['next_grade'],
            'transition_date' => $t['transition_date'],
            'alert_date' => $today,
            'recipients' => implode(', ', $recipients),
            'subject' => $subject,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    private function h(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    private function number(mixed $value, int $maxDecimals = 3): string
    {
        $num = (float)$value;
        $formatted = number_format($num, $maxDecimals, '.', ',');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}
