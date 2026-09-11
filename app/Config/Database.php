<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $pdo = null;
    private static $env = [];

    // Charge le fichier .env en mémoire
    public static function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            self::$env = parse_ini_file($envFile);
        }
    }

    // Récupère une variable d'environnement avec une valeur par défaut
    public static function getEnv($key, $default = null) {
        if (empty(self::$env)) {
            self::loadEnv();
        }
        $val = self::$env[$key] ?? $default;
        if (($key === 'ORIGIN_LAT' || $key === 'ORIGIN_LON') && (empty($val) || (float)$val == 0) && $default !== null) {
            return $default;
        }
        return $val;
    }

    // Fournit la connexion PDO (Singleton)
    public static function getConnection() {
        if (self::$pdo === null) {
            $db_host = self::getEnv('DB_HOST', 'db');
            $db_name = self::getEnv('DB_NAME', 'ecotrace');
            $db_user = self::getEnv('DB_USER', 'root');
            $db_pass = self::getEnv('DB_PASS', '');

            try {
                // On se connecte d'abord au serveur sans spécifier la base (pour pouvoir la créer plus tard)
                self::$pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Si la base existe, on l'utilise
                $stmt = self::$pdo->query("SHOW DATABASES LIKE '$db_name'");
                if ($stmt->fetch()) {
                    self::$pdo->exec("USE `$db_name`");
                }
            } catch (PDOException $e) {
                die("Erreur critique MySQL : " . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$pdo;
    }
}