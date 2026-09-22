<?php ini_set("display_errors", 1); error_reporting(E_ALL); ?>
<?php
session_start();

// Par défaut, masquer les erreurs (surcharge possible après lecture du .env)
ini_set('display_errors', 0);
error_reporting(0);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\InstallController;
use App\Controllers\MainController;
use App\Controllers\SocieteController;
use App\Controllers\MaintenanceController;

Database::loadEnv();
// Après avoir chargé le .env et l'autoload, appliquer APP_DEBUG
$debug = \App\Config\Database::getEnv('APP_DEBUG', 0);
ini_set('display_errors', $debug ? 1 : 0);
error_reporting($debug ? E_ALL : 0);

/**
 * Tentative de routage dynamique pour les actions GET.
 * Retourne true si une action a été dispatchée.
 */
function attemptDynamicGetRoute(string $action): bool {
    // Cas spécial : changement de société doit aller vers MainController::changeSociete
    if (strpos($action, 'change_') === 0) {
        $mc = '\\App\\Controllers\\MainController';
        if (class_exists($mc)) {
            try {
                $c = new $mc();
                if (method_exists($c, 'changeSociete')) { $c->changeSociete(); return true; }
            } catch (\Throwable $e) {}
        }
    }
    $parts = explode('_', $action);

    // Construire une liste de candidats de contrôleurs
    $candidates = [];
    // Premier candidat basé sur le premier segment
    $candidates[] = ucfirst($parts[0]) . 'Controller';
    // Ajouter des candidats basés sur chaque segment (singulier simplifié)
    foreach ($parts as $p) {
        $candidates[] = ucfirst(rtrim($p, 's')) . 'Controller';
    }

    // Ajouter tous les contrôleurs existants dans le dossier pour avoir plus de chances
    $ctrlDir = __DIR__ . '/../app/Controllers';
    foreach (glob($ctrlDir . '/*.php') as $f) {
        $candidates[] = basename($f, '.php');
    }

    $candidates = array_unique($candidates);

    foreach ($candidates as $ctrlName) {
        $fullClass = '\\App\\Controllers\\' . $ctrlName;
        if (!class_exists($fullClass)) continue;

        try {
            $controller = new $fullClass();
        } catch (\Throwable $e) {
            continue; // constructeur incompatible
        }

        // Générer des noms de méthodes candidats
        $tail = array_slice($parts, 1);
        $firstTail = $tail[0] ?? null;
        $camelAll = lcfirst(implode('', array_map('ucfirst', $parts)));
        $camelTail = lcfirst(implode('', array_map('ucfirst', $tail)));

        $methodCandidates = [];
        if ($camelTail) {
            $methodCandidates[] = $camelTail; // ex: sirenProcess
            if ($firstTail) {
                $methodCandidates[] = 'process' . ucfirst($firstTail); // processSiren
                $methodCandidates[] = 'show' . ucfirst($firstTail) . 'Form'; // showSirenForm
            }
        }
        // Génériques fréquemment utilisés
        $methodCandidates[] = $camelAll; // ex: importSirenProcess
        $methodCandidates[] = 'showForm';
        $methodCandidates[] = 'process';
        $methodCandidates[] = 'index';

        foreach ($methodCandidates as $m) {
            if (method_exists($controller, $m)) {
                // Appel de la méthode
                $controller->{$m}();
                return true;
            }
        }
    }

    return false;
}

$authController = new AuthController();
$authController->handleSetup();
$authController->handleLogin();

// --- INTERCEPTION DES REQUÊTES AJAX POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 1. Vidage des tables sélectionnées
    if ($_POST['action'] === 'api_empty_tables') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->emptyTables();
        exit;
    }
    
    // 2. Actions AJAX des Sociétés
    if ($_POST['action'] === 'save_societe_ajax') {
        $societeController = new SocieteController();
        $societeController->saveAjax();
        exit;
    }
    
    if ($_POST['action'] === 'delete_societe_ajax') {
        $societeController = new SocieteController();
        $societeController->deleteAjax();
        exit;
    }

    if ($_POST['action'] === 'geocode_societe_ajax') {
        $societeController = new SocieteController();
        $societeController->geocodeAjax();
        exit;
    }

    // 3. Mise à jour GitHub (AJAX)
    if ($_POST['action'] === 'check_update_ajax') {
        $updateController = new \App\Controllers\UpdateController();
        $updateController->checkAjax();
        exit;
    }

    if ($_POST['action'] === 'apply_update_ajax') {
        $updateController = new \App\Controllers\UpdateController();
        $updateController->applyAjax();
        exit;
    }

    // 4. Routage API standard (recherches, etc.)
    $apiController = new \App\Controllers\ApiController();
    $apiController->handle();
    exit;
}

