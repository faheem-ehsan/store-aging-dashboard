<?php
namespace App\Services;

class SapCredentialCipher
{
    private string $key;

    public function __construct()
    {
        $secretFile = ROOT_PATH . '/config/sap_secret.php';
        $secret = is_file($secretFile) ? (string)require $secretFile : '';
        if ($secret === '') {
            throw new \RuntimeException('SAP credential encryption key is missing.');
        }
        $this->key = hash('sha256', $secret, true);
    }

    public function encrypt(string $plain): string
    {
        if ($plain === '') return '';
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL extension is required to store SAP credentials securely.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(
            $plain,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($cipher === false) {
            throw new \RuntimeException('Could not encrypt SAP credential.');
        }

        return 'v1:' . base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(?string $encrypted): string
    {
        $encrypted = trim((string)$encrypted);
        if ($encrypted === '') return '';
        if (!str_starts_with($encrypted, 'v1:')) {
            throw new \RuntimeException('Unsupported SAP credential format.');
        }

        $raw = base64_decode(substr($encrypted, 3), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Stored SAP credential is invalid.');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);

        $plain = openssl_decrypt(
            $cipher,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plain === false) {
            throw new \RuntimeException('Could not decrypt SAP credential.');
        }

        return $plain;
    }
}
