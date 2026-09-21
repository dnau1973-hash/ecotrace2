<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class AuthController {
    
    // Gère la configuration initiale et la modification du fichier .env
    public function handleSetup() {
        $envFile = __DIR__ . '/../../.env';

        // Demande de réinitialisation
        if (isset($_GET['reset_config'])) {
            if (file_exists($envFile)) unlink($envFile);
            header("Location: ?"); 
            exit;
        }

        // Sauvegarde du formulaire de configuration
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_env_action'])) {
            $newEnv = "DB_HOST=\"" . addslashes($_POST['db_host']) . "\"\n"
                    . "DB_NAME=\"" . addslashes($_POST['db_name']) . "\"\n"
                    . "DB_USER=\"" . addslashes($_POST['db_user']) . "\"\n"
                    . "DB_PASS=\"" . addslashes($_POST['db_pass']) . "\"\n"
                    . "ADMIN_USER=\"" . addslashes($_POST['admin_user']) . "\"\n"
                    . "ADMIN_PASS=\"" . addslashes($_POST['admin_pass']) . "\"\n"
                    . "GITHUB_REPO=\"" . addslashes(trim($_POST['github_repo'] ?? '')) . "\"\n"
                    . "ORIGIN_LAT=" . (float)$_POST['origin_lat'] . "\n"
                    . "ORIGIN_LON=" . (float)$_POST['origin_lon'] . "\n";
            file_put_contents($envFile, $newEnv);
            
            // On recharge le nouvel environnement en mémoire
            Database::loadEnv();
            header("Location: ?"); 
            exit;
        }

        // Vérifie si on doit afficher l'écran de configuration
        $is_editing_config = (isset($_GET['edit_config']) && !empty($_SESSION['ecotrace_logged_in']));

        if (!file_exists($envFile) || $is_editing_config) {
            // Préparation des variables pour la Vue
            $db_host = Database::getEnv('DB_HOST', 'db');
            $db_name = Database::getEnv('DB_NAME', 'ecotrace');
            $db_user = Database::getEnv('DB_USER', 'root');
            $db_pass = Database::getEnv('DB_PASS', '');
            $admin_user = Database::getEnv('ADMIN_USER', 'admin');
            $admin_pass = Database::getEnv('ADMIN_PASS', 'admin');
            $github_repo = Database::getEnv('GITHUB_REPO', 'https://github.com/dnau1973-hash/ecotrace.git');
            $origineLat = Database::getEnv('ORIGIN_LAT', 45.19165526);
            $origineLon = Database::getEnv('ORIGIN_LON', 0.76262712);

            // On appelle la Vue HTML
            require __DIR__ . '/../Views/setup.php';
            exit; // On stoppe l'exécution ici tant que ce n'est pas configuré
        }
    }

    // Gère le formulaire de connexion et la déconnexion
    public function handleLogin() {
        if (isset($_GET['logout'])) { 
            session_destroy(); 
            header("Location: ?"); 
            exit; 
        }

        // Si l'utilisateur n'est pas connecté
        if (empty($_SESSION['ecotrace_logged_in'])) {
            $login_error = null;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
                $loginInput = trim($_POST['username'] ?? '');
                $passInput  = $_POST['password'] ?? '';

                // Tentative 1 : connexion via la table utilisateurs en base
                $authOk = false;
                try {
                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ? AND actif = 1 LIMIT 1");
                    $stmt->execute([$loginInput]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($passInput, $user['password_hash'])) {
                        // Mettre à jour la date de dernière connexion
                        $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")
                            ->execute([$user['id']]);

                        $_SESSION['ecotrace_logged_in']    = true;
                        $_SESSION['ecotrace_user_id']      = (int)$user['id'];
                        $_SESSION['ecotrace_user_login']   = $user['login'];
                        $_SESSION['ecotrace_user_nom']     = trim($user['prenom'] . ' ' . $user['nom']);
                        $_SESSION['ecotrace_user_role']    = $user['role'];
                        $_SESSION['ecotrace_user_societes'] = $user['societe_ids'] ? json_decode($user['societe_ids'], true) : null;
                        $authOk = true;
                    }
                } catch (\Throwable $e) {
                    // La table n'existe pas encore → on passe au fallback .env
                }

                // Tentative 2 (fallback) : identifiants .env
                if (!$authOk) {
                    $admin_user = Database::getEnv('ADMIN_USER', 'admin');
                    $admin_pass = Database::getEnv('ADMIN_PASS', 'admin');

                    if ($loginInput === $admin_user && $passInput === $admin_pass) {
                        $_SESSION['ecotrace_logged_in']    = true;
                        $_SESSION['ecotrace_user_id']      = 0;
                        $_SESSION['ecotrace_user_login']   = $admin_user;
                        $_SESSION['ecotrace_user_nom']     = 'Administrateur';
                        $_SESSION['ecotrace_user_role']    = 'superadmin';
                        $_SESSION['ecotrace_user_societes'] = null;
                        $authOk = true;
                    }
                }

                if ($authOk) {
                    header("Location: ?"); 
                    exit;
                } else {
                    $login_error = "Identifiants incorrects.";
                }
            }

            // On appelle la Vue HTML de connexion
            require __DIR__ . '/../Views/login.php';
            exit; // On stoppe l'exécution ici tant que ce n'est pas logué
        }
    }

    /**
     * Vérifie si l'utilisateur connecté est superadmin
     */
    public static function isSuperAdmin(): bool {
        return ($_SESSION['ecotrace_user_role'] ?? '') === 'superadmin';
    }

    /**
     * Vérifie si l'utilisateur connecté est admin ou superadmin
     */
    public static function isAdmin(): bool {
        return in_array($_SESSION['ecotrace_user_role'] ?? '', ['superadmin', 'admin']);
    }

    /**
     * Vérifie si l'utilisateur a accès à une société donnée
     */
    public static function canAccessSociete(int $societeId): bool {
        if (self::isSuperAdmin()) return true;
        $allowed = $_SESSION['ecotrace_user_societes'] ?? null;
        if ($allowed === null) return true; // null = toutes
        return in_array($societeId, (array)$allowed);
    }
}