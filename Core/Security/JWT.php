<?php
namespace Core\Security;

class JWT
{
    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;

        $b64 = fn($data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        $h = $b64($header);
        $p = $b64($payload);
        $signature = hash_hmac('sha256', "$h.$p", $secret, true);
        $s = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$h.$p.$s";
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$h, $p, $s] = $parts;
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "$h.$p", $secret, true)), '+/', '-_'), '=');

        if (!hash_equals($expected, $s)) return null;

        $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) return null;

        return $payload;
    }
}