// --- GESTION DE LA CONFIGURATION (Accessible via ?edit_config=1 ou ?action=edit_config) ---
if (isset($_GET['edit_config']) || (isset($_GET['action']) && $_GET['action'] === 'edit_config')) {
    $configController = new \App\Controllers\ConfigController();
    $configController->showForm();
    exit;
}

// --- GESTION DES ACTIONS GET & POST VIA ?action= ---
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Export CSV
    if ($action === 'export_csv') {
        $mainController = new MainController();
        $mainController->exportCsv();
        exit;
    } elseif ($action === 'change_societe') {
        $mainController = new MainController();
        $mainController->changeSociete();
        exit;
    } elseif ($action === 'manage_societes') {
        $societeController = new SocieteController();
        $societeController->index();
        exit;
    } elseif ($action === 'save_config' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $configController = new \App\Controllers\ConfigController();
        $configController->save();
        exit;
    } elseif ($action === 'uninstall' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $adminController = new \App\Controllers\AdminController();
        $adminController->uninstall();
        exit;
    } elseif ($action === 'admin_database') {
        $adminController = new \App\Controllers\AdminController();
        $adminController->database();
        exit;
    } elseif ($action === 'updates') {
        $updateController = new \App\Controllers\UpdateController();
        $updateController->index();
        exit;

    } elseif ($action === 'manage_users') {
        $userController = new \App\Controllers\UserController();
        $userController->manage();
        exit;
    } elseif ($action === 'save_user_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController = new \App\Controllers\UserController();
        $userController->saveAjax();
        exit;
    } elseif ($action === 'delete_user_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController = new \App\Controllers\UserController();
        $userController->deleteAjax();
        exit;
    } elseif ($action === 'change_password_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController = new \App\Controllers\UserController();
        $userController->changePasswordAjax();
        exit;
    } elseif ($action === 'admin_truncate_table') {
        $adminController = new \App\Controllers\AdminController();
        $adminController->truncateTable();
        exit;
    } elseif ($action === 'import_csv') {
        $importController = new \App\Controllers\ImportController();
        $importController->showForm();
        exit;
    } elseif ($action === 'import_csv_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $importController = new \App\Controllers\ImportController();
        $importController->process();
        exit;
    } elseif ($action === 'import_siren') {
        $importController = new \App\Controllers\ImportController();
        $importController->showSirenForm();
        exit;
    } elseif ($action === 'import_siren_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $importController = new \App\Controllers\ImportController();
        $importController->processSiren();
        exit;
    } elseif ($action === 'export_sql') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->exportSql();
        exit;
    } elseif ($_GET['action'] === 'import_sql') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->showImportSqlForm();
        exit;
    } elseif ($_GET['action'] === 'import_sql_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->processImportSql();
        exit;    
    } elseif ($_GET['action'] === 'api_get_distances_todo') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->getDistancesToFix();
        exit;
    } elseif ($_GET['action'] === 'api_fix_distance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->fixSingleDistance();
        exit;
    } elseif ($_GET['action'] === 'api_get_geo_todo') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->getGeoToFix();
        exit;
    } elseif ($_GET['action'] === 'api_fix_geo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->fixSingleGeo();
        exit;
    } elseif ($_GET['action'] === 'report') {
        $reportCtrl = new \App\Controllers\ReportController();
        $reportCtrl->generate();
        exit;
    } elseif ($_GET['action'] === 'api_maj_naf') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->apiMajNaf();
        exit;
    } elseif ($_GET['action'] === 'api_valider_connus') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->apiValiderConnus();
        exit;
    } elseif ($_GET['action'] === 'api_relancer_introuvables') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->apiRelancerIntrouvables();
        exit;
    } elseif ($_GET['action'] === 'api_valider_etranger') {
        $apiController = new ApiController();
        $apiController->validerEtranger();
        exit;
    } elseif ($_GET['action'] === 'manage_naf') {
        $nafController = new \App\Controllers\NafController();
        $nafController->manage();
        exit;
    } elseif ($_GET['action'] === 'save_naf' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nafController = new \App\Controllers\NafController();
        $nafController->save();
        exit;
    } elseif ($_GET['action'] === 'manage_ademe') {
        $ademeController = new \App\Controllers\AdemeController();
        $ademeController->manage();
        exit;
    } elseif ($_GET['action'] === 'save_ademe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ademeController = new \App\Controllers\AdemeController();
        $ademeController->save();
        exit;
    } elseif ($_GET['action'] === 'add_scope1' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $scope1Ctrl = new \App\Controllers\InternalEmissionsController();
        $scope1Ctrl->add();
        exit;
        } elseif ($_GET['action'] === 'add_vehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $fleetCtrl = new \App\Controllers\FleetController();
        $fleetCtrl->addVehicle();
        exit;
    } elseif ($_GET['action'] === 'api_estimate_conso') {
        $refCtrl = new \App\Controllers\ReferentielController();
        $refCtrl->estimateConsoAjax();
        exit;
    } elseif ($_GET['action'] === 'manage_referentiel') {
        $refCtrl = new \App\Controllers\ReferentielController();
        $refCtrl->manage();
        exit;
    } elseif ($_GET['action'] === 'save_referentiel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $refCtrl = new \App\Controllers\ReferentielController();
        $refCtrl->save();
        exit;
    } elseif ($_GET['action'] === 'delete_referentiel' && isset($_GET['id'])) {
        $refCtrl = new \App\Controllers\ReferentielController();
        $refCtrl->delete();
        exit;
    } elseif ($_GET['action'] === 'edit_vehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $fleetCtrl = new \App\Controllers\FleetController();
        $fleetCtrl->editVehicle();
        exit;
    } elseif ($_GET['action'] === 'delete_vehicle' && isset($_GET['id'])) {
        $fleetCtrl = new \App\Controllers\FleetController();
        $fleetCtrl->deleteVehicle();
        exit;
    } elseif ($_GET['action'] === 'import_mileage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $fleetCtrl = new \App\Controllers\FleetController();
        $fleetCtrl->importMileage();
        exit;
    } elseif ($_GET['action'] === 'delete_scope1' && isset($_GET['id'])) {
        $scope1Ctrl = new \App\Controllers\InternalEmissionsController();
        $scope1Ctrl->delete();
        exit;
    } elseif ($_GET['action'] === 'api_repasser_attente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->repasserEnAttente();
        exit;
    } elseif ($_GET['action'] === 'add_achat_scope3' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = \App\Config\Database::getConnection();
        $societeId = (int)($_POST['societe_id'] ?? 1);
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $apiId = (int)($_POST['api_result_id'] ?? 0);
        $montant = (float)str_replace(',', '.', $_POST['montant'] ?? 0);
        $poids = (float)str_replace(',', '.', $_POST['poids'] ?? 0);
        $redirect = $_POST['redirect_to'] ?? 'index.php';

        // Vérification clôture d'exercice
        $stmtCloture = $db->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
        $stmtCloture->execute([$annee]);
        if ($stmtCloture->fetchColumn() === 'cloture') {
            $_SESSION['flash_message'] = "Action impossible : l'exercice comptable $annee est clôturé et verrouillé.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . $redirect);
            exit;
        }

        if ($apiId > 0 && $montant >= 0) {
            // Vérifier si ce fournisseur a déjà une dépense pour cet exercice
            $stmtCheck = $db->prepare("
                SELECT id FROM sources_csv 
                WHERE api_result_id_selectionne = ? AND annee = ? AND statut IN ('valide_auto', 'valide_manuel')
                LIMIT 1
            ");
            $stmtCheck->execute([$apiId, $annee]);
            if ($stmtCheck->fetchColumn()) {
                $_SESSION['flash_message'] = "Ce fournisseur possède déjà un enregistrement pour l'exercice $annee.";
                $_SESSION['flash_type'] = "warning";
                header("Location: " . $redirect);
                exit;
            }

            $stmtNom = $db->prepare("SELECT nom_complet FROM api_resultats WHERE id = ?");
            $stmtNom->execute([$apiId]);
            $nom = $stmtNom->fetchColumn() ?: 'Fournisseur';

            $stmtIns = $db->prepare("
                INSERT INTO sources_csv (societe_id, nom_recherche, montant, poids, statut, api_result_id_selectionne, annee, date_import)
                VALUES (?, ?, ?, ?, 'valide_manuel', ?, ?, NOW())
            ");
            $stmtIns->execute([$societeId, $nom, $montant, $poids, $apiId, $annee]);
            $_SESSION['flash_message'] = "Ligne d'achat ajoutée avec succès pour l'exercice $annee.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Veuillez sélectionner un fournisseur et indiquer un montant valide.";
            $_SESSION['flash_type'] = "danger";
        }
        header("Location: " . $redirect);
        exit;
    } elseif ($_GET['action'] === 'edit_achat_scope3' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = \App\Config\Database::getConnection();
        $sourceId = (int)($_POST['source_id'] ?? 0);
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $montant = (float)str_replace(',', '.', $_POST['montant'] ?? 0);
        $poids = (float)str_replace(',', '.', $_POST['poids'] ?? 0);
        $redirect = $_POST['redirect_to'] ?? 'index.php';

        // Vérification clôture d'exercice
        $stmtCloture = $db->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
        $stmtCloture->execute([$annee]);
        if ($stmtCloture->fetchColumn() === 'cloture') {
            $_SESSION['flash_message'] = "Action impossible : l'exercice comptable $annee est clôturé et verrouillé.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . $redirect);
            exit;
        }

        if ($sourceId > 0) {
            $stmtUpd = $db->prepare("UPDATE sources_csv SET annee = ?, montant = ?, poids = ? WHERE id = ?");
            $stmtUpd->execute([$annee, $montant, $poids, $sourceId]);
            $_SESSION['flash_message'] = "Ligne d'achat mise à jour.";
            $_SESSION['flash_type'] = "success";
        }
        header("Location: " . $redirect);
        exit;
    } elseif ($_GET['action'] === 'delete_achat_scope3') {
        $db = \App\Config\Database::getConnection();
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? 'index.php';
        
        // Suppression unitaire (GET)
        if (isset($_GET['id'])) {
            $sourceId = (int)$_GET['id'];
            if ($sourceId > 0) {
                // Vérifier si l'exercice de la dépense est clôturé
                $stmtCheckAnnee = $db->prepare("SELECT annee FROM sources_csv WHERE id = ?");
                $stmtCheckAnnee->execute([$sourceId]);
                $anneeSrc = (int)$stmtCheckAnnee->fetchColumn();
                $stmtCloture = $db->prepare("SELECT statut FROM exercices_comptables WHERE annee = ?");
                $stmtCloture->execute([$anneeSrc]);
                if ($stmtCloture->fetchColumn() === 'cloture') {
                    $_SESSION['flash_message'] = "Impossible de supprimer : l'exercice comptable $anneeSrc est clôturé et verrouillé.";
                    $_SESSION['flash_type'] = "danger";
                    header("Location: " . $redirect);
                    exit;
                }

                $stmtDel = $db->prepare("DELETE FROM sources_csv WHERE id = ?");
                $stmtDel->execute([$sourceId]);
                $_SESSION['flash_message'] = "Ligne d'achat supprimée du livre de bord.";
                $_SESSION['flash_type'] = "success";
            }
        }
        // Suppression multiple (POST ids[])
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
            $cleanIds = array_map('intval', $_POST['ids']);
            $cleanIds = array_filter($cleanIds, function($id) { return $id > 0; });
            if (!empty($cleanIds)) {
                $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
                // Filtrer pour ne pas supprimer de lignes appartenant à des exercices clôturés
                $stmtVerif = $db->prepare("
                    SELECT sc.id 
                    FROM sources_csv sc 
                    JOIN exercices_comptables ec ON ec.annee = sc.annee 
                    WHERE sc.id IN ($placeholders) AND ec.statut = 'cloture'
                ");
                $stmtVerif->execute(array_values($cleanIds));
                $idsClotures = $stmtVerif->fetchAll(\PDO::FETCH_COLUMN);

                $idsDeletable = array_diff($cleanIds, $idsClotures);
                if (!empty($idsDeletable)) {
                    $delPlaceholders = implode(',', array_fill(0, count($idsDeletable), '?'));
                    $stmtDel = $db->prepare("DELETE FROM sources_csv WHERE id IN ($delPlaceholders)");
                    $stmtDel->execute(array_values($idsDeletable));
                    $count = count($idsDeletable);
                    $_SESSION['flash_message'] = "$count dépense(s) supprimée(s) avec succès du livre de bord.";
                    $_SESSION['flash_type'] = "success";
                }
                if (!empty($idsClotures)) {
                    $_SESSION['flash_message'] .= " " . count($idsClotures) . " dépense(s) ignorée(s) car appartenant à un exercice clôturé.";
                    $_SESSION['flash_type'] = "warning";
                }
            }
        }
        header("Location: " . $redirect);
        exit;
    } elseif ($_GET['action'] === 'delete_fournisseur_inventaire' && isset($_GET['id'])) {
        $db = \App\Config\Database::getConnection();
        $id = (int)$_GET['id'];
        $redirect = $_GET['redirect_to'] ?? 'index.php';
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE api_resultats SET est_qualifie = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $stmtUnlink = $db->prepare("UPDATE sources_csv SET api_result_id_selectionne = NULL, statut = 'en_attente' WHERE api_result_id_selectionne = ?");
            $stmtUnlink->execute([$id]);
            $_SESSION['flash_message'] = "Fournisseur retiré de l'inventaire qualifié.";
            $_SESSION['flash_type'] = "info";
        }
        header("Location: " . $redirect);
        exit;
    } elseif ($_GET['action'] === 'import_siren_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $importCtrl = new \App\Controllers\ImportController();
        $importCtrl->processSirenCsv();
    } elseif ($_GET['action'] === 'import_siren_csv_parse' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $importCtrl = new \App\Controllers\ImportController();
        $importCtrl->parseSirenCsv();
    } elseif ($_GET['action'] === 'import_siren_item_process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $importCtrl = new \App\Controllers\ImportController();
        $importCtrl->processSirenItem();
    } elseif ($_GET['action'] === 'toggle_cloture_exercice') {
        $db = \App\Config\Database::getConnection();
        $annee = (int)($_POST['annee'] ?? $_GET['annee'] ?? 0);
        $nouveauStatut = $_POST['statut'] ?? $_GET['statut'] ?? '';
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? 'index.php';

        if ($annee >= 2000 && in_array($nouveauStatut, ['ouvert', 'cloture'])) {
            $dateCloture = ($nouveauStatut === 'cloture') ? date('Y-m-d H:i:s') : null;
            $stmt = $db->prepare("
                INSERT INTO exercices_comptables (annee, statut, date_cloture) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE statut = VALUES(statut), date_cloture = VALUES(date_cloture)
            ");
            $stmt->execute([$annee, $nouveauStatut, $dateCloture]);

            if ($nouveauStatut === 'cloture') {
                $_SESSION['flash_message'] = "L'exercice comptable $annee a été clôturé avec succès. Les flux sont verrouillés.";
                $_SESSION['flash_type'] = "warning";
            } else {
                $_SESSION['flash_message'] = "L'exercice comptable $annee a été rouvert. Les flux sont de nouveau modifiables.";
                $_SESSION['flash_type'] = "success";
            }
        }
        header("Location: " . $redirect);
        exit;
    }

    // Tentative de routage dynamique EN DERNIER RECOURS (après les routes explicites)
    if (attemptDynamicGetRoute($action)) {
        exit;
    }
}

$installController = new InstallController();
$installController->checkInstallation();

try {
    $mainController = new MainController();
    $mainController->index();
} catch (\Throwable $e) {
    die("<div style='padding:20px;background:red;color:white;'>FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</div>");
}