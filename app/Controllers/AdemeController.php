<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class AdemeController {
    
    private function ensureTableExists($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ademe_facteurs (
                code_naf VARCHAR(10) PRIMARY KEY,
                facteur DECIMAL(10,4) DEFAULT 0.2000,
                mis_a_jour_le DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        $stmt = $pdo->query("SELECT code FROM codes_naf WHERE code NOT IN (SELECT code_naf FROM ademe_facteurs)");
        $missingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($missingCodes) {
            $insert = $pdo->prepare("INSERT INTO ademe_facteurs (code_naf, facteur) VALUES (?, ?)");
            $pdo->beginTransaction();
            foreach ($missingCodes as $code) {
                $div = substr($code, 0, 2);
                $facteur = 0.2000;
                if (in_array($div, ['10','11','56'])) $facteur = 0.4500;
                elseif (in_array($div, ['49','50','51','52'])) $facteur = 0.8000;
                elseif (in_array($div, ['61','62','63','69','70','71'])) $facteur = 0.0500;
                $insert->execute([$code, $facteur]);
            }
            $pdo->commit();
        }
    }

    public function manage() {
        $db = Database::getConnection();
        $this->ensureTableExists($db);
        
        $stmt = $db->query("
            SELECT c.code, c.libelle, a.facteur, a.mis_a_jour_le 
            FROM codes_naf c 
            LEFT JOIN ademe_facteurs a ON c.code = a.code_naf 
            ORDER BY c.code ASC
        ");
        $ademeList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Setup variables for layout
        ob_start();
        require __DIR__ . '/../Views/admin/manage_ademe.php';
        $content = ob_get_clean();
        
        $societeId = $_SESSION["active_societe_id"] ?? "all";
        $socFilter = ($societeId !== "all") ? " AND societe_id = " . (int)$societeId : "";
        $countEnAttente = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'en_attente'" . $socFilter)->fetchColumn();
        $countValides = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut IN ('valide_auto', 'valide_manuel')" . $socFilter)->fetchColumn();
        $countIntrouvables = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'introuvable'" . $socFilter)->fetchColumn();
        $societes = $db->query("SELECT * FROM societes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../Views/layout.php';
    }
    
    public function save() {
        $db = Database::getConnection();
        
        if (!empty($_POST['facteurs']) && is_array($_POST['facteurs'])) {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE ademe_facteurs SET facteur = ? WHERE code_naf = ?");
                
                foreach ($_POST['facteurs'] as $code => $facteur) {
                    $facteurVal = str_replace(',', '.', trim($facteur));
                    if (is_numeric($facteurVal)) {
                        $stmt->execute([(float)$facteurVal, $code]);
                    }
                }
                $db->commit();
                $_SESSION['flash_message'] = "Les facteurs d'émission ADEME ont été mis à jour.";
                $_SESSION['flash_type'] = "success";
            } catch (\Exception $e) {
                $db->rollBack();
                $_SESSION['flash_message'] = "Erreur : " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        }
        
        header("Location: ?action=manage_ademe");
        exit;
    }
}
