<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class ResultatModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    // Récupère les candidats API liés à une recherche CSV
    public function getCandidatsBySourceId($sourceId) {
        $sql = "
            SELECT r.*, COALESCE(n.libelle, r.activite_principale_libelle) as activite_principale_libelle 
            FROM api_resultats r 
            LEFT JOIN codes_naf n ON UPPER(REPLACE(n.code, '.', '')) = UPPER(REPLACE(r.activite_principale, '.', '')) 
            WHERE r.source_id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $sourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}