<?php
namespace App\Controllers;

use App\Models\SocieteModel;
use App\Models\SourceModel;
use App\Config\Database;
use PDO;

class SocieteController {
    private $societeModel;
    private $sourceModel;

    public function __construct() {
        $this->societeModel = new SocieteModel();
        $this->sourceModel  = new SourceModel();
    }

    public function index() {
        $societes = $this->societeModel->getAll();
        
        // Société active en session
        $activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';

        // Calcul dynamique des compteurs de badges pour le layout
        $sourcesEnAttente   = $this->sourceModel->getEnAttente($activeSocieteId);
        $sourcesValides     = $this->sourceModel->getValidees($activeSocieteId);
        $sourcesIntrouvables = $this->sourceModel->getIntrouvables($activeSocieteId);

        $countEnAttente   = count($sourcesEnAttente);
        $countValides     = count($sourcesValides);
        $countIntrouvables = count($sourcesIntrouvables);

        // Transmission de la liste des sociétés au layout pour le sélecteur du header
        $content = $this->renderView('societes/index', ['societes' => $societes]);
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveAjax() {
        header('Content-Type: application/json');
        
        $id          = $_POST['id'] ?? null;
        $nom         = trim($_POST['nom'] ?? '');
        $siren       = trim($_POST['siren'] ?? '');
        $codeInterne = trim($_POST['code_interne'] ?? '');
        $adresse     = trim($_POST['adresse'] ?? '');
        $codePostal  = trim($_POST['code_postal'] ?? '');
        $ville       = trim($_POST['ville'] ?? '');
        $latitude    = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
        $longitude   = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

        if (empty($nom)) {
            echo json_encode(['success' => false, 'message' => 'Le nom de la société est obligatoire.']);
            exit;
        }

        try {
            if (!empty($id)) {
                $this->societeModel->update($id, $nom, $siren, $codeInterne, $latitude, $longitude, $adresse, $codePostal, $ville);
            } else {
                $this->societeModel->create($nom, $siren, $codeInterne, $latitude, $longitude, $adresse, $codePostal, $ville);
            }
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteAjax() {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);

        if ($id === 1) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer la société principale.']);
            exit;
        }

        try {
            $this->societeModel->delete($id);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function geocodeAjax() {
        header('Content-Type: application/json');
        $adresse = trim($_POST['adresse'] ?? '');
        $codePostal = trim($_POST['code_postal'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        
        $fullAddress = trim("$adresse $codePostal $ville");
        
        if (empty($fullAddress)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez saisir une adresse.']);
            exit;
        }
        
        $coords = \App\Helpers\ApiHelper::geocodeSmarter($fullAddress);
        
        if ($coords) {
            echo json_encode(['success' => true, 'lat' => $coords['lat'], 'lon' => $coords['lon']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Coordonnées introuvables pour cette adresse.']);
        }
        exit;
    }

    private function renderView($viewName, $data = []) {
        extract($data);
        ob_start();
        require __DIR__ . '/../Views/' . $viewName . '.php';
        return ob_get_clean();
    }
}