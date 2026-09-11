<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\ApiHelper;
use PDO;

class ApiController {
    
    public function handle() {
        $action = $_POST['action'] ?? '';

        if ($action === 'valider') {
            $this->valider();
        } elseif ($action === 'delete_record') {
            $this->deleteRecord();
        } elseif ($action === 'ajax_search') {
            $this->ajaxSearch();
        } elseif ($action === 'ajout_manuel') {
            $this->ajoutManuel();
        } elseif ($action === 'ajout_manuel_direct') {
            $this->ajoutManuelDirect();
        } elseif ($action === 'enrichir_donnees') {
            $this->enrichirDonnees();
        } elseif ($action === 'surligner_siren_single') {
            $this->surlignerSirenSingle();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
            exit;
        }
    }

    private function valider() {
        $sourceId = (int)($_POST['source_id'] ?? 0);
        $resultatId = (int)($_POST['resultat_id'] ?? 0);

        if ($sourceId && $resultatId) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE sources_csv SET statut = 'valide_manuel', api_result_id_selectionne = :res_id WHERE id = :src_id");
            $stmt->execute([':res_id' => $resultatId, ':src_id' => $sourceId]);
            $pdo->prepare("UPDATE api_resultats SET est_qualifie = 1 WHERE id = ?")->execute([$resultatId]);
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header("Location: ?");
        exit;
    }

    private function deleteRecord() {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getConnection();
            $pdo->prepare("DELETE FROM sources_csv WHERE id = :id")->execute([':id' => (int)$_POST['id']]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) { 
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
        }
        exit;
    }
    
