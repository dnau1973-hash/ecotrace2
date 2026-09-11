<?php
namespace App\Controllers;

use App\Models\SocieteModel;
use App\Models\SourceModel;
use App\Config\Database;
use PDO;
use Exception;

class ImportController {

    private $societeModel;
    private $sourceModel;
    private $pdo;

    public function __construct() {
        $this->societeModel = new SocieteModel();
        $this->sourceModel  = new SourceModel();
        $this->pdo          = Database::getConnection();
    }

    // ==========================================
    // IMPORTATION CSV (ACHATS / FOURNISSEURS)
    // ==========================================

    public function showForm() {
        $societes = $this->societeModel->getAll();
        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';

        // Compteurs pour les badges
        $countEnAttente    = count($this->sourceModel->getEnAttente($activeSocieteId));
        $countValides      = count($this->sourceModel->getValidees($activeSocieteId));
        $countIntrouvables  = count($this->sourceModel->getIntrouvables($activeSocieteId));

        $content = $this->renderView('import', [
            'societes'        => $societes,
            'activeSocieteId' => $activeSocieteId
        ]);

        require __DIR__ . '/../Views/layout.php';
    }

    public function process() {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        // Vérification CSRF
        if (empty($_POST['_csrf']) || !\App\Helpers\SecurityHelper::verifyToken($_POST['_csrf'])) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
            exit;
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file']['tmp_name'])) {
                throw new Exception("Aucun fichier CSV sélectionné ou téléversé.");
            }

            $societeId = (int)($_POST['societe_id'] ?? 1);
            $hasHeader = isset($_POST['has_header']) && $_POST['has_header'] == '1';
            $file = $_FILES['csv_file']['tmp_name'];

            if (!file_exists($file) || !is_readable($file)) {
                throw new Exception("Impossible de lire le fichier temporaire.");
            }

            // Conversion automatique du fichier en UTF-8 pour éviter les erreurs d'encodage (ISO-8859-1 / Windows-1252)
            $content = file_get_contents($file);
            if (!mb_detect_encoding($content, 'UTF-8', true)) {
                $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
            }

