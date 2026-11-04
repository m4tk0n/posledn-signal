<?php
declare(strict_types=1);

namespace App\Security;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('LASTSIGNAL_SESSION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new \RuntimeException('Session could not be started.');
        }

        if (!isset($_SESSION['_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['_initialized'] = true;
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }
}
