<?php
namespace App\Services;

class SmtpMailer
{
    /** @var resource|null */
    private $socket = null;

    public function send(array $settings, array $recipients, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $recipients = $this->normalizeEmails($recipients);
        if (!$recipients) throw new \RuntimeException('No valid email recipients were supplied.');

        $host = trim((string)($settings['smtp_host'] ?? ''));
        $port = (int)($settings['smtp_port'] ?? 587);
        $encryption = strtolower(trim((string)($settings['smtp_encryption'] ?? 'tls')));
        $username = trim((string)($settings['smtp_username'] ?? ''));
        $password = (string)($settings['smtp_password'] ?? '');
        $fromEmail = trim((string)($settings['from_email'] ?? ''));
        $fromName = preg_replace('/[\r\n]+/', ' ', trim((string)($settings['from_name'] ?? 'Stock Aging Dashboard'))) ?: 'Stock Aging Dashboard';

        if ($host === '') throw new \RuntimeException('SMTP host is not configured.');
        if ($port < 1 || $port > 65535) throw new \RuntimeException('SMTP port is invalid.');
        if (!in_array($encryption, ['none','tls','ssl'], true)) throw new \RuntimeException('SMTP encryption setting is invalid.');
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('From email address is invalid.');

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$this->socket) {
            throw new \RuntimeException('SMTP connection failed: ' . ($errstr ?: ('error ' . $errno)));
        }

        stream_set_timeout($this->socket, 20);

        try {
            $this->expect([220]);
            $ehloName = gethostname() ?: 'localhost';
            $this->command('EHLO ' . $ehloName, [250]);

            if ($encryption === 'tls') {
                $this->command('STARTTLS', [220]);
                $crypto = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($crypto !== true) throw new \RuntimeException('SMTP STARTTLS negotiation failed.');
                $this->command('EHLO ' . $ehloName, [250]);
            }

            if ($username !== '') {
                $this->command('AUTH LOGIN', [334]);
                $this->command(base64_encode($username), [334]);
                $this->command(base64_encode($password), [235]);
            }

            $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
            foreach ($recipients as $recipient) {
                $this->command('RCPT TO:<' . $recipient . '>', [250, 251]);
            }

            $this->command('DATA', [354]);

            if ($textBody === '') {
                $textBody = trim(strip_tags(preg_replace('/<br\s*\/?\s*>/i', "\n", $htmlBody) ?? $htmlBody));
            }

            $boundary = '=_Qarshi_' . bin2hex(random_bytes(12));
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>',
                'To: ' . implode(', ', array_map(static fn(string $email): string => '<' . $email . '>', $recipients)),
                'Subject: ' . $this->encodeHeader($subject),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($this->domainFromEmail($fromEmail) ?: 'localhost') . '>',
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            ];

            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $this->normalizeLines($textBody) . "\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $this->normalizeLines($htmlBody) . "\r\n\r\n"
                . '--' . $boundary . "--\r\n";

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = preg_replace('/^\./m', '..', $message) ?? $message;
            fwrite($this->socket, $message . "\r\n.\r\n");
            $this->expect([250]);
            $this->command('QUIT', [221]);
        } finally {
            if (is_resource($this->socket)) fclose($this->socket);
            $this->socket = null;
        }
    }

    private function command(string $command, array $expectedCodes): string
    {
        if (!is_resource($this->socket)) throw new \RuntimeException('SMTP connection is not open.');
        fwrite($this->socket, $command . "\r\n");
        return $this->expect($expectedCodes);
    }

    private function expect(array $expectedCodes): string
    {
        if (!is_resource($this->socket)) throw new \RuntimeException('SMTP connection is not open.');

        $response = '';
        $code = 0;
        while (($line = fgets($this->socket, 8192)) !== false) {
            $response .= $line;
            if (preg_match('/^(\d{3})([ -])/', $line, $m)) {
                $code = (int)$m[1];
                if ($m[2] === ' ') break;
            }
        }

        $meta = stream_get_meta_data($this->socket);
        if (!empty($meta['timed_out'])) throw new \RuntimeException('SMTP server timed out.');
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('SMTP error ' . $code . ': ' . trim($response));
        }
        return trim($response);
    }

    private function normalizeEmails(array $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $email = strtolower(trim((string)$email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $out[$email] = $email;
        }
        return array_values($out);
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function normalizeLines(string $value): string
    {
        return preg_replace("~\r\n|\r|\n~", "\r\n", $value) ?? $value;
    }

    private function domainFromEmail(string $email): string
    {
        $pos = strrpos($email, '@');
        return $pos === false ? '' : substr($email, $pos + 1);
    }
}
