<?php

namespace App\Services;

class PaynowGateway {
    public static function buildMerchantReference(int $orderId, int $paymentId): string {
        return sprintf('AS-%d-%d', $orderId, max($paymentId, 1));
    }

    public static function mapStatus(?string $status): string {
        $normalized = strtolower(trim((string)$status));

        if (in_array($normalized, ['paid', 'awaiting delivery', 'delivered', 'ok'], true)) {
            return PAYMENT_STATUS_COMPLETED;
        }

        if (in_array($normalized, ['cancelled', 'failed', 'error', 'disputed'], true)) {
            return PAYMENT_STATUS_FAILED;
        }

        return PAYMENT_STATUS_PENDING;
    }

    public function isConfigured(): bool {
        return PAYNOW_INTEGRATION_ID !== '' && PAYNOW_INTEGRATION_KEY !== '';
    }

    public function generateHash(array $values): string {
        $string = '';

        foreach ($values as $key => $value) {
            if (strtoupper((string)$key) === 'HASH') {
                continue;
            }

            $string .= urldecode((string)$value);
        }

        return strtoupper(hash('sha512', $string . PAYNOW_INTEGRATION_KEY));
    }

    public function verifyHash(array $values): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        $incomingHash = $values['hash'] ?? $values['Hash'] ?? null;
        if (!$incomingHash) {
            return false;
        }

        return strtoupper((string)$incomingHash) === $this->generateHash($values);
    }

    public function initiate(array $payload): array {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('PayNow integration is not configured');
        }

        $request = [
            'id' => PAYNOW_INTEGRATION_ID,
            'reference' => $payload['reference'],
            'amount' => number_format((float)$payload['amount'], 2, '.', ''),
            'additionalinfo' => $payload['additionalInfo'] ?? '',
            'returnurl' => $payload['returnUrl'],
            'resulturl' => $payload['resultUrl'],
            'authemail' => $payload['email'] ?? '',
            'authphone' => $payload['phone'] ?? '',
            'authname' => $payload['name'] ?? '',
            'merchanttrace' => $payload['merchantTrace'] ?? '',
            'status' => 'Message',
        ];
        $request['hash'] = $this->generateHash($request);

        $response = $this->postForm(PAYNOW_INITIATE_URL, $request);
        if (!$this->verifyHash($response)) {
            throw new \RuntimeException('PayNow returned an invalid hash');
        }

        $status = strtolower((string)($response['status'] ?? ''));
        if ($status !== 'ok') {
            throw new \RuntimeException($response['error'] ?? 'PayNow rejected the transaction');
        }

        return $response;
    }

    public function poll(string $pollUrl): array {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('PayNow integration is not configured');
        }

        $response = $this->postForm($pollUrl, []);
        if (!$this->verifyHash($response)) {
            throw new \RuntimeException('PayNow returned an invalid poll hash');
        }

        return $response;
    }

    private function postForm(string $url, array $payload): array {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT => 20,
            ]);

            $rawResponse = curl_exec($ch);
            if ($rawResponse === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException('PayNow request failed: ' . $error);
            }

            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                throw new \RuntimeException('PayNow request failed with HTTP ' . $httpCode);
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $body,
                    'timeout' => 20,
                ],
            ]);

            $rawResponse = file_get_contents($url, false, $context);
            if ($rawResponse === false) {
                throw new \RuntimeException('PayNow request failed');
            }
        }

        parse_str((string)$rawResponse, $parsed);
        return array_change_key_case($parsed, CASE_LOWER);
    }
}
