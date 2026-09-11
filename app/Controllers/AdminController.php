<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class AdminController {
    public function uninstall() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = Database::getConnection();
            try {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec("DROP TABLE IF EXISTS `" . $row['table_name'] . "`");
                }
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
                
                // Effacer le fichier .env
                $envFile = __DIR__ . '/../../.env';
                if (file_exists($envFile)) {
                    unlink($envFile);
                }
                
                session_destroy();
                header("Location: ?");
                exit;
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = "Erreur de désinstallation: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
                header("Location: ?");
                exit;
            }
        }
    }
    public function truncateTable() {
        $db = Database::getConnection();
        $table = $_GET["table"] ?? "";
        if (!empty($table)) {
            try {
                // Basic safety check
                if (preg_match("/^[a-zA-Z0-9_]+$/", $table)) {
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                    $db->exec("DELETE FROM `" . $table . "`");
                    // Optionally reset auto-increment, though some DBs might need ALER TABLE
                    try { $db->exec("ALTER TABLE `" . $table . "` AUTO_INCREMENT = 1"); } catch(\Exception $e) {}
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
                }
            } catch (\Exception $e) {
                // S'assurer de remettre les cles etrangeres si erreur
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
                $_SESSION['flash_message'] = "Erreur lors du vidage : " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: ?action=admin_database");
        exit;
    }

    public function database() {
        $db = Database::getConnection();
        
        $tables = [];
        $totalSize = 0;
        $totalRows = 0;
        
        try {
            // A safer way is using database() function in mysql
            $stmt = $db->query("
                SELECT 
                    table_name AS 'name', 
                    table_rows AS 'rows', 
                    (data_length + index_length) AS 'size', 
                    data_free AS 'free',
                    create_time AS 'created_at',
                    update_time AS 'updated_at',
                    table_collation AS 'collation'
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY table_name ASC
            ");
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                // information_schema table_rows is an approximation for InnoDB, let's get the exact count
                $countStmt = $db->query("SELECT COUNT(*) FROM `" . $row['name'] . "`");
                $exactCount = $countStmt->fetchColumn();
                
                $row['exact_rows'] = $exactCount;
                $tables[] = $row;
                
                $totalSize += $row['size'];
                $totalRows += $exactCount;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $societeId = $_SESSION["active_societe_id"] ?? "all";
        $socFilter = ($societeId !== "all") ? " AND societe_id = " . (int)$societeId : "";
        $countEnAttente = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'en_attente'" . $socFilter)->fetchColumn();
        $countValides = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut IN ('valide_auto', 'valide_manuel')" . $socFilter)->fetchColumn();
        $countIntrouvables = $db->query("SELECT COUNT(*) FROM sources_csv WHERE statut = 'introuvable'" . $socFilter)->fetchColumn();
        $societes = $db->query("SELECT * FROM societes ORDER BY nom ASC")->fetchAll(\PDO::FETCH_ASSOC);

        // Render view
        ob_start();
        require __DIR__ . '/../Views/admin/database.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout.php';
    }
    
    public function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
