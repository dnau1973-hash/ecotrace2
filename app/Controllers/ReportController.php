<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class ReportController {

    public function generate() {
        $db = Database::getConnection();

        // 1. Fetch societies
        $stmtSoc = $db->query("SELECT * FROM societes ORDER BY nom ASC");
        $societes = $stmtSoc->fetchAll(PDO::FETCH_ASSOC);

        // 2. Filters
        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';
        // Check if user submitted filter form in the report page
        $anneeFiltre = $_GET['annee'] ?? date('Y');
        $socFiltre = $_GET['societe_id'] ?? $activeSocieteId;

        // 3. Build queries
        $socCondS1 = ($socFiltre === 'all') ? "1=1" : "societe_id = " . (int)$socFiltre;
        $socCondS3 = ($socFiltre === 'all') ? "(sc.societe_id IS NULL OR 1=1)" : "sc.societe_id = " . (int)$socFiltre;

        // --- SCOPE 1 & 2 ---
        $stmtS1 = $db->prepare("
            SELECT categorie, SUM(total_co2) as total_co2 
            FROM scope1_emissions 
            WHERE $socCondS1 AND annee = ? 
            GROUP BY categorie
        ");
        $stmtS1->execute([$anneeFiltre]);
        $scope1Data = $stmtS1->fetchAll(PDO::FETCH_ASSOC);

        $totalScope1 = 0;
        foreach($scope1Data as $d) {
            $totalScope1 += $d['total_co2'];
        }

        // --- SCOPE 3 ---
        // Fetch validated sources for the selected year
        $stmtS3 = $db->prepare("
            SELECT 
                sc.nom_recherche, 
                sc.montant, 
                sc.poids, 
                ar.nom_complet as fournisseur,
                ar.activite_principale_libelle as secteur,
                af.facteur,
                (sc.montant * af.facteur) as total_co2
            FROM sources_csv sc
            JOIN api_resultats ar ON sc.api_result_id_selectionne = ar.id
            LEFT JOIN ademe_facteurs af ON ar.activite_principale = af.code_naf
            WHERE sc.statut IN ('valide_auto', 'valide_manuel') 
              AND sc.annee = ?
              AND $socCondS3
            ORDER BY total_co2 DESC
        ");
        $stmtS3->execute([$anneeFiltre]);
        $scope3Data = $stmtS3->fetchAll(PDO::FETCH_ASSOC);

        $totalScope3 = 0;
        $secteurs = [];
        $topFournisseurs = [];

        foreach($scope3Data as $idx => $d) {
            $co2 = $d['total_co2'] ?: 0;
            $totalScope3 += $co2;
            
            // Group by sector
            $sect = $d['secteur'] ?: 'Non défini';
            if(!isset($secteurs[$sect])) {
                $secteurs[$sect] = 0;
            }
            $secteurs[$sect] += $co2;

            // Top 10
            if ($idx < 10) {
                $topFournisseurs[] = $d;
            }
        }

        // Sort sectors
        arsort($secteurs);
        $topSecteurs = array_slice($secteurs, 0, 5, true);

        // Render View
        ob_start();
        require __DIR__ . '/../Views/report.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
}