    private function ajoutManuel() {
        $nom = trim($_POST['nom_recherche'] ?? '');
        $montant = (float)($_POST['montant'] ?? 0);
        $poids = (float)($_POST['poids'] ?? 0);
        $societeId = (int)($_SESSION['active_societe_id'] ?? 1);
        if ($societeId === 'all') $societeId = 1;

        if (!empty($nom)) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO sources_csv (societe_id, nom_recherche, montant, poids, statut) VALUES (:soc_id, :nom, :montant, :poids, 'en_attente')");
            $stmt->execute([':soc_id' => $societeId, ':nom' => $nom, ':montant' => $montant, ':poids' => $poids]);
        }
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            $sourceId = isset($pdo) ? $pdo->lastInsertId() : null;
            echo json_encode(['success' => true, 'source_id' => $sourceId]);
            exit;
        }
        header("Location: ?");
        exit;
    }

    private function ajoutManuelDirect() {
        header('Content-Type: application/json');
        
        $nomComplet = trim($_POST['nom_complet'] ?? '');
        $siren = trim($_POST['siren'] ?? '');
        $codeNaf = trim($_POST['code_naf'] ?? '');
        $estAlimStr = $_POST['est_alimentaire'] ?? 'auto';
        $etatAdmin = $_POST['etat_administratif'] ?? 'A';
        $adresse = trim($_POST['adresse'] ?? '');
        $lat = $_POST['latitude'] ?? null;
        if ($lat === '') $lat = null;
        $lon = $_POST['longitude'] ?? null;
        if ($lon === '') $lon = null;
        $distance = $_POST['distance'] ?? null;
        if ($distance === '') $distance = null;

        $societeId = (int)($_SESSION['active_societe_id'] ?? 1);
        if ($societeId === 'all') $societeId = 1;

        if (empty($nomComplet)) {
            echo json_encode(['success' => false, 'message' => 'Le nom complet est obligatoire.']);
            exit;
        }

        $estAlim = 0;
        if ($estAlimStr === '1') $estAlim = 1;
        elseif ($estAlimStr === 'auto' && !empty($codeNaf)) {
            $estAlim = \App\Helpers\EcoHelper::estAlimentaire($codeNaf) ? 1 : 0;
        }

        $santeJuridique = 'Actif';
        if ($etatAdmin === 'C') $santeJuridique = 'Fermée';
        elseif ($etatAdmin === 'D') $santeJuridique = 'Procédure Collective';

        try {
            $pdo = Database::getConnection();
            
            // Insert into sources_csv with statut = valide_manuel
            $stmtSrc = $pdo->prepare("INSERT INTO sources_csv (societe_id, nom_recherche, statut) VALUES (:soc_id, :nom, 'valide_manuel')");
            $stmtSrc->execute([':soc_id' => $societeId, ':nom' => $nomComplet]);
            $sourceId = $pdo->lastInsertId();

            // Origine Geo fallback
            $ogeo = \App\Helpers\ApiHelper::determinerOrigineGeo($distance, $adresse);

            // Check if SIREN known
            $est_connu = 0;
            if ($siren) {
                $stmtCheck = $pdo->prepare("SELECT 1 FROM siren_connus WHERE siren = :siren");
                $stmtCheck->execute([':siren' => $siren]);
                if ($stmtCheck->fetchColumn()) $est_connu = 1;
            }

            // Insert into api_resultats
            $stmtRes = $pdo->prepare("INSERT INTO api_resultats 
                (source_id, siren, nom_complet, activite_principale, activite_principale_libelle, est_alimentaire, statut_juridique, siege_adresse, latitude, longitude, distance, origine_geo, est_connu, est_qualifie) 
                VALUES (:sid, :siren, :nom, :naf, :naf_libelle, :alim, :statut_juridique, :adr, :lat, :lon, :dist, :ogeo, :connu, 1)");
            $stmtRes->execute([
                ':sid' => $sourceId,
                ':siren' => $siren ?: null,
                ':nom' => $nomComplet,
                ':naf' => $codeNaf ?: null,
                ':naf_libelle' => $codeNaf ? \App\Helpers\ApiHelper::getLibelleNafLocal($codeNaf) : null,
                ':alim' => $estAlim,
                ':statut_juridique' => $santeJuridique,
                ':adr' => $adresse ?: null,
                ':lat' => $lat,
                ':lon' => $lon,
                ':dist' => $distance,
                ':ogeo' => $ogeo,
                ':connu' => $est_connu
            ]);
            $resId = $pdo->lastInsertId();

            // Update validation
            $stmtUpdate = $pdo->prepare("UPDATE sources_csv SET api_result_id_selectionne = :resid WHERE id = :id");
            $stmtUpdate->execute([':resid' => $resId, ':id' => $sourceId]);

            echo json_encode(['success' => true, 'message' => "L'entité a été ajoutée et validée avec succès !"]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
        exit;
    }

    private function ajaxSearch() {
        header('Content-Type: application/json');
        $sourceId = (int)($_POST['source_id'] ?? 0); 
        $nouveauNom = trim($_POST['nouveau_nom'] ?? ''); 
        $apiSource = $_POST['api_source'] ?? 'gouv';
        
        if (empty($nouveauNom)) { 
            echo json_encode(['success' => false, 'message' => 'Le nom est vide.']); 
            exit; 
        }
        

        $pdo = Database::getConnection();
        
        $origine = ApiHelper::getOrigineCoordinates($sourceId);
        $origineLat = $origine['lat'];
        $origineLon = $origine['lon'];

        $entreprises_trouvees = [];

        // --- APPEL A L'API GOUV ---
        $url = 'https://recherche-entreprises.api.gouv.fr/search?q=' . urlencode($nouveauNom) . '&per_page=5';
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0'); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $reponse = curl_exec($ch); $errNo = curl_errno($ch); $errMsg = curl_error($ch);
        curl_close($ch);
        if ($errNo) { $reponse = null; }

        if ($reponse) {
            $donneesJSON = json_decode($reponse, true); 
            $raw_results = $donneesJSON['results'] ?? [];
            foreach(array_slice($raw_results, 0, 5) as $ent) {
                $entreprises_trouvees[] = [
                    'siren' => $ent['siren'] ?? null, 
                    'nom_complet' => $ent['nom_complet'] ?? null, 
                    'activite_principale' => $ent['activite_principale'] ?? null, 
                    'adresse' => $ent['siege']['adresse'] ?? null, 
                    'latitude' => $ent['siege']['latitude'] ?? null, 
                    'longitude' => $ent['siege']['longitude'] ?? null, 
                    'etat_administratif' => $ent['etat_administratif'] ?? 'A', 
                    'est_ess' => ($ent['complements']['est_ess'] ?? false) ? 1 : 0, 
                    'est_mission' => ($ent['complements']['est_societe_mission'] ?? false) ? 1 : 0
                ];
            }
        }

        try {
            $pdo = Database::getConnection();

            // Garantir que la table siren_connus existe
            $pdo->exec("CREATE TABLE IF NOT EXISTS siren_connus (
                siren VARCHAR(9) PRIMARY KEY,
                date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // 0 résultat -> passage en statut Introuvable
            if (count($entreprises_trouvees) === 0) { 
                $pdo->prepare("UPDATE sources_csv SET statut = 'introuvable' WHERE id = :id")->execute([':id' => $sourceId]);
                echo json_encode(['success' => true, 'statut' => 'introuvable', 'message' => "Aucun résultat trouvé."]); 
                exit; 
            }

            // Nettoyage des anciens candidats de la source
            $pdo->prepare("DELETE FROM api_resultats WHERE source_id = :id")->execute([':id' => $sourceId]);
            
            $stmtInsert = $pdo->prepare("INSERT INTO api_resultats (source_id, siren, nom_complet, activite_principale, activite_principale_libelle, est_alimentaire, est_ess, est_societe_mission, statut_juridique, siege_adresse, latitude, longitude, distance, origine_geo, est_connu) VALUES (:source_id, :siren, :nom_complet, :activite_principale, :activite_principale_libelle, :est_alimentaire, :est_ess, :est_mission, :statut_juridique, :siege_adresse, :latitude, :longitude, :distance, :ogeo, :est_connu)");
            
            $stmtCheck = $pdo->prepare("SELECT 1 FROM siren_connus WHERE siren = :siren");
            
            $dernierResultatId = null;
            foreach ($entreprises_trouvees as $entreprise) {
                $lat = $entreprise['latitude'] ?? null; 
                $lon = $entreprise['longitude'] ?? null; 
                if (empty($lat) || empty($lon)) { 
                    $coords = ApiHelper::geocodeSmarter($entreprise['adresse']); 
                    if ($coords) { $lat = $coords['lat']; $lon = $coords['lon']; } 
                }
                $dist = ($lat && $lon) ? ApiHelper::calculerDistance($origineLat, $origineLon, $lat, $lon) : null;
                $ogeo = ApiHelper::determinerOrigineGeo($dist, $entreprise['adresse']);
                $sante = ApiHelper::determinerSanteJuridique(['siren' => $entreprise['siren'], 'etat_administratif' => $entreprise['etat_administratif']]);

                // Vérification si SIREN reconnu
                $est_connu = 0;
                if (!empty($entreprise['siren'])) {
                    $stmtCheck->execute([':siren' => $entreprise['siren']]);
                    $est_connu = $stmtCheck->fetchColumn() ? 1 : 0;
                }

                $stmtInsert->execute([
                    ':source_id' => $sourceId, 
                    ':siren' => $entreprise['siren'], 
                    ':nom_complet' => $entreprise['nom_complet'],
                    ':activite_principale' => $entreprise['activite_principale'], 
                    ':activite_principale_libelle' => ApiHelper::getLibelleNafLocal($entreprise['activite_principale']), 
                    ':est_alimentaire' => ApiHelper::estAlimentaire($entreprise['activite_principale']), 
                    ':est_ess' => $entreprise['est_ess'], 
                    ':est_mission' => $entreprise['est_mission'], 
                    ':statut_juridique' => $sante, 
                    ':siege_adresse' => $entreprise['adresse'], 
                    ':latitude' => $lat, 
                    ':longitude' => $lon, 
                    ':distance' => $dist, 
                    ':ogeo' => $ogeo,
                    ':est_connu' => $est_connu
                ]);
                $dernierResultatId = $pdo->lastInsertId();
            }
            
            $statut = (count($entreprises_trouvees) === 1) ? 'valide_auto' : 'en_attente'; 
            $resId = (count($entreprises_trouvees) === 1) ? $dernierResultatId : null;
            $pdo->prepare("UPDATE sources_csv SET nom_recherche = :nom, statut = :statut, api_result_id_selectionne = :res_id WHERE id = :id")->execute([':nom' => $nouveauNom, ':statut' => $statut, ':res_id' => $resId, ':id' => $sourceId]);
            if ($resId) {
                $pdo->prepare("UPDATE api_resultats SET est_qualifie = 1 WHERE id = ?")->execute([$resId]);
            }
            
            echo json_encode(['success' => true, 'statut' => $statut, 'message' => "Mise à jour réussie !"]); 
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => "Erreur de traitement : " . $e->getMessage()]);
            exit;
        }
    }

    private function enrichirDonnees() {
        $sourceId = (int)($_POST['source_id'] ?? 0);
        $montant = (float)($_POST['montant'] ?? 0);
        $poids = (float)($_POST['poids'] ?? 0);

        if ($sourceId) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE sources_csv SET montant = :montant, poids = :poids WHERE id = :id");
            $stmt->execute([
                ':montant' => $montant, 
                ':poids' => $poids, 
                ':id' => $sourceId
            ]);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header("Location: ?");
        exit;
    }

    private function surlignerSirenSingle() {
        header('Content-Type: application/json');
        $siren = $_POST['siren'] ?? '';
        
        if (strlen($siren) !== 9) { 
            echo json_encode(['success' => false, 'message' => 'Format SIREN invalide']); 
            exit; 
        }

        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO siren_connus (siren) VALUES (:siren)");
        $stmt->execute([':siren' => $siren]);

        $stmtUpd = $pdo->prepare("UPDATE api_resultats SET est_connu = 1 WHERE siren = :siren");
        $stmtUpd->execute([':siren' => $siren]);
        
        echo json_encode(['success' => true, 'candidats_surlignes' => $stmtUpd->rowCount()]);
        exit;
    }
    public function validerEtranger() {
        header('Content-Type: application/json');
        
        $sourceId = (int)($_POST['source_id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        
        if (!$sourceId || !$nom) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            return;
        }
        
        try {
            $pdo = Database::getConnection();
            
            // Origine Geo / Distance
            $ogeo = 'Inconnue';
            $lat = null;
            $lon = null;
            $distance = null;
            
            if ($adresse) {
                // geocoder
                $coords = \App\Helpers\ApiHelper::geocodeSmarter($adresse);
                if ($coords) {
                    $lat = $coords['lat'];
                    $lon = $coords['lon'];
                    
                    $origine = \App\Helpers\ApiHelper::getOrigineCoordinates($sourceId);
                    $distance = \App\Helpers\ApiHelper::calculerDistance($origine['lat'], $origine['lon'], $lat, $lon);
                    $ogeo = \App\Helpers\ApiHelper::determinerOrigineGeo($distance, $adresse);
                }
            }
            
            $stmtRes = $pdo->prepare("INSERT INTO api_resultats 
                (source_id, siren, nom_complet, statut_juridique, siege_adresse, latitude, longitude, distance, origine_geo, est_connu, est_qualifie) 
                VALUES (?, 'ETRANGER', ?, 'Etranger', ?, ?, ?, ?, ?, 0, 1)");
            
            $stmtRes->execute([
                $sourceId, $nom, $adresse ?: null, $lat, $lon, $distance, $ogeo
            ]);
            
            $resultatId = $pdo->lastInsertId();
            
            $stmtUpd = $pdo->prepare("UPDATE sources_csv SET statut = 'valide_manuel', api_result_id_selectionne = ? WHERE id = ?");
            $stmtUpd->execute([$resultatId, $sourceId]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
