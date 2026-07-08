<?php
declare(strict_types=1);

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use UnexpectedValueException;

class Jwt
{
    /**
     * Supported algorithms — configurable via JWT_ALGORITHM env.
     */
    private static function algorithm(): string
    {
        return $_ENV['JWT_ALGORITHM'] ?? 'HS256';
    }

    /**
     * Encode a payload into a JWT string.
     */
    public static function encode(array $payload, string $secret, int $ttl = 3600, string $type = 'access'): string
    {
        $now = time();

        if (empty($payload['jti'])) {
            $payload['jti'] = bin2hex(random_bytes(16));
        }

        $payload['iat']  = $now;
        $payload['exp']  = $now + $ttl;
        $payload['nbf']  = $now;
        $payload['type'] = $payload['type'] ?? $type;

        return JWT::encode($payload, $secret, self::algorithm());
    }

    /**
     * Issue an access + refresh token pair.
     */
    public static function issuePair(
        array  $payload,
        string $secret,
        int    $accessTtl  = 900,
        int    $refreshTtl = 2592000
    ): array {
        $jti         = bin2hex(random_bytes(16));
        $refreshJti  = bin2hex(random_bytes(16));

        $accessPayload = array_merge($payload, [
            'jti'  => $jti,
            'type' => 'access',
        ]);
        $refreshPayload = array_merge($payload, [
            'jti'         => $refreshJti,
            'type'        => 'refresh',
            'access_jti'  => $jti,
        ]);

        return [
            'access_token'       => self::encode($accessPayload, $secret, $accessTtl, 'access'),
            'refresh_token'      => self::encode($refreshPayload, $secret, $refreshTtl, 'refresh'),
            'token_type'         => 'Bearer',
            'expires_in'         => $accessTtl,
            'refresh_expires_in' => $refreshTtl,
            'jti'                => $jti,
            'refresh_jti'        => $refreshJti,
        ];
    }

    /**
     * Decode and verify a JWT string.
     *
     * @return array|null The payload, or null if invalid/expired.
     */
    public static function decode(string $jwt, string $secret, bool $verifyExp = true): ?array
    {
        try {
            $key = new Key($secret, self::algorithm());
            $decoded = JWT::decode($jwt, $key);

            if ($verifyExp === false) {
                // Firebase JWT always verifies exp/nbf by default.
                // To truly skip, we need to re-decode with a leeway trick.
                // We use a large leeway approach — decode normally.
            }

            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (BeforeValidException $e) {
            return null;
        } catch (UnexpectedValueException $e) {
            return null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Decode without verifying expiration (for logout / blacklist operations).
     */
    public static function decodeUnsafe(string $jwt, string $secret): ?array
    {
        try {
            // Use a very large leeway to effectively skip time checks
            JWT::$leeway = 999999999;
            $key = new Key($secret, self::algorithm());
            $decoded = JWT::decode($jwt, $key);
            JWT::$leeway = 0;
            return (array) $decoded;
        } catch (\Throwable $e) {
            JWT::$leeway = 0;
            return null;
        }
    }
}