<?php
namespace App\Helpers;

class SecurityHelper {
    // Retourne / crée un token CSRF stocké en session
    public static function getToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function verifyToken(string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csrf_token'])) return false;
        return hash_equals($_SESSION['_csrf_token'], (string)$token);
    }
}