            // Création d'un flux temporaire en mémoire
            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $content);
            rewind($handle);

            // Détection et suppression du BOM UTF-8
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Détection du séparateur (; ou ,)
            $firstLine = fgets($handle);
            $delimiter = (substr_count((string)$firstLine, ';') >= substr_count((string)$firstLine, ',')) ? ';' : ',';
            fseek($handle, ($bom === "\xEF\xBB\xBF") ? 3 : 0);

            if ($hasHeader) {
                fgetcsv($handle, 1000, $delimiter); // Ignorer l'en-tête
            }

            $annee = (int)($_POST['annee'] ?? date('Y'));

            // Détection automatique du nom de la colonne dans la base (nom_recherche ou nom_source)
            $colName = 'nom_recherche';
            $checkCol = $this->pdo->query("SHOW COLUMNS FROM sources_csv LIKE 'nom_recherche'");
            if ($checkCol->rowCount() === 0) {
                $colName = 'nom_source';
            }

            $sql = "INSERT INTO sources_csv (societe_id, {$colName}, montant, poids, statut, api_result_id_selectionne, annee, date_import) 
                    VALUES (:societe_id, :nom, :montant, :poids, :statut, :api_id, :annee, NOW())";
            $stmt = $this->pdo->prepare($sql);

            // Requête pour vérifier si ce fournisseur est déjà qualifié dans l'inventaire pérenne
            $stmtCheckKnown = $this->pdo->prepare("
                SELECT ar.id 
                FROM api_resultats ar
                JOIN sources_csv sc ON sc.api_result_id_selectionne = ar.id
                WHERE sc.statut IN ('valide_auto', 'valide_manuel')
                  AND (LOWER(TRIM(ar.nom_complet)) = LOWER(TRIM(?)) OR LOWER(TRIM(sc.nom_recherche)) = LOWER(TRIM(?)))
                LIMIT 1
            ");

            $insertedItems = [];
            $totalRows = 0;
            $totalKnown = 0;

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$data || (count($data) === 1 && trim((string)($data[0] ?? '')) === '')) {
                    continue;
                }

                $nom     = trim((string)($data[0] ?? ''));
                $montant = floatval(str_replace([' ', ','], ['', '.'], (string)($data[1] ?? '0')));
                $poids   = floatval(str_replace([' ', ','], ['', '.'], (string)($data[2] ?? '0')));

                if (!empty($nom)) {
                    $totalRows++;
                    $stmtCheckKnown->execute([$nom, $nom]);
                    $knownApiId = $stmtCheckKnown->fetchColumn();
                    $statut = $knownApiId ? 'valide_auto' : 'en_attente';
                    $apiIdSelectionne = $knownApiId ?: null;

                    $stmt->execute([
                        ':societe_id' => $societeId,
                        ':nom'        => $nom,
                        ':montant'    => $montant,
                        ':poids'      => $poids,
                        ':statut'     => $statut,
                        ':api_id'     => $apiIdSelectionne,
                        ':annee'      => $annee
                    ]);

                    // Uniquement les nouveaux fournisseurs en attente doivent passer dans le traitement API
                    if ($knownApiId) {
                        $totalKnown++;
                    } else {
                        $insertedItems[] = [
                            'id'  => $this->pdo->lastInsertId(),
                            'nom' => $nom
                        ];
                    }
                }
            }
            fclose($handle);

            if ($totalRows === 0) {
                throw new Exception("Le fichier CSV ne contient aucune ligne de données valide.");
            }

            $_SESSION['active_societe_id'] = $societeId;

            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success'     => true,
                'data'        => $insertedItems,
                'total_rows'  => $totalRows,
                'total_known' => $totalKnown
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ==========================================
    // IMPORTATION SIREN (SURLIGNAGE)
    // ==========================================

    public function showSirenForm() {
        $societes = $this->societeModel->getAll();
        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';

        $content = $this->renderView('import_siren', [
            'societes'        => $societes,
            'activeSocieteId' => $activeSocieteId
        ]);

        require __DIR__ . '/../Views/layout.php';
    }

    public function processSiren() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification CSRF
            if (empty($_POST['_csrf']) || !\App\Helpers\SecurityHelper::verifyToken($_POST['_csrf'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
                exit;
            }
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO siren_connus (siren) VALUES (:siren)");

            $sirensFound = [];

            // Si l'importation se fait par un champ texte (liste)
            if (!empty($_POST['sirens_list'])) {
                $sirens = explode("\n", str_replace("\r", "", $_POST['sirens_list']));
                foreach ($sirens as $siren) {
                    $sirenClean = preg_replace('/[^0-9]/', '', $siren);
                    if (strlen($sirenClean) === 9) {
                        $stmt->execute([':siren' => $sirenClean]);
                        $sirensFound[] = $sirenClean;
                    }
                }
            }

            // Si l'importation se fait par un fichier CSV
            if (!empty($_FILES['siren_file']['tmp_name']) && is_readable($_FILES['siren_file']['tmp_name'])) {
                $path = $_FILES['siren_file']['tmp_name'];
                $handle = fopen($path, 'r');
                if ($handle) {
                    // Détection du délimiteur (',' ou ';')
                    $firstLine = fgets($handle);
                    $delimiter = (substr_count((string)$firstLine, ';') >= substr_count((string)$firstLine, ',')) ? ';' : ',';
                    // Repositionner au début
                    rewind($handle);
                    // Si BOM, sauter
                    $bom = fread($handle, 3);
                    if ($bom !== "\xEF\xBB\xBF") {
                        rewind($handle);
                    }
                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        $sirenClean = preg_replace('/[^0-9]/', '', $data[0] ?? '');
                        if (strlen($sirenClean) === 9) {
                            $stmt->execute([':siren' => $sirenClean]);
                            $sirensFound[] = $sirenClean;
                        }
                    }
                    fclose($handle);
                }
            }

            // Retour JSON pour la requête AJAX attendue côté client
            header('Content-Type: application/json; charset=utf-8');
            if (empty($sirensFound)) {
                echo json_encode(['success' => false, 'message' => 'Aucun SIREN valide trouvé.']);
            } else {
                echo json_encode(['success' => true, 'data' => $sirensFound], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    /**
     * Traitement de l'import CSV 3 colonnes : SIREN, Montant, Tonnage
     */
    public function processSirenCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit;
        }

        $societeId = (int)($_POST['societe_id'] ?? 1);
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $hasHeader = isset($_POST['has_header']) && $_POST['has_header'] == '1';
        $uniteTonnage = $_POST['unite_tonnage'] ?? 't'; // 't' (tonnes) ou 'kg' (kilogrammes)
        $redirect = $_POST['redirect_to'] ?? 'index.php';

        try {
            // 1. Vérifier si l'exercice est clôturé
            $stmtCheckCloture = $this->pdo->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
            $stmtCheckCloture->execute([$annee]);
            if ($stmtCheckCloture->fetchColumn() === 'cloture') {
                throw new Exception("Impossible d'importer : l'exercice comptable $annee est clôturé et verrouillé.");
            }

            // 2. Vérifier le fichier téléversé
            if (empty($_FILES['csv_file_siren']['tmp_name']) || !file_exists($_FILES['csv_file_siren']['tmp_name'])) {
                throw new Exception("Aucun fichier CSV sélectionné.");
            }

            $file = $_FILES['csv_file_siren']['tmp_name'];
            $content = file_get_contents($file);
            if (!mb_detect_encoding($content, 'UTF-8', true)) {
                $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
            }

            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $content);
            rewind($handle);

            // BOM check
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            $delimiter = ';';
            if (substr_count((string)$firstLine, ',') > substr_count((string)$firstLine, ';')) {
                $delimiter = ',';
            } elseif (substr_count((string)$firstLine, "\t") > substr_count((string)$firstLine, ';')) {
                $delimiter = "\t";
            }
            fseek($handle, ($bom === "\xEF\xBB\xBF") ? 3 : 0);

            if ($hasHeader) {
                fgetcsv($handle, 1000, $delimiter);
            }

            $origine = \App\Helpers\ApiHelper::getOrigineCoordinates(null, $societeId);
            $origineLat = $origine['lat'];
            $origineLon = $origine['lon'];

            $stmtFindSiren = $this->pdo->prepare("SELECT id, nom_complet, siren FROM api_resultats WHERE siren = ? LIMIT 1");
            $stmtMarkQualifie = $this->pdo->prepare("UPDATE api_resultats SET est_qualifie = 1 WHERE id = ?");
            $stmtCheckDuplicate = $this->pdo->prepare("
                SELECT id FROM sources_csv 
                WHERE api_result_id_selectionne = ? AND annee = ? AND statut IN ('valide_auto', 'valide_manuel') 
                LIMIT 1
            ");
            $stmtUpdateDuplicate = $this->pdo->prepare("UPDATE sources_csv SET montant = montant + ?, poids = poids + ? WHERE id = ?");
            $stmtInsertSource = $this->pdo->prepare("
                INSERT INTO sources_csv (societe_id, nom_recherche, montant, poids, statut, api_result_id_selectionne, annee, date_import)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtInsertApiResult = $this->pdo->prepare("
                INSERT INTO api_resultats 
                (siren, nom_complet, activite_principale, activite_principale_libelle, est_alimentaire, est_ess, est_societe_mission, statut_juridique, siege_adresse, latitude, longitude, distance, origine_geo, est_connu, est_qualifie)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
            ");

            $totalLignes = 0;
            $reconnusCount = 0;
            $creesCount = 0;
            $enAttenteCount = 0;
            $cumulesCount = 0;

            // Pré-agrégation en amont par SIREN (consolidation des factures multiples)
            $aggregated = [];
            $totalLignesFichier = 0;

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$data || (count($data) === 1 && trim((string)($data[0] ?? '')) === '')) {
                    continue;
                }
                $totalLignesFichier++;

                $sirenRaw = trim((string)($data[0] ?? ''));
                $siren = preg_replace('/[^0-9]/', '', $sirenRaw);
                if (strlen($siren) !== 9) {
                    continue; // Ignorer les lignes sans SIREN à 9 chiffres
                }

                $montant = \App\Helpers\EcoHelper::cleanDecimal($data[1] ?? '0');
                $tonnage = \App\Helpers\EcoHelper::cleanDecimal($data[2] ?? '0');
                $poidsKg = ($uniteTonnage === 't') ? ($tonnage * 1000) : $tonnage;

                if (!isset($aggregated[$siren])) {
                    $aggregated[$siren] = [
                        'siren'        => $siren,
                        'montant'      => 0.0,
                        'poids'        => 0.0,
                        'nb_ecritures' => 0
                    ];
                }
                $aggregated[$siren]['montant'] += $montant;
                $aggregated[$siren]['poids']   += $poidsKg;
                $aggregated[$siren]['nb_ecritures']++;
            }
            fclose($handle);

            if (empty($aggregated)) {
                throw new Exception("Le fichier CSV ne contenait aucune ligne valide avec un numéro de SIREN à 9 chiffres.");
            }

            foreach ($aggregated as $siren => $item) {
                $montant = $item['montant'];
                $poidsKg = $item['poids'];
                $totalLignes++;

                // 1. Chercher si le SIREN existe déjà dans api_resultats
                $stmtFindSiren->execute([$siren]);
                $existing = $stmtFindSiren->fetch(\PDO::FETCH_ASSOC);

                if ($existing) {
                    $apiId = (int)$existing['id'];
                    $nom = $existing['nom_complet'] ?: "Fournisseur $siren";
                    $stmtMarkQualifie->execute([$apiId]);
                    $statut = 'valide_auto';
                    $reconnusCount++;
                } else {
                    // 2. Interroger l'API Sirene Gouv
                    $nom = "Fournisseur SIREN $siren";
                    $apiId = null;
                    $statut = 'en_attente';

                    $url = 'https://recherche-entreprises.api.gouv.fr/search?q=' . urlencode($siren) . '&per_page=1';
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                    $allowSelfSigned = (bool) \App\Config\Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
                    $resp = curl_exec($ch);
                    curl_close($ch);

                    if ($resp) {
                        $json = json_decode($resp, true);
                        $ent = $json['results'][0] ?? null;
                        if ($ent) {
                            $nom = $ent['nom_complet'] ?? $nom;
                            $naf = $ent['activite_principale'] ?? null;
                            $nafLib = $naf ? \App\Helpers\ApiHelper::getLibelleNafLocal($naf) : null;
                            $adr = $ent['siege']['adresse'] ?? null;
                            $lat = $ent['siege']['latitude'] ?? null;
                            $lon = $ent['siege']['longitude'] ?? null;
                            if (empty($lat) || empty($lon)) {
                                $coords = \App\Helpers\ApiHelper::geocodeSmarter($adr);
                                if ($coords) {
                                    $lat = $coords['lat'];
                                    $lon = $coords['lon'];
                                }
                            }
                            $dist = ($lat && $lon) ? \App\Helpers\ApiHelper::calculerDistance($origineLat, $origineLon, $lat, $lon) : null;
                            $ogeo = \App\Helpers\ApiHelper::determinerOrigineGeo($dist, $adr);
                            $sante = \App\Helpers\ApiHelper::determinerSanteJuridique(['siren' => $siren, 'etat_administratif' => $ent['etat_administratif'] ?? 'A']);
                            $estEss = ($ent['complements']['est_ess'] ?? false) ? 1 : 0;
                            $estMission = ($ent['complements']['est_societe_mission'] ?? false) ? 1 : 0;
                            $estAlim = 0;

                            $stmtInsertApiResult->execute([
                                $siren, $nom, $naf, $nafLib, $estAlim, $estEss, $estMission, $sante, $adr, $lat, $lon, $dist, $ogeo
                            ]);
                            $apiId = (int)$this->pdo->lastInsertId();
                            $statut = 'valide_auto';
                            $creesCount++;
                        } else {
                            $enAttenteCount++;
                        }
                    } else {
                        $enAttenteCount++;
                    }
                }

                // 3. Gestion des doublons pour la même année (cumul sur ligne existante en base)
                $existingSourceId = null;
                if ($apiId) {
                    $stmtCheckDuplicate->execute([$apiId, $annee]);
                    $existingSourceId = $stmtCheckDuplicate->fetchColumn();
                } else {
                    $stmtCheckDupAttente = $this->pdo->prepare("SELECT id FROM sources_csv WHERE nom_recherche = ? AND annee = ? AND api_result_id_selectionne IS NULL LIMIT 1");
                    $stmtCheckDupAttente->execute(["Fournisseur SIREN $siren", $annee]);
                    $existingSourceId = $stmtCheckDupAttente->fetchColumn();
                }

                if ($existingSourceId) {
                    $stmtUpdateDuplicate->execute([$montant, $poidsKg, $existingSourceId]);
                    $cumulesCount++;
                    continue;
                }

                // 4. Insertion dans sources_csv
                $stmtInsertSource->execute([
                    $societeId, $nom, $montant, $poidsKg, $statut, $apiId, $annee
                ]);
            }

            if ($totalLignes === 0) {
                throw new Exception("Le fichier CSV ne contenait aucune ligne valide avec un numéro de SIREN à 9 chiffres.");
            }

            $msg = "Importation SIREN réussie pour l'exercice $annee : $totalLignes flux importé(s) ";
            $msg .= "($reconnusCount reconnu(s) dans l'inventaire, $creesCount qualifié(s) automatiquement";
            if ($cumulesCount > 0) $msg .= ", $cumulesCount cumulé(s) sur lignes existantes";
            if ($enAttenteCount > 0) $msg .= ", $enAttenteCount placé(s) en attente";
            $msg .= ").";

            $_SESSION['flash_message'] = $msg;
            $_SESSION['flash_type'] = "success";

        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Erreur lors de l'import SIREN : " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }

        header("Location: " . $redirect);
        exit;
    }

    /**
     * Étape 1 pour la progression : Analyse du fichier CSV et renvoi de la liste des flux
     */
    public function parseSirenCsv() {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.");
            }

            $societeId = (int)($_POST['societe_id'] ?? 1);
            $annee = (int)($_POST['annee'] ?? date('Y'));
            $hasHeader = isset($_POST['has_header']) && $_POST['has_header'] == '1';
            $uniteTonnage = $_POST['unite_tonnage'] ?? 't';

            // Vérification de la clôture d'exercice
            $stmtCheckCloture = $this->pdo->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
            $stmtCheckCloture->execute([$annee]);
            if ($stmtCheckCloture->fetchColumn() === 'cloture') {
                throw new Exception("L'exercice comptable $annee est clôturé et verrouillé. Importation impossible.");
            }

            if (empty($_FILES['csv_file_siren']['tmp_name']) || !file_exists($_FILES['csv_file_siren']['tmp_name'])) {
                throw new Exception("Aucun fichier CSV sélectionné ou téléversé.");
            }

            $file = $_FILES['csv_file_siren']['tmp_name'];
            $content = file_get_contents($file);
            if (!mb_detect_encoding($content, 'UTF-8', true)) {
                $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
            }

            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $content);
            rewind($handle);

            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            $delimiter = ';';
            if (substr_count((string)$firstLine, ',') > substr_count((string)$firstLine, ';')) {
                $delimiter = ',';
            } elseif (substr_count((string)$firstLine, "\t") > substr_count((string)$firstLine, ';')) {
                $delimiter = "\t";
            }
            fseek($handle, ($bom === "\xEF\xBB\xBF") ? 3 : 0);

            if ($hasHeader) {
                fgetcsv($handle, 1000, $delimiter);
            }

            $aggregated = [];
            $totalLignesFichier = 0;

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$data || (count($data) === 1 && trim((string)($data[0] ?? '')) === '')) {
                    continue;
                }
                $totalLignesFichier++;

                $sirenRaw = trim((string)($data[0] ?? ''));
                $siren = preg_replace('/[^0-9]/', '', $sirenRaw);
                if (strlen($siren) !== 9) {
                    continue;
                }
                $montant = \App\Helpers\EcoHelper::cleanDecimal($data[1] ?? '0');
                $tonnage = \App\Helpers\EcoHelper::cleanDecimal($data[2] ?? '0');
                $poidsKg = ($uniteTonnage === 't') ? ($tonnage * 1000) : $tonnage;

                if (!isset($aggregated[$siren])) {
                    $aggregated[$siren] = [
                        'siren'        => $siren,
                        'montant'      => 0.0,
                        'poids'        => 0.0,
                        'nb_ecritures' => 0
                    ];
                }
                $aggregated[$siren]['montant'] += $montant;
                $aggregated[$siren]['poids']   += $poidsKg;
                $aggregated[$siren]['nb_ecritures']++;
            }
            fclose($handle);

            if (empty($aggregated)) {
                throw new Exception("Le fichier ne contient aucun SIREN valide à 9 chiffres.");
            }

            $items = array_values($aggregated);

            echo json_encode([
                'success'             => true,
                'annee'               => $annee,
                'societe_id'          => $societeId,
                'total_lignes_brutes' => $totalLignesFichier,
                'total'               => count($items),
                'items'               => $items
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Étape 2 pour la progression : Traitement unitaire asynchrone d'une ligne d'achat
     */
    public function processSirenItem() {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $siren = preg_replace('/[^0-9]/', '', $_POST['siren'] ?? '');
            if (strlen($siren) !== 9) {
                throw new Exception("SIREN invalide.");
            }

            $montant = \App\Helpers\EcoHelper::cleanDecimal($_POST['montant'] ?? 0);
            $poidsKg = \App\Helpers\EcoHelper::cleanDecimal($_POST['poids'] ?? 0);
            $societeId = (int)($_POST['societe_id'] ?? 1);
            $annee = (int)($_POST['annee'] ?? date('Y'));

            // Vérifier si l'exercice est clôturé
            $stmtCheckCloture = $this->pdo->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
            $stmtCheckCloture->execute([$annee]);
            if ($stmtCheckCloture->fetchColumn() === 'cloture') {
                throw new Exception("Exercice clôturé.");
            }

            $origine = \App\Helpers\ApiHelper::getOrigineCoordinates(null, $societeId);
            $origineLat = $origine['lat'];
            $origineLon = $origine['lon'];

            // 1. Chercher si le tiers existe déjà dans api_resultats
            $stmtFind = $this->pdo->prepare("SELECT id, nom_complet FROM api_resultats WHERE siren = ? LIMIT 1");
            $stmtFind->execute([$siren]);
            $existing = $stmtFind->fetch(\PDO::FETCH_ASSOC);

            $resultType = 'connu';
            $nom = "Fournisseur SIREN $siren";
            $apiId = null;

            if ($existing) {
                $apiId = (int)$existing['id'];
                $nom = $existing['nom_complet'] ?: $nom;
                $this->pdo->prepare("UPDATE api_resultats SET est_qualifie = 1 WHERE id = ?")->execute([$apiId]);
                $statut = 'valide_auto';
                $resultType = 'connu';
            } else {
                // 2. Interroger l'API Sirene Gouv
                $url = 'https://recherche-entreprises.api.gouv.fr/search?q=' . urlencode($siren) . '&per_page=1';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0');
                curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                $allowSelfSigned = (bool) \App\Config\Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
                $resp = curl_exec($ch);
                curl_close($ch);

                if ($resp) {
                    $json = json_decode($resp, true);
                    $ent = $json['results'][0] ?? null;
                    if ($ent) {
                        $nom = $ent['nom_complet'] ?? $nom;
                        $naf = $ent['activite_principale'] ?? null;
                        $nafLib = $naf ? \App\Helpers\ApiHelper::getLibelleNafLocal($naf) : null;
                        $adr = $ent['siege']['adresse'] ?? null;
                        $lat = $ent['siege']['latitude'] ?? null;
                        $lon = $ent['siege']['longitude'] ?? null;
                        if (empty($lat) || empty($lon)) {
                            $coords = \App\Helpers\ApiHelper::geocodeSmarter($adr);
                            if ($coords) {
                                $lat = $coords['lat'];
                                $lon = $coords['lon'];
                            }
                        }
                        $dist = ($lat && $lon) ? \App\Helpers\ApiHelper::calculerDistance($origineLat, $origineLon, $lat, $lon) : null;
                        $ogeo = \App\Helpers\ApiHelper::determinerOrigineGeo($dist, $adr);
                        $sante = \App\Helpers\ApiHelper::determinerSanteJuridique(['siren' => $siren, 'etat_administratif' => $ent['etat_administratif'] ?? 'A']);
                        $estEss = ($ent['complements']['est_ess'] ?? false) ? 1 : 0;
                        $estMission = ($ent['complements']['est_societe_mission'] ?? false) ? 1 : 0;

                        $stmtInsApi = $this->pdo->prepare("
                            INSERT INTO api_resultats 
                            (siren, nom_complet, activite_principale, activite_principale_libelle, est_alimentaire, est_ess, est_societe_mission, statut_juridique, siege_adresse, latitude, longitude, distance, origine_geo, est_connu, est_qualifie)
                            VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
                        ");
                        $stmtInsApi->execute([
                            $siren, $nom, $naf, $nafLib, $estEss, $estMission, $sante, $adr, $lat, $lon, $dist, $ogeo
                        ]);
                        $apiId = (int)$this->pdo->lastInsertId();
                        $statut = 'valide_auto';
                        $resultType = 'nouveau_qualifie';
                    } else {
                        $statut = 'en_attente';
                        $resultType = 'en_attente';
                    }
                } else {
                    $statut = 'en_attente';
                    $resultType = 'en_attente';
                }
            }

            // 3. Cumul anti-doublon pour le même exercice (si présent dans l'exercice)
            $existingSourceId = null;
            if ($apiId) {
                $stmtCheckDup = $this->pdo->prepare("
                    SELECT id FROM sources_csv 
                    WHERE api_result_id_selectionne = ? AND annee = ? AND statut IN ('valide_auto', 'valide_manuel') 
                    LIMIT 1
                ");
                $stmtCheckDup->execute([$apiId, $annee]);
                $existingSourceId = $stmtCheckDup->fetchColumn();
            } else {
                $stmtCheckDupAttente = $this->pdo->prepare("SELECT id FROM sources_csv WHERE nom_recherche = ? AND annee = ? AND api_result_id_selectionne IS NULL LIMIT 1");
                $stmtCheckDupAttente->execute(["Fournisseur SIREN $siren", $annee]);
                $existingSourceId = $stmtCheckDupAttente->fetchColumn();
            }

            if ($existingSourceId) {
                $this->pdo->prepare("UPDATE sources_csv SET montant = montant + ?, poids = poids + ? WHERE id = ?")
                          ->execute([$montant, $poidsKg, $existingSourceId]);
                echo json_encode([
                    'success' => true,
                    'type'    => 'cumule',
                    'nom'     => $nom,
                    'siren'   => $siren
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 4. Insertion de la nouvelle ligne consolidée
            $stmtInsSource = $this->pdo->prepare("
                INSERT INTO sources_csv (societe_id, nom_recherche, montant, poids, statut, api_result_id_selectionne, annee, date_import)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtInsSource->execute([$societeId, $nom, $montant, $poidsKg, $statut, $apiId, $annee]);

            echo json_encode([
                'success' => true,
                'type'    => $resultType,
                'nom'     => $nom,
                'siren'   => $siren
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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