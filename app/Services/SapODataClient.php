<?php
namespace App\Services;

class SapODataClient
{
    private array $settings;
    private ?string $accessToken = null;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP cURL extension is required for SAP integration.');
        }
    }

    public function testEndpoints(): array
    {
        $result = [];
        foreach ([
            'Product API' => (string)($this->settings['product_endpoint'] ?? ''),
            'Batch API' => (string)($this->settings['batch_endpoint'] ?? ''),
            'Material Stock API' => (string)($this->settings['stock_endpoint'] ?? ''),
        ] as $label => $endpoint) {
            if (trim($endpoint) === '') {
                throw new \RuntimeException($label . ' endpoint is not configured.');
            }
            $rows = $this->fetchAll($endpoint, 1, 1);
            $result[$label] = count($rows);
        }
        return $result;
    }

    public function fetchAll(string $endpoint, ?int $pageSize = null, ?int $maxPages = null): array
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') throw new \RuntimeException('SAP endpoint is empty.');

        $pageSize = $pageSize ?: max(1, min(5000, (int)($this->settings['page_size'] ?? 500)));
        $url = $this->absoluteUrl($endpoint);
        $url = $this->withTop($url, $pageSize);

        $rows = [];
        $pages = 0;

        while ($url !== '') {
            $pages++;
            if ($maxPages !== null && $pages > $maxPages) break;

            $payload = $this->getJson($url);
            [$pageRows, $next] = $this->extractRowsAndNext($payload, $url);

            foreach ($pageRows as $row) {
                if (is_array($row)) $rows[] = $row;
            }

            if ($maxPages !== null && $pages >= $maxPages) break;
            $url = $next;
        }

        return $rows;
    }

    private function withTop(string $url, int $pageSize): string
    {
        // Preserve any query already configured by the SAP team.
        if (preg_match('/(?:\?|&)\$top=/', $url)) return $url;
        return $url . (str_contains($url, '?') ? '&' : '?') . '$top=' . $pageSize;
    }

    private function getJson(string $url): array
    {
        $headers = ['Accept: application/json'];
        $authType = (string)($this->settings['auth_type'] ?? 'basic');

        if ($authType === 'oauth2') {
            $headers[] = 'Authorization: Bearer ' . $this->oauthToken();
        } elseif ($authType === 'api_key') {
            $key = (string)($this->settings['api_key'] ?? '');
            $header = (string)($this->settings['api_key_header'] ?? 'APIKey');
            if ($key === '') throw new \RuntimeException('SAP API key is not configured.');
            if (!preg_match('/^[A-Za-z0-9-]{1,80}$/', $header)) throw new \RuntimeException('Invalid SAP API key header.');
            if (preg_match('/[\r\n]/', $key)) throw new \RuntimeException('Invalid SAP API key.');
            $headers[] = $header . ': ' . $key;
        }

        $ch = curl_init($url);
        if ($ch === false) throw new \RuntimeException('Could not initialize SAP HTTP request.');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(30, (int)($this->settings['request_timeout_sec'] ?? 30)),
            CURLOPT_TIMEOUT => (int)($this->settings['request_timeout_sec'] ?? 30),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => !empty($this->settings['verify_ssl']),
            CURLOPT_SSL_VERIFYHOST => !empty($this->settings['verify_ssl']) ? 2 : 0,
            CURLOPT_HEADER => false,
        ]);

        if ($authType === 'basic') {
            $username = (string)($this->settings['username'] ?? '');
            $password = (string)($this->settings['password'] ?? '');
            if ($username === '') throw new \RuntimeException('SAP Basic Authentication username is not configured.');
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('SAP request failed: ' . $this->safeError($error));
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('SAP returned HTTP ' . $status . ' for the configured endpoint.');
        }

        $decoded = json_decode((string)$body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('SAP response is not valid JSON.');
        }

        return $decoded;
    }

    private function oauthToken(): string
    {
        if ($this->accessToken !== null) return $this->accessToken;

        $tokenUrl = trim((string)($this->settings['oauth_token_url'] ?? ''));
        $clientId = trim((string)($this->settings['client_id'] ?? ''));
        $clientSecret = (string)($this->settings['client_secret'] ?? '');

        if ($tokenUrl === '' || $clientId === '') {
            throw new \RuntimeException('OAuth 2.0 Token URL and Client ID are required.');
        }

        $ch = curl_init($tokenUrl);
        if ($ch === false) throw new \RuntimeException('Could not initialize SAP OAuth request.');

        $postFields = http_build_query(['grant_type' => 'client_credentials']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_CONNECTTIMEOUT => min(30, (int)($this->settings['request_timeout_sec'] ?? 30)),
            CURLOPT_TIMEOUT => (int)($this->settings['request_timeout_sec'] ?? 30),
            CURLOPT_SSL_VERIFYPEER => !empty($this->settings['verify_ssl']),
            CURLOPT_SSL_VERIFYHOST => !empty($this->settings['verify_ssl']) ? 2 : 0,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('SAP OAuth request failed: ' . $this->safeError($error));
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('SAP OAuth endpoint returned HTTP ' . $status . '.');
        }

        $decoded = json_decode((string)$body, true);
        $token = is_array($decoded) ? trim((string)($decoded['access_token'] ?? '')) : '';
        if ($token === '') throw new \RuntimeException('SAP OAuth response did not contain an access token.');

        return $this->accessToken = $token;
    }

    private function extractRowsAndNext(array $payload, string $currentUrl): array
    {
        $rows = [];
        $next = '';

        if (isset($payload['value']) && is_array($payload['value'])) {
            $rows = $payload['value'];
            $next = trim((string)($payload['@odata.nextLink'] ?? $payload['odata.nextLink'] ?? ''));
        } elseif (isset($payload['d']['results']) && is_array($payload['d']['results'])) {
            $rows = $payload['d']['results'];
            $next = trim((string)($payload['d']['__next'] ?? ''));
        } elseif (isset($payload['d']) && is_array($payload['d'])) {
            // Some SAP OData endpoints return one object under "d".
            $rows = [$payload['d']];
        } else {
            throw new \RuntimeException(
                'SAP endpoint returned JSON, but no OData collection was found. Configure a collection/entity-set URL.'
            );
        }

        if ($next !== '' && !preg_match('#^https?://#i', $next)) {
            $next = $this->absoluteUrl($next);
        }

        return [$rows, $next];
    }

    private function absoluteUrl(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if (preg_match('#^https?://#i', $endpoint)) return $endpoint;

        $baseUrl = rtrim(trim((string)($this->settings['base_url'] ?? '')), '/');
        if ($baseUrl === '') throw new \RuntimeException('SAP Base URL is not configured.');

        return $baseUrl . '/' . ltrim($endpoint, '/');
    }

    private function safeError(string $error): string
    {
        $error = trim($error);
        if ($error === '') return 'network/transport error';
        // Keep admin-facing error concise and prevent accidental credential leakage.
        return substr(preg_replace('#https?://\S+#i', '[SAP endpoint]', $error), 0, 220);
    }
}
