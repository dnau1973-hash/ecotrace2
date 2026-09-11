<?php
namespace App\Controllers;

use App\Config\Database;

class InternalEmissionsController {
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $societeId = $_POST['societe_id'] ?? null;
            $annee = $_POST['annee'] ?? date('Y');
            $categorie = $_POST['categorie'] ?? '';
            $description = $_POST['description'] ?? '';
            $quantite = (float)str_replace(',', '.', $_POST['quantite'] ?? 0);
            $unite = $_POST['unite'] ?? '';
            $facteur = (float)str_replace(',', '.', $_POST['facteur_emission'] ?? 0);
            
            // Allow default active society fallback
            if (!$societeId || $societeId == 'all') {
                $societeId = $_SESSION['active_societe_id'] ?? null;
            }
            
            if ($societeId && $societeId != 'all' && $quantite > 0 && $facteur > 0) {
                $totalCo2 = $quantite * $facteur;
                
                $db = Database::getConnection();
                $stmt = $db->prepare("INSERT INTO scope1_emissions (societe_id, annee, categorie, description, quantite, unite, facteur_emission, total_co2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$societeId, $annee, $categorie, $description, $quantite, $unite, $facteur, $totalCo2]);
                
                $_SESSION['flash_message'] = "Consommation ajoutée avec succès.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Erreur: Veuillez sélectionner une filiale spécifique, indiquer une quantité et un facteur valides.";
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: ?");
        exit;
    }
    
    public function delete() {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM scope1_emissions WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['flash_message'] = "Consommation supprimée.";
            $_SESSION['flash_type'] = "success";
        }
        header("Location: ?");
        exit;
    }
}
