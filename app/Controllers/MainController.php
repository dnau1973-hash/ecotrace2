<?php
namespace App\Controllers;

use App\Models\SourceModel;
use App\Models\ResultatModel;
use App\Models\StatistiquesModel;
use App\Models\SocieteModel;

class MainController {

    private $sourceModel;
    private $resultatModel;
    private $societeModel;

    public function __construct() {
        $this->sourceModel   = new SourceModel();
        $this->resultatModel = new ResultatModel();
        $this->societeModel  = new SocieteModel();
    }

    public function changeSociete() {
        if (isset($_GET['societe_id'])) {
            $societeId = $_GET['societe_id'];
            if ($societeId === 'all') {
                $_SESSION['active_societe_id'] = 'all';
            } else {
                $_SESSION['active_societe_id'] = (int)$societeId;
            }
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?'));
        exit;
    }

    public function index() {
        if (isset($_GET['action']) && $_GET['action'] === 'change_societe') {
            $this->changeSociete();
        }

        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';

        // 1. Récupération des sociétés pour le header
        $societes = $this->societeModel->getAll();

        // 2. Récupération des enregistrements selon le filtre société
        $sourcesEnAttenteBrut = $this->sourceModel->getEnAttente($activeSocieteId);
        $sourcesValides       = $this->sourceModel->getValidees($activeSocieteId);
        $sourcesIntrouvables  = $this->sourceModel->getIntrouvables($activeSocieteId);

        // 3. Rattachement des candidats
        $sourcesEnAttente = [];
        foreach ($sourcesEnAttenteBrut as $source) {
            $source['candidats'] = $this->resultatModel->getCandidatsBySourceId($source['id']);
            $sourcesEnAttente[]  = $source;
        }

        // 4. Calcul des statistiques
        $statsRse = StatistiquesModel::calculerStatsRse($sourcesValides);

        // 5. Compteurs pour la barre de navigation
        $countEnAttente   = count($sourcesEnAttente);
        $countValides     = count($sourcesValides);
        $countIntrouvables = count($sourcesIntrouvables);

        // Fetch active societe coordinates from database
        $activeOrigine = \App\Helpers\ApiHelper::getOrigineCoordinates(null, $activeSocieteId);
        $activeSocieteLat = $activeOrigine['lat'];
        $activeSocieteLon = $activeOrigine['lon'];

        $pdo = \App\Config\Database::getConnection();
        try { $pdo->exec("UPDATE societes SET latitude = 45.19165526, longitude = 0.76262712 WHERE (latitude IS NULL OR latitude = 0) AND est_defaut = 1"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE sources_csv ADD COLUMN annee INT NOT NULL DEFAULT 2024"); } catch (\PDOException $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS referentiel_vehicules (id INT AUTO_INCREMENT PRIMARY KEY, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, carburant VARCHAR(50) NOT NULL, conso_moyenne DECIMAL(10,2) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); } catch (\PDOException $e) {}
        try { $stmt = $pdo->query("SELECT COUNT(*) FROM referentiel_vehicules"); if ($stmt->fetchColumn() == 0) { $pdo->exec("INSERT INTO referentiel_vehicules (marque, modele, carburant, conso_moyenne) VALUES ('Renault', 'Clio V', 'Gazole', 4.5), ('Peugeot', '208', 'Essence', 5.2), ('Tesla', 'Model 3', 'Electrique', 15.0), ('Renault', 'Kangoo', 'Gazole', 5.8)"); } } catch (\PDOException $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS flotte_vehicules (id INT AUTO_INCREMENT PRIMARY KEY, societe_id INT NOT NULL, plaque VARCHAR(20) NOT NULL, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, carburant VARCHAR(50) NOT NULL, conso_moyenne DECIMAL(10,2) NOT NULL, statut VARCHAR(20) DEFAULT 'Actif', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); } catch (\PDOException $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS scope1_emissions (id INT AUTO_INCREMENT PRIMARY KEY, societe_id INT NOT NULL, annee INT NOT NULL, categorie VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, quantite DECIMAL(12,2) NOT NULL, unite VARCHAR(20) NOT NULL, facteur_emission DECIMAL(10,4) NOT NULL, total_co2 DECIMAL(12,2) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); } catch (\PDOException $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS siren_connus (siren VARCHAR(9) PRIMARY KEY, date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE codes_naf ADD COLUMN est_privilegie TINYINT(1) DEFAULT 0"); } catch (\PDOException $e) {}

        $stmtNaf = $pdo->query("SELECT code FROM codes_naf WHERE est_privilegie = 1");
        $nafPrivilegies = $stmtNaf ? $stmtNaf->fetchAll(\PDO::FETCH_COLUMN) : [];

                // --- SCOPE 1 & 2 FETCH ---
        $anneeEnCours = date('Y'); // We can make this dynamic later
        $socFilterScope1 = ($activeSocieteId !== "all") ? " societe_id = " . (int)$activeSocieteId : " 1=1 ";
        $stmtRef = $pdo->query("SELECT * FROM referentiel_vehicules ORDER BY marque ASC, modele ASC");
        $referentielVehicules = $stmtRef ? $stmtRef->fetchAll(\PDO::FETCH_ASSOC) : [];
        $marquesExistantes = array_values(array_unique(array_filter(array_column($referentielVehicules, 'marque'))));
        sort($marquesExistantes);
        
        // --- FLOTTE VEHICULES FETCH ---
        $stmtFlotte = $pdo->query("SELECT * FROM flotte_vehicules WHERE $socFilterScope1 ORDER BY marque ASC, modele ASC");
        $flotteVehicules = $stmtFlotte ? $stmtFlotte->fetchAll(\PDO::FETCH_ASSOC) : [];

        $stmtScope1 = $pdo->query("SELECT * FROM scope1_emissions WHERE $socFilterScope1 ORDER BY annee DESC, id DESC");
        $scope1Emissions = $stmtScope1 ? $stmtScope1->fetchAll(\PDO::FETCH_ASSOC) : [];
        
        $totalScope1 = 0;
        foreach ($scope1Emissions as $s1) {
            $totalScope1 += (float)$s1['total_co2'];
        }

        // --- SCOPE 3 COMPLEMENTS (NAF, ADEME, ANNEES, FOURNISSEURS QUALIFIES) ---
        $stmtNafAll = $pdo->query("SELECT * FROM codes_naf ORDER BY code ASC");
        $nafList = $stmtNafAll ? $stmtNafAll->fetchAll(\PDO::FETCH_ASSOC) : [];

        $stmtAdeme = $pdo->query("SELECT code_naf, facteur FROM ademe_facteurs");
        $ademeFacteurs = $stmtAdeme ? $stmtAdeme->fetchAll(\PDO::FETCH_KEY_PAIR) : [];

        $stmtYears = $pdo->query("
            SELECT DISTINCT annee FROM sources_csv WHERE annee IS NOT NULL 
            UNION 
            SELECT annee FROM exercices_comptables 
            ORDER BY annee DESC
        ");
        $anneesScope3 = $stmtYears ? $stmtYears->fetchAll(\PDO::FETCH_COLUMN) : [];
        if (empty($anneesScope3)) {
            $anneesScope3 = [(int)date('Y')];
        }

        $stmtExercices = $pdo->query("SELECT annee, statut, date_cloture, commentaire FROM exercices_comptables");
        $statutsExercices = [];
        if ($stmtExercices) {
            while ($ex = $stmtExercices->fetch(\PDO::FETCH_ASSOC)) {
                $statutsExercices[(int)$ex['annee']] = $ex;
            }
        }

        $stmtSuppliers = $pdo->query("
            SELECT DISTINCT ar.id as api_id, ar.nom_complet, ar.siren, ar.activite_principale, ar.activite_principale_libelle, ar.ville
            FROM api_resultats ar
            WHERE ar.est_qualifie = 1
               OR ar.id IN (SELECT DISTINCT api_result_id_selectionne FROM sources_csv WHERE api_result_id_selectionne IS NOT NULL AND statut IN ('valide_auto', 'valide_manuel'))
            ORDER BY ar.nom_complet ASC
        ");
        $fournisseursQualifies = $stmtSuppliers ? $stmtSuppliers->fetchAll(\PDO::FETCH_ASSOC) : [];
        
        $stmtExisting = $pdo->query("
            SELECT DISTINCT api_result_id_selectionne, annee 
            FROM sources_csv 
            WHERE api_result_id_selectionne IS NOT NULL
              AND statut IN ('valide_auto', 'valide_manuel')
        ");
        $fournisseurAnneesOccupees = [];
        if ($stmtExisting) {
            while ($row = $stmtExisting->fetch(\PDO::FETCH_ASSOC)) {
                $aid = (int)$row['api_result_id_selectionne'];
                $yr = (int)$row['annee'];
                if (!isset($fournisseurAnneesOccupees[$aid])) {
                    $fournisseurAnneesOccupees[$aid] = [];
                }
                $fournisseurAnneesOccupees[$aid][] = $yr;
            }
        }
        
        $stmtValideesInv = $pdo->query("
            SELECT ar.id as resultat_id, ar.id as api_id, ar.siren, ar.nom_complet, ar.siege_adresse, ar.latitude, ar.longitude, 
                   ar.distance, ar.origine_geo, ar.activite_principale, 
                   COALESCE(n.libelle, ar.activite_principale_libelle) as activite_principale_libelle,
                   ar.est_alimentaire, ar.est_ess, ar.est_societe_mission, ar.est_connu, ar.statut_juridique,
                   COUNT(sc.id) as nb_depenses,
                   COALESCE(SUM(sc.montant), 0) as total_depenses,
                   COALESCE(SUM(sc.poids), 0) as total_fret
            FROM api_resultats ar
            LEFT JOIN codes_naf n ON UPPER(REPLACE(n.code, '.', '')) = UPPER(REPLACE(ar.activite_principale, '.', ''))
            LEFT JOIN sources_csv sc ON sc.api_result_id_selectionne = ar.id AND sc.statut IN ('valide_auto', 'valide_manuel')
            WHERE ar.est_qualifie = 1
               OR sc.id IS NOT NULL
            GROUP BY ar.id
            ORDER BY ar.nom_complet ASC
        ");
        $societesValideesInventaire = $stmtValideesInv ? $stmtValideesInv->fetchAll(\PDO::FETCH_ASSOC) : [];
        $countValides = count($societesValideesInventaire);

        // 6. Rendu final
        $content = $this->renderView('dashboard', [
            'societes'                   => $societes,
            'activeSocieteId'            => $activeSocieteId,
            'stats'                      => $statsRse,
            'sourcesEnAttente'           => $sourcesEnAttente,
            'sourcesValides'             => $sourcesValides,
            'societesValideesInventaire' => $societesValideesInventaire,
            'sourcesIntrouvables'        => $sourcesIntrouvables,
            'countEnAttente'             => $countEnAttente,
            'countValides'               => $countValides,
            'countIntrouvables'          => $countIntrouvables,
            'activeSocieteLat'           => $activeSocieteLat,
            'activeSocieteLon'           => $activeSocieteLon,
            'nafPrivilegies'             => $nafPrivilegies,
            'nafList'                    => $nafList,
            'ademeFacteurs'              => $ademeFacteurs,
            'anneesScope3'               => $anneesScope3,
            'statutsExercices'           => $statutsExercices,
            'fournisseursQualifies'      => $fournisseursQualifies,
            'fournisseurAnneesOccupees'  => $fournisseurAnneesOccupees,
            'referentielVehicules'       => $referentielVehicules,
            'marquesExistantes'          => $marquesExistantes,
            'flotteVehicules'            => $flotteVehicules,
            'scope1Emissions'            => $scope1Emissions,
            'totalScope1'                => $totalScope1
        ]);

        require __DIR__ . '/../Views/layout.php';
    }

    public function exportCsv() {
        try {
            if (ob_get_length()) ob_end_clean();
            
            $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';
            $sourcesValides = $this->sourceModel->getValidees($activeSocieteId);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_validees.csv"');
            
            $output = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8 compatibility
            fwrite($output, "\xEF\xBB\xBF");
            
            fputcsv($output, [
                'ID Source', 
                'Nom Recherche', 
                'Nom Complet', 
                'SIREN', 
                'Statut Juridique',
                'Secteur activite', 
                'Est Alimentaire', 
                'Est ESS', 
                'Est Mission', 
                'Adresse', 
                'Distance (km)', 
                'Origine Geo', 
                'Montant Achats', 
                'Poids Fret (kg)',
                'Empreinte Carbone (kg CO2)',
                'Connu'
            ], ';');

            foreach ($sourcesValides as $valide) {
                $co2 = \App\Helpers\EcoHelper::estimerCO2(
                    $valide['activite_principale'] ?? '', 
                    $valide['montant'] ?? 0, 
                    $valide['poids'] ?? 0, 
                    $valide['distance'] ?? 0
                );
                fputcsv($output, [
                    $valide['source_id'] ?? '',
                    $valide['nom_recherche'] ?? '',
                    $valide['nom_complet'] ?? '',
                    $valide['siren'] ?? '',
                    $valide['statut_juridique'] ?? '',
                    $valide['activite_principale_libelle'] ?? '',
                    !empty($valide['est_alimentaire']) ? 'Oui' : 'Non',
                    !empty($valide['est_ess']) ? 'Oui' : 'Non',
                    !empty($valide['est_societe_mission']) ? 'Oui' : 'Non',
                    $valide['siege_adresse'] ?? '',
                    $valide['distance'] ?? '',
                    $valide['origine_geo'] ?? '',
                    $valide['montant'] ?? '',
                    $valide['poids'] ?? '',
                    $co2,
                    !empty($valide['est_connu']) ? 'Oui' : 'Non'
                ], ';');
            }

            fclose($output);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8', true, 500);
            echo "Erreur export CSV: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine();
            exit;
        }
    }

    private function renderView($viewName, $data = []) {
        extract($data);
        ob_start();
        require __DIR__ . '/../Views/' . $viewName . '.php';
        return ob_get_clean();
    }
}