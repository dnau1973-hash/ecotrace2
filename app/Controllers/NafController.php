<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class NafController {
    public function manage() {
        $db = Database::getConnection();
        
        // Ensure column exists
        try { 
            $db->exec("ALTER TABLE codes_naf ADD COLUMN est_privilegie TINYINT(1) DEFAULT 0"); 
        } catch (\Exception $e) {}
        
        $stmt = $db->query("SELECT * FROM codes_naf ORDER BY code ASC");
        $nafList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Render view
        ob_start();
        require __DIR__ . '/../Views/admin/manage_naf.php';
        $content = ob_get_clean();
        
        // Needs variables for layout
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
        
        $selectedCodes = $_POST['naf_codes'] ?? [];
        
        try {
            $db->beginTransaction();
            // Reset all
            $db->exec("UPDATE codes_naf SET est_privilegie = 0");
            
            // Set selected
            if (!empty($selectedCodes) && is_array($selectedCodes)) {
                $placeholders = implode(',', array_fill(0, count($selectedCodes), '?'));
                $stmt = $db->prepare("UPDATE codes_naf SET est_privilegie = 1 WHERE code IN ($placeholders)");
                $stmt->execute($selectedCodes);
            }
            $db->commit();
            
            $_SESSION['flash_message'] = "La liste des codes NAF privilégiés a été mise à jour.";
            $_SESSION['flash_type'] = "success";
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_message'] = "Erreur : " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '?action=manage_naf';
        header("Location: " . $redirect);
        exit;
    }
}
