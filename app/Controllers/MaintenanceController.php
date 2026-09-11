<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class MaintenanceController {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    private function getPdo() {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        return $this->pdo;
    }

    public function emptyTables() {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pdo = $this->getPdo();
            if (!$pdo) {
                throw new Exception("Impossible d'établir la connexion à la base de données.");
            }

            $tables = $_POST['tables'] ?? [];

            if (empty($tables) || !is_array($tables)) {
                echo json_encode(['success' => false, 'message' => 'Aucune table n\'a été sélectionnée.']);
                exit;
            }

            $allowedTables = ['sources_csv', 'api_resultats', 'siren_connus', 'codes_naf', 'societes'];
            
            // Désactivation temporaire des contraintes de clés étrangères
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            foreach ($tables as $table) {
                if (in_array($table, $allowedTables)) {
                    if ($table === 'societes') {
                        $pdo->exec("DELETE FROM `societes` WHERE id > 1;");
                        $pdo->exec("ALTER TABLE `societes` AUTO_INCREMENT = 2;");
                    } else {
                        $pdo->exec("DELETE FROM `{$table}`;");
                        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1;");
                    }
                }
            }

            // Réactivation des contraintes
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            echo json_encode(['success' => true]);
            exit;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
            exit;
        }
    }

    public function exportSql() {
        $pdo = $this->getPdo();
        $tables = ['societes', 'sources_csv', 'api_resultats', 'siren_connus', 'codes_naf'];
        $dump = "-- Save EcoTrace SQL Dump\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $dump .= "TRUNCATE TABLE `{$table}`;\n";
                foreach ($rows as $row) {
                    $keys = array_keys($row);
                    $values = array_map(function($val) use ($pdo) {
                        return $val === null ? 'NULL' : $pdo->quote($val);
                    }, array_values($row));

                    $dump .= "INSERT INTO `{$table}` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $dump .= "\n";
            }
        }
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="ecotrace_backup_' . date('Y-m-d_H-i') . '.sql"');
        echo $dump;
        exit;
    }

    public function showImportSqlForm() {
        $content = '<div class="container-xl"><div class="card"><div class="card-header"><h3 class="card-title">Restaurer un Dump SQL</h3></div><div class="card-body"><form action="?action=import_sql_process" method="POST" enctype="multipart/form-data"><div class="mb-3"><label class="form-label">Fichier SQL</label><input type="file" name="sql_file" class="form-control" accept=".sql" required></div><button type="submit" class="btn btn-danger">Exécuter la restauration</button></form></div></div></div>';
        require __DIR__ . '/../Views/layout.php';
    }

    public function processImportSql() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file']['tmp_name'])) {
            $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
            if ($sql) {
                $pdo = $this->getPdo();
                $pdo->exec($sql);
            }
        }
        header('Location: index.php');
        exit;
    }

    public function getDistancesToFix() {
        header('Content-Type: application/json');
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->query("SELECT id, source_id, siren, nom_complet, siege_adresse, latitude, longitude FROM api_resultats WHERE (distance IS NULL OR distance <= 0 OR distance > 3000)");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
        exit;
    }

    public function fixSingleDistance() {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $adresse = trim($_POST['adresse'] ?? '');
        
        if ($id) {
            try {
                $pdo = $this->getPdo();
                $stmt = $pdo->prepare("SELECT id, source_id, siren, nom_complet, siege_adresse, latitude, longitude FROM api_resultats WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $lat = (!empty($row['latitude']) && (float)$row['latitude'] != 0) ? (float)$row['latitude'] : null;
                    $lon = (!empty($row['longitude']) && (float)$row['longitude'] != 0) ? (float)$row['longitude'] : null;
                    
                    if (empty($adresse) && !empty($row['siege_adresse'])) {
                        $adresse = $row['siege_adresse'];
                    }
                    
                    // Si adresse vide mais SIREN disponible, interroger l'API Recherche Entreprises pour retrouver l'adresse/commune
                    if (empty($adresse) && !empty($row['siren'])) {
                        $adresse = \App\Helpers\ApiHelper::recupererAdresseParSiren($row['siren']);
                        if (!empty($adresse)) {
                            $pdo->prepare("UPDATE api_resultats SET siege_adresse = :adr WHERE id = :id")->execute([':adr' => $adresse, ':id' => $id]);
                        }
                    }

                    // Si pas de coordonnées valides, tenter le géocodage intelligent (avec repli automatique sur le centre de la ville)
                    if (!$lat || !$lon) {
                        $coords = \App\Helpers\ApiHelper::geocodeSmarter($adresse);
                        if ($coords) {
                            $lat = (float)$coords['lat'];
                            $lon = (float)$coords['lon'];
                        }
                    }
                    
                    if ($lat && $lon) {
                        $origine = \App\Helpers\ApiHelper::getOrigineCoordinates($row['source_id'] ?? null);
                        $dist = \App\Helpers\ApiHelper::calculerDistance($origine['lat'], $origine['lon'], $lat, $lon);
                        if ($dist !== null && $dist > 0) {
                            $geo = \App\Helpers\ApiHelper::determinerOrigineGeo($dist, $adresse);
                            $upd = $pdo->prepare("UPDATE api_resultats SET distance = :dist, origine_geo = :geo, latitude = :lat, longitude = :lon WHERE id = :id");
                            $upd->execute([
                                ':dist' => $dist,
                                ':geo' => $geo,
                                ':lat' => $lat,
                                ':lon' => $lon,
                                ':id' => $id
                            ]);
                            echo json_encode(['success' => true, 'distance' => $dist, 'origine_geo' => $geo]);
                            exit;
                        }
                    }
                }
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'message' => "Impossible de géocoder l'adresse ou de calculer la distance."]);
        exit;
    }

    public function getGeoToFix() {
        header('Content-Type: application/json');
        $pdo = $this->getPdo();
        $stmt = $pdo->query("SELECT id, distance, siege_adresse FROM api_resultats WHERE origine_geo IS NULL OR origine_geo = '' OR origine_geo = 'Inconnue'");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function fixSingleGeo() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? null;
        $distance = $_POST['distance'] ?? null;
        $adresse = $_POST['adresse'] ?? '';
        if ($id) {
            $geo = \App\Helpers\ApiHelper::determinerOrigineGeo($distance, $adresse);
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("UPDATE api_resultats SET origine_geo = :geo WHERE id = :id");
            $stmt->execute([':geo' => $geo, ':id' => $id]);
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    public function apiMajNaf() {
        header('Content-Type: application/json');
        try {
            $pdo = $this->getPdo();
            $sql = "UPDATE api_resultats r 
                    INNER JOIN codes_naf n ON UPPER(REPLACE(n.code, '.', '')) = UPPER(REPLACE(r.activite_principale, '.', '')) 
                    SET r.activite_principale_libelle = n.libelle 
                    WHERE r.activite_principale IS NOT NULL AND r.activite_principale != ''";
            $count = $pdo->exec($sql);
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function repasserEnAttente() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? null;
        $nom = $_POST['nom_recherche'] ?? null;
        if ($id) {
            $pdo = $this->getPdo();
            if ($nom !== null) {
                $stmt = $pdo->prepare("UPDATE sources_csv SET statut = 'en_attente', api_result_id_selectionne = NULL, nom_recherche = :nom WHERE id = :id");
                $stmt->execute([':id' => $id, ':nom' => $nom]);
            } else {
                $stmt = $pdo->prepare("UPDATE sources_csv SET statut = 'en_attente', api_result_id_selectionne = NULL WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
    public function apiValiderConnus() {
        $pdo = $this->getPdo();
        try {
            // Find sources_csv en_attente which have a candidate with est_connu = 1
            $stmt = $pdo->query("
                SELECT s.id as source_id, r.id as resultat_id
                FROM sources_csv s
                JOIN api_resultats r ON s.id = r.source_id
                WHERE s.statut = 'en_attente' AND r.est_connu = 1
            ");
            
            $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $count = 0;
            
            $updateStmt = $pdo->prepare("UPDATE sources_csv SET statut = 'valide_auto', api_result_id_selectionne = :res_id WHERE id = :src_id");
            $qualifStmt = $pdo->prepare("UPDATE api_resultats SET est_qualifie = 1 WHERE id = ?");
            
            $processedSources = [];
            foreach ($matches as $match) {
                if (!isset($processedSources[$match['source_id']])) {
                    $updateStmt->execute([
                        ':res_id' => $match['resultat_id'],
                        ':src_id' => $match['source_id']
                    ]);
                    $qualifStmt->execute([$match['resultat_id']]);
                    $processedSources[$match['source_id']] = true;
                    $count++;
                }
            }
            
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    public function apiRelancerIntrouvables() {
        $pdo = $this->getPdo();
        try {
            // Find sources_csv introuvable
            $stmt = $pdo->query("SELECT id, nom_recherche, societe_id FROM sources_csv WHERE statut = 'introuvable'");
            $introuvables = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $count = 0;
            
            // Require API Helper if not loaded
            require_once __DIR__ . '/../Helpers/ApiHelper.php';
            $apiHelper = new \App\Helpers\ApiHelper();
            
            foreach ($introuvables as $item) {
                $nom = $item['nom_recherche'];
                
                // Smart Cleaning
                $mots_parasites = ['/\bSARL\b/i', '/\bSASU?\b/i', '/\bEURL\b/i', '/\bSA\b/i', '/\bFRANCE\b/i', '/\bGROUPE\b/i', '/\bLTD\b/i', '/\bINC\b/i', '/\bSNC\b/i', '/\bSCI\b/i'];
                $cleanNom = trim(preg_replace($mots_parasites, '', $nom));
                $cleanNom = preg_replace('/\s+/', ' ', $cleanNom); // remove extra spaces
                
                if (strlen($cleanNom) > 2 && $cleanNom !== $nom) {
                                        // Relancer API Gouv
                    $url = "https://recherche-entreprises.api.gouv.fr/search?q=" . urlencode($cleanNom) . "&per_page=5";
                    $ch = curl_init(); 
                    curl_setopt($ch, CURLOPT_URL, $url); 
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                    curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0'); 
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $reponse = curl_exec($ch); 
                    curl_close($ch);
                    
                    if ($reponse) {
                        $donneesJSON = json_decode($reponse, true); 
                        $raw_results = $donneesJSON['results'] ?? [];
                        
                        if (!empty($raw_results)) {
                            // Supprimer d'anciens candidats s'il y en avait
                            $delStmt = $pdo->prepare("DELETE FROM api_resultats WHERE source_id = ?");
                            $delStmt->execute([$item['id']]);
                            
                            foreach (array_slice($raw_results, 0, 5) as $r) {
                                $siren = $r['siren'] ?? null;
                                $nom_complet = $r['nom_complet'] ?? null;
                                $activite_principale = $r['activite_principale'] ?? null;
                                $siege_adresse = $r['siege']['adresse'] ?? null;
                                $latitude = $r['siege']['latitude'] ?? null;
                                $longitude = $r['siege']['longitude'] ?? null;
                                $est_ess = ($r['complements']['est_ess'] ?? false) ? 1 : 0;
                                $est_mission = ($r['complements']['est_societe_mission'] ?? false) ? 1 : 0;
                                $statut_juridique = isset($r['etat_administratif']) && $r['etat_administratif'] === 'C' ? 'Fermée' : 'Actif';
                                $est_alimentaire = \App\Helpers\ApiHelper::estAlimentaire($activite_principale) ? 1 : 0;
                                
                                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM siren_connus WHERE siren = ?");
                                $checkStmt->execute([$siren]);
                                $est_connu = $checkStmt->fetchColumn() ? 1 : 0;
                                
                                $ins = $pdo->prepare("INSERT INTO api_resultats 
                                    (source_id, siren, nom_complet, activite_principale, est_alimentaire, est_ess, est_societe_mission, statut_juridique, siege_adresse, latitude, longitude, est_connu)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    
                                $ins->execute([
                                    $item['id'], $siren, $nom_complet, $activite_principale,
                                    $est_alimentaire, $est_ess, $est_mission,
                                    $statut_juridique, $siege_adresse, $latitude, $longitude, $est_connu
                                ]);
                            }
                            
                            $updStmt = $pdo->prepare("UPDATE sources_csv SET statut = 'en_attente', nom_recherche = ? WHERE id = ?");
                            $updStmt->execute([$cleanNom, $item['id']]);
                            $count++;
                        }
                    }              }
            }
            
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
