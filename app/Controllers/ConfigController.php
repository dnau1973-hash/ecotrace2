<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class ConfigController {
    
    // Chemin vers le fichier .env à la racine du projet
    private $envPath = __DIR__ . '/../../.env';

    // Affiche le formulaire pré-rempli
    public function showForm() {
        $envVars = [];
        
        // Lecture du fichier .env
        if (file_exists($this->envPath)) {
            $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // Ignorer les commentaires
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $envVars[trim($parts[0])] = trim($parts[1]);
                }
            }
        }

        ob_start();
        require __DIR__ . '/../Views/config.php';
        $content = ob_get_clean();
        
        // Récupération des compteurs pour maintenir le menu intact
        $countEnAttente = 0; $countIntrouvables = 0; $countValides = 0;
        try {
            $pdo = Database::getConnection();
            $countEnAttente = $pdo->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'en_attente'")->fetchColumn();
            $countIntrouvables = $pdo->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'introuvable'")->fetchColumn();
            $countValides = $pdo->query("SELECT COUNT(*) FROM sources_csv WHERE statut LIKE 'valide%'")->fetchColumn();
        } catch (\Exception $e) {}
        
        require __DIR__ . '/../Views/layout.php';
    }

    // Sauvegarde les modifications dans le fichier .env
    public function save() {
        if (!isset($_POST['env']) || !is_array($_POST['env'])) {
            header("Location: ?edit_config=1");
            exit;
        }

        $content = "";
        foreach ($_POST['env'] as $key => $value) {
            $cleanKey = strtoupper(trim($key));
            $cleanValue = trim($value);
            $content .= $cleanKey . "=" . $cleanValue . "\n";
        }
        
        file_put_contents($this->envPath, $content);
        
        // Redirection avec un paramètre de succès
        header("Location: ?edit_config=1&success=1");
        exit;
    }
}