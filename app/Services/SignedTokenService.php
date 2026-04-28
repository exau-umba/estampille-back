<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use RuntimeException;

class SignedTokenService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 4;
    private const CODE_SPACE_SIZE = 1048576; // 32^4

    public function issue(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'ESTQ'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true);

        return $encodedHeader.'.'.$encodedPayload.'.'.$this->base64UrlEncode($signature);
    }

    public function issueCompactToken(int $bytes = 12): string
    {
        if ($bytes < 8) {
            $bytes = 8;
        }

        return $this->base64UrlEncode(random_bytes($bytes));
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->secret(), true)
        );

        if (!hash_equals($expected, $encodedSignature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && Carbon::now()->timestamp > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    public function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function nextCode(): string
    {
        $length = strlen(self::CODE_ALPHABET);
        $output = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $output .= self::CODE_ALPHABET[random_int(0, $length - 1)];
        }

        return $output;
    }

    public function codeFromSerial(string $batchId, int $serial): string
    {
        if ($serial < 1 || $serial > self::CODE_SPACE_SIZE) {
            throw new RuntimeException('Serial is out of 4-char code space.');
        }

        $x = $serial - 1;
        $key = hash('sha256', $batchId, true);
        $a = unpack('N', substr($key, 0, 4))[1] ?? 1;
        $b = unpack('N', substr($key, 4, 4))[1] ?? 0;

        // Ensure "a" is odd to keep a bijection modulo 2^20.
        $a = ($a | 1) % self::CODE_SPACE_SIZE;
        if ($a === 0) {
            $a = 1;
        }

        $value = (($a * $x) + $b) % self::CODE_SPACE_SIZE;

        return $this->encodeBase32Fixed((int) $value);
    }

    private function secret(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('APP_KEY is missing.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('APP_KEY base64 format is invalid.');
            }

            return $decoded;
        }

        return $key;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private function encodeBase32Fixed(int $value): string
    {
        $alphabet = self::CODE_ALPHABET;
        $base = strlen($alphabet);
        $chars = array_fill(0, self::CODE_LENGTH, $alphabet[0]);

        for ($i = self::CODE_LENGTH - 1; $i >= 0; $i--) {
            $index = $value % $base;
            $chars[$i] = $alphabet[$index];
            $value = intdiv($value, $base);
        }

        return implode('', $chars);
    }
}
