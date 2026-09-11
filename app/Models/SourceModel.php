<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class SourceModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getEnAttente($societeId = 'all') {
        $sql = "SELECT sc.*, soc.nom as societe_nom FROM sources_csv sc LEFT JOIN societes soc ON sc.societe_id = soc.id WHERE sc.statut = 'en_attente'";
        $params = [];

        if ($societeId !== 'all') {
            $sql .= " AND sc.societe_id = :societe_id";
            $params[':societe_id'] = $societeId;
        }

        $sql .= " ORDER BY sc.id DESC LIMIT 1000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIntrouvables($societeId = 'all') {
        $sql = "SELECT sc.*, soc.nom as societe_nom FROM sources_csv sc LEFT JOIN societes soc ON sc.societe_id = soc.id WHERE sc.statut = 'introuvable'";
        $params = [];

        if ($societeId !== 'all') {
            $sql .= " AND sc.societe_id = :societe_id";
            $params[':societe_id'] = $societeId;
        }

        $sql .= " ORDER BY sc.id DESC LIMIT 1000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getValidees($societeId = 'all') {
        $sql = "
            SELECT s.id as source_id, s.societe_id, s.nom_recherche, s.statut, s.date_import, s.montant, s.poids, COALESCE(s.annee, 2024) as annee,
                   r.id as resultat_id, r.siren, r.nom_complet, r.siege_adresse, r.latitude, r.longitude, r.distance, r.origine_geo,
                   r.activite_principale, COALESCE(n.libelle, r.activite_principale_libelle) as activite_principale_libelle, 
                   r.est_alimentaire, r.est_ess, r.est_societe_mission, r.est_connu, r.statut_juridique,
                   soc.nom as societe_nom
            FROM sources_csv s 
            INNER JOIN api_resultats r ON s.api_result_id_selectionne = r.id
            LEFT JOIN codes_naf n ON UPPER(REPLACE(n.code, '.', '')) = UPPER(REPLACE(r.activite_principale, '.', ''))
            LEFT JOIN societes soc ON s.societe_id = soc.id
            WHERE s.statut IN ('valide_auto', 'valide_manuel')
        ";
        $params = [];

        if ($societeId !== 'all') {
            $sql .= " AND s.societe_id = :societe_id";
            $params[':societe_id'] = $societeId;
        }

        $sql .= " ORDER BY s.id DESC LIMIT 1000";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}