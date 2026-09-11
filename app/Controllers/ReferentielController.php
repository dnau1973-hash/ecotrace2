<?php
namespace App\Controllers;
use App\Config\Database;
use PDO;

class ReferentielController {
    public function manage() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM referentiel_vehicules ORDER BY marque ASC, modele ASC");
        $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtMarques = $db->query("SELECT DISTINCT marque FROM referentiel_vehicules WHERE marque != '' ORDER BY marque ASC");
        $marquesExistantes = $stmtMarques->fetchAll(PDO::FETCH_COLUMN);

        // Fetch societes for header
        $stmtSoc = $db->query("SELECT * FROM societes ORDER BY nom ASC");
        $societes = $stmtSoc->fetchAll(PDO::FETCH_ASSOC);
        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';
        
        $countEnAttente = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'en_attente'")->fetchColumn();
        $countValides = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut IN ('valide_auto', 'valide_manuel')")->fetchColumn();
        $countIntrouvables = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'introuvable'")->fetchColumn();

        ob_start();
        require __DIR__ . '/../Views/admin/manage_referentiel.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $marque = trim($_POST['marque'] ?? '');
            $modele = trim($_POST['modele'] ?? '');
            $carburant = $_POST['carburant'] ?? '';
            $conso = (float)str_replace(',', '.', $_POST['conso_moyenne'] ?? 0);

            if ($marque && $modele && $carburant && $conso > 0) {
                if ($id) {
                    $stmt = $db->prepare("UPDATE referentiel_vehicules SET marque=?, modele=?, carburant=?, conso_moyenne=? WHERE id=?");
                    $stmt->execute([$marque, $modele, $carburant, $conso, $id]);
                    $_SESSION['flash_message'] = "Véhicule mis à jour.";
                } else {
                    $stmt = $db->prepare("INSERT INTO referentiel_vehicules (marque, modele, carburant, conso_moyenne) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$marque, $modele, $carburant, $conso]);
                    $_SESSION['flash_message'] = "Nouveau véhicule ajouté au référentiel.";
                }
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Veuillez remplir correctement tous les champs.";
                $_SESSION['flash_type'] = "danger";
            }
        }
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '?action=manage_referentiel';
        header("Location: " . $redirect);
        exit;
    }

    public function estimateConsoAjax() {
        $marque = trim($_GET['marque'] ?? '');
        $modele = trim($_GET['modele'] ?? '');
        $carburant = trim($_GET['carburant'] ?? '');

        if (empty($marque) || empty($modele) || empty($carburant)) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            exit;
        }

        // Requête DuckDuckGo Lite (HTML brut, pas de JS requis)
        $query = urlencode("consommation mixte WLTP " . $marque . " " . $modele . " " . $carburant . " L/100km");
        $url = "https://html.duckduckgo.com/html/?q=" . $query;
        
        // Configuration du contexte pour simuler un navigateur
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                            "Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $html = @file_get_contents($url, false, $context);

        if (!$html) {
            echo json_encode(['success' => false, 'message' => 'Impossible d\'effectuer la recherche internet.']);
            exit;
        }

        // On cherche un pattern du type "X,X L/100" ou "X.X l/100km" dans les snippets de résultats
        // Expression régulière : un ou deux chiffres, une virgule ou point, un chiffre, puis optionnellement des espaces et "L/100"
        $pattern = '/\b(\d{1,2}[.,]\d{1})\s*(?:l|L)\/100/i';
        if (preg_match_all($pattern, $html, $matches)) {
            // Extraire la première valeur trouvée
            $valeursStr = $matches[1];
            // Normaliser en float
            $valeursFloat = array_map(function($v) {
                return (float)str_replace(',', '.', $v);
            }, $valeursStr);
            
            // On peut prendre la première occurrence, ou faire une moyenne si plusieurs. Prenons la plus fréquente ou première.
            $estimation = $valeursFloat[0];
            
            echo json_encode([
                'success' => true, 
                'estimation' => $estimation,
                'source' => 'Recherche Web (DuckDuckGo)'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucune donnée de consommation trouvée sur internet.']);
        }
        exit;
    }

    public function delete() {
        if (isset($_GET['id'])) {
            $db = Database::getConnection();
            // Prevent deleting if it is used in flotte_vehicules? 
            // In our current design, referential copies its data into the fleet, so it's safe to delete from referential.
            $stmt = $db->prepare("DELETE FROM referentiel_vehicules WHERE id=?");
            $stmt->execute([(int)$_GET['id']]);
            $_SESSION['flash_message'] = "Véhicule supprimé du référentiel.";
            $_SESSION['flash_type'] = "success";
        }
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '?action=manage_referentiel';
        header("Location: " . $redirect);
        exit;
    }
}
