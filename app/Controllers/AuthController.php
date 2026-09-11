<?php
namespace App\Controllers;

use App\Config\Database;

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
                $admin_user = Database::getEnv('ADMIN_USER', 'admin');
                $admin_pass = Database::getEnv('ADMIN_PASS', 'admin');

                if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
                    $_SESSION['ecotrace_logged_in'] = true; 
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
}