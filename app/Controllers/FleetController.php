<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class FleetController {

    public function manage() {
        // Optionnel : Une page entière dédiée, ou juste retourner à l'index (car géré dans le dashboard)
        header("Location: ?");
        exit;
    }

    public function addVehicle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $societeId = $_POST['societe_id'] ?? null;
            $plaque = strtoupper(trim($_POST['plaque'] ?? ''));
            $referentielId = (int)($_POST['referentiel_id'] ?? 0);

            if ($societeId && $plaque && $referentielId > 0) {
                $db = Database::getConnection();
                
                // Fetch details from referential
                $stmt = $db->prepare("SELECT * FROM referentiel_vehicules WHERE id = ?");
                $stmt->execute([$referentielId]);
                $ref = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$ref) {
                    $_SESSION['flash_message'] = "Modèle de référence introuvable.";
                    $_SESSION['flash_type'] = "danger";
                    header("Location: ?");
                    exit;
                }
                
                $marque = $ref['marque'];
                $modele = $ref['modele'];
                $carburant = $ref['carburant'];
                $conso = $ref['conso_moyenne'];
                $db = Database::getConnection();
                
                // Vérifier si la plaque existe déjà pour éviter les doublons
                $stmt = $db->prepare("SELECT id FROM flotte_vehicules WHERE plaque = ? AND societe_id = ?");
                $stmt->execute([$plaque, $societeId]);
                if ($stmt->fetch()) {
                    $_SESSION['flash_message'] = "Ce véhicule (Plaque: $plaque) existe déjà dans votre inventaire.";
                    $_SESSION['flash_type'] = "warning";
                } else {
                    $stmt = $db->prepare("INSERT INTO flotte_vehicules (societe_id, plaque, marque, modele, carburant, conso_moyenne) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$societeId, $plaque, $marque, $modele, $carburant, $conso]);
                    
                    $_SESSION['flash_message'] = "Véhicule ajouté à la flotte avec succès.";
                    $_SESSION['flash_type'] = "success";
                }
            } else {
                $_SESSION['flash_message'] = "Veuillez remplir tous les champs correctement.";
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: ?");
        exit;
    }

        public function editVehicle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $societeId = $_POST['societe_id'] ?? null;
            $plaque = strtoupper(trim($_POST['plaque'] ?? ''));
            $statut = $_POST['statut'] ?? 'Actif';
            $referentielId = (int)($_POST['referentiel_id'] ?? 0);

            if ($id > 0 && $societeId && $plaque) {
                $db = Database::getConnection();
                
                if ($referentielId > 0) {
                    $stmt = $db->prepare("SELECT * FROM referentiel_vehicules WHERE id = ?");
                    $stmt->execute([$referentielId]);
                    $ref = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($ref) {
                        $stmt = $db->prepare("UPDATE flotte_vehicules SET societe_id=?, plaque=?, statut=?, marque=?, modele=?, carburant=?, conso_moyenne=? WHERE id=?");
                        $stmt->execute([$societeId, $plaque, $statut, $ref['marque'], $ref['modele'], $ref['carburant'], $ref['conso_moyenne'], $id]);
                    }
                } else {
                    $stmt = $db->prepare("UPDATE flotte_vehicules SET societe_id=?, plaque=?, statut=? WHERE id=?");
                    $stmt->execute([$societeId, $plaque, $statut, $id]);
                }
                
                $_SESSION['flash_message'] = "Le véhicule a été mis à jour avec succès.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Veuillez remplir correctement les champs obligatoires.";
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: ?");
        exit;
    }

    public function deleteVehicle() {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM flotte_vehicules WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['flash_message'] = "Véhicule supprimé de l'inventaire.";
            $_SESSION['flash_type'] = "success";
        }
        header("Location: ?");
        exit;
    }

    public function importMileage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_mileage'])) {
            $file = $_FILES['file_mileage']['tmp_name'];
            $annee = (int)($_POST['annee'] ?? date('Y'));
            $societeId = $_POST['societe_id'] ?? null;

            if ($societeId == 'all' || !$societeId) {
                $_SESSION['flash_message'] = "Veuillez sélectionner une société pour l'import.";
                $_SESSION['flash_type'] = "danger";
                header("Location: ?");
                exit;
            }

            if (is_uploaded_file($file)) {
                $db = Database::getConnection();
                $handle = fopen($file, "r");
                $imported = 0;
                $not_found = 0;
                $errors = [];

                if ($handle !== FALSE) {
                    $firstLine = fgets($handle);
                    $delim = (strpos($firstLine, ';') !== false) ? ';' : ',';
                    rewind($handle);
                    fgetcsv($handle, 1000, $delim); // Skip header
                    
                    $db->beginTransaction();
                    try {
                        while (($data = fgetcsv($handle, 1000, $delim)) !== FALSE) {
                            if (count($data) >= 2) {
                                $plaque = strtoupper(trim($data[0]));
                                $kilometrage = (float)str_replace(',', '.', $data[1]);
                                // On permet une 3ème colonne Année optionnelle
                                $lineAnnee = isset($data[2]) && !empty(trim($data[2])) ? (int)trim($data[2]) : $annee;

                                if (empty($plaque) || $kilometrage <= 0) continue;

                                // Recherche du véhicule dans la flotte
                                $stmt = $db->prepare("SELECT * FROM flotte_vehicules WHERE plaque = ? AND societe_id = ? LIMIT 1");
                                $stmt->execute([$plaque, $societeId]);
                                $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($vehicule) {
                                    $consoMoyenne = (float)$vehicule['conso_moyenne'];
                                    $quantiteL = ($kilometrage / 100) * $consoMoyenne;
                                    
                                    // Déterminer la catégorie et le facteur d'émission (simplifié)
                                    $carburant = $vehicule['carburant'];
                                    $categorie = "Combustion Mobile ($carburant)";
                                    $fe = 1.0;
                                    $unite = "Litres";
                                    
                                    if (stripos($carburant, 'gazole') !== false || stripos($carburant, 'diesel') !== false) {
                                        $fe = 2.8;
                                    } elseif (stripos($carburant, 'essence') !== false) {
                                        $fe = 2.5;
                                    } elseif (stripos($carburant, 'electri') !== false) {
                                        $categorie = "Électricité (Flotte Mobile)";
                                        $fe = 0.05;
                                        $unite = "kWh";
                                    }

                                    $totalCo2 = $quantiteL * $fe;
                                    $description = "Véhicule $plaque (" . $vehicule['marque'] . " " . $vehicule['modele'] . ") - Relevé kilométrique";

                                    $ins = $db->prepare("INSERT INTO scope1_emissions (societe_id, annee, categorie, description, quantite, unite, facteur_emission, total_co2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                    $ins->execute([$societeId, $lineAnnee, $categorie, $description, $quantiteL, $unite, $fe, $totalCo2]);
                                    
                                    $imported++;
                                } else {
                                    $not_found++;
                                    $errors[] = $plaque;
                                }
                            }
                        }
                        $db->commit();
                        
                        $msg = "$imported relevés importés avec succès.";
                        if ($not_found > 0) {
                            $msg .= " $not_found plaques non trouvées dans l'inventaire (" . implode(', ', array_slice($errors, 0, 5)) . "...).";
                            $_SESSION['flash_type'] = "warning";
                        } else {
                            $_SESSION['flash_type'] = "success";
                        }
                        $_SESSION['flash_message'] = $msg;
                        
                    } catch (\Exception $e) {
                        $db->rollBack();
                        $_SESSION['flash_message'] = "Erreur lors de l'import : " . $e->getMessage();
                        $_SESSION['flash_type'] = "danger";
                    }
                    fclose($handle);
                }
            } else {
                $_SESSION['flash_message'] = "Erreur de fichier.";
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: ?");
        exit;
    }
}
