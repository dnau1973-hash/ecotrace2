<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class SocieteModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT s.*, COUNT(sc.id) as total_imports FROM societes s LEFT JOIN sources_csv sc ON s.id = sc.societe_id GROUP BY s.id ORDER BY s.nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM societes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($nom, $siren = null, $codeInterne = null, $latitude = null, $longitude = null, $adresse = null, $codePostal = null, $ville = null) {
        $stmt = $this->pdo->prepare("INSERT INTO societes (nom, siren, code_interne, latitude, longitude, adresse, code_postal, ville) VALUES (:nom, :siren, :code_interne, :latitude, :longitude, :adresse, :code_postal, :ville)");
        return $stmt->execute([
            ':nom' => $nom,
            ':siren' => $siren,
            ':code_interne' => $codeInterne,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':adresse' => $adresse,
            ':code_postal' => $codePostal,
            ':ville' => $ville
        ]);
    }

    public function update($id, $nom, $siren = null, $codeInterne = null, $latitude = null, $longitude = null, $adresse = null, $codePostal = null, $ville = null) {
        $stmt = $this->pdo->prepare("UPDATE societes SET nom = :nom, siren = :siren, code_interne = :code_interne, latitude = :latitude, longitude = :longitude, adresse = :adresse, code_postal = :code_postal, ville = :ville WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':nom' => $nom,
            ':siren' => $siren,
            ':code_interne' => $codeInterne,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':adresse' => $adresse,
            ':code_postal' => $codePostal,
            ':ville' => $ville
        ]);
    }

    public function delete($id) {
        // Empêcher la suppression de la société principale par défaut (ID 1)
        if ((int)$id === 1) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM societes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}