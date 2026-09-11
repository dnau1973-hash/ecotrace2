<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

class InstallController {
    
    public function checkInstallation() {
        $pdo = Database::getConnection();
        $db_name = Database::getEnv('DB_NAME', 'ecotrace');
        $needsInstall = false;

        try {
            $pdo->exec("USE `$db_name`");
            $stmt = $pdo->query("SHOW TABLES LIKE 'codes_naf'");
            if (!$stmt->fetch()) {
                $needsInstall = true;
            } else {
                // Rétrocompatibilité : si vous importez un vieux dump SQL (V1), 
                // cette fonction ajoutera silencieusement les colonnes manquantes.
                $this->updateLegacySchema($pdo);
            }
        } catch (PDOException $e) {
            $needsInstall = true; // La base n'existe pas encore
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_action'])) {
            $this->runInstall($pdo, $db_name);
        }

        if ($needsInstall) {
            require __DIR__ . '/../Views/install.php';
            exit;
        }
    }

    private function runInstall($pdo, $db_name) {
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `$db_name`;");

            // Le schéma complet et définitif de la V2.0.0
            $sql = "
                        CREATE TABLE IF NOT EXISTS societes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(255) NOT NULL,
                siren VARCHAR(9) NULL,
                code_interne VARCHAR(50) NULL,
                code_postal VARCHAR(10) NULL,
                ville VARCHAR(100) NULL,
                adresse TEXT NULL,
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(10,8) NULL,
                est_defaut TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS sources_csv (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom_recherche VARCHAR(255) NOT NULL,
                societe_id INT NULL,
                montant DECIMAL(10,2) DEFAULT 0,
                poids DECIMAL(10,2) DEFAULT 0,
                statut VARCHAR(50) DEFAULT 'en_attente', 
                api_result_id_selectionne INT NULL,
                annee INT NOT NULL DEFAULT 2024,
                date_import DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                                    CREATE TABLE IF NOT EXISTS referentiel_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS flotte_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                societe_id INT NOT NULL,
                plaque VARCHAR(20) NOT NULL,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL,
                statut VARCHAR(20) DEFAULT 'Actif',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS scope1_emissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                societe_id INT NOT NULL,
                annee INT NOT NULL,
                categorie VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL,
                quantite DECIMAL(12,2) NOT NULL,
                unite VARCHAR(20) NOT NULL,
                facteur_emission DECIMAL(10,4) NOT NULL,
                total_co2 DECIMAL(12,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS api_resultats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source_id INT NULL,
                siren VARCHAR(9) NULL,
                code_interne VARCHAR(50) NULL,
                code_postal VARCHAR(10) NULL,
                ville VARCHAR(100) NULL,
                nom_complet VARCHAR(255) NULL,
                activite_principale VARCHAR(10) NULL,
                activite_principale_libelle VARCHAR(255) NULL,
                est_alimentaire TINYINT(1) DEFAULT 0,
                est_ess TINYINT(1) DEFAULT 0,
                est_societe_mission TINYINT(1) DEFAULT 0,
                est_connu TINYINT(1) DEFAULT 0,
                est_qualifie TINYINT(1) DEFAULT 0,
                statut_juridique VARCHAR(100) DEFAULT 'Actif',
                siege_adresse TEXT NULL,
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(10,8) NULL,
                distance FLOAT NULL,
                origine_geo VARCHAR(50) NULL,
                date_requete DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS ademe_facteurs (
                code_naf VARCHAR(10) PRIMARY KEY,
                facteur DECIMAL(10,4) DEFAULT 0.2000,
                mis_a_jour_le DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS codes_naf (
                code VARCHAR(10) PRIMARY KEY, 
                libelle VARCHAR(255), est_privilegie TINYINT(1) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS siren_connus (
                siren VARCHAR(9) PRIMARY KEY,
                date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS exercices_comptables (
                annee INT PRIMARY KEY,
                statut ENUM('ouvert', 'cloture') DEFAULT 'ouvert',
                date_cloture DATETIME NULL,
                commentaire VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($sql);

            // Création de la société par défaut si inexistante
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM societes");
                if ($stmt->fetchColumn() == 0) {
                    $pdo->exec("INSERT INTO societes (nom, est_defaut) VALUES ('Mon Entreprise (Par défaut)', 1)");
                }
            } catch (\PDOException $e) {}

            // Seed des véhicules de référence par défaut
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM referentiel_vehicules");
                if ($stmt->fetchColumn() == 0) {
                    $pdo->exec("INSERT INTO referentiel_vehicules (marque, modele, carburant, conso_moyenne) VALUES 
                    ('Renault', 'Clio V', 'Gazole', 4.5),
                    ('Peugeot', '208', 'Essence', 5.2),
                    ('Tesla', 'Model 3', 'Electrique', 15.0),
                    ('Renault', 'Kangoo', 'Gazole', 5.8)
                    ");
                }
            } catch (\PDOException $e) {}

            // Import automatique du fichier NAF
            $nafFile = __DIR__ . '/../../insee.codenaf.csv';
            if (file_exists($nafFile)) {
                $handle = fopen($nafFile, "r");
                if ($handle !== FALSE) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO codes_naf (code, libelle) VALUES (:code, :libelle)");
                    $firstLine = fgets($handle);
                    $delim = (strpos($firstLine, ';') !== false) ? ';' : ',';
                    rewind($handle);
                    fgetcsv($handle, 1000, $delim); 
                    while (($data = fgetcsv($handle, 1000, $delim)) !== FALSE) {
                        if (isset($data[0]) && isset($data[1])) {
                            $code = mb_substr(trim($data[0]), 0, 10, 'UTF-8');
                            $libelle = mb_substr(trim($data[1]), 0, 250, 'UTF-8');
                            if (!mb_check_encoding($libelle, 'UTF-8')) {
                                $libelle = mb_convert_encoding($libelle, 'UTF-8', 'Windows-1252');
                            }
                            $stmt->execute([':code' => $code, ':libelle' => $libelle]);
                        }
                    }
                    fclose($handle);
                }
            }

            // Import automatique des facteurs ADEME par défaut depuis la nouvelle table codes_naf
            try {
                $stmt = $pdo->query("SELECT code FROM codes_naf WHERE code NOT IN (SELECT code_naf FROM ademe_facteurs)");
                $missingCodes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if ($missingCodes) {
                    $insert = $pdo->prepare("INSERT INTO ademe_facteurs (code_naf, facteur) VALUES (?, ?)");
                    $pdo->beginTransaction();
                    foreach ($missingCodes as $code) {
                        $div = substr($code, 0, 2);
                        $facteur = 0.2000;
                        if (in_array($div, ['10','11','56'])) $facteur = 0.4500;
                        elseif (in_array($div, ['49','50','51','52'])) $facteur = 0.8000;
                        elseif (in_array($div, ['61','62','63','69','70','71'])) $facteur = 0.0500;
                        $insert->execute([$code, $facteur]);
                    }
                    $pdo->commit();
                }
            } catch (\PDOException $e) {}

            header("Location: ?");
            exit;
        } catch (PDOException $e) {
            die("Erreur d'installation : " . htmlspecialchars($e->getMessage()));
        }
    }

    // Gardé uniquement pour sécuriser l'importation de vieilles sauvegardes SQL
                private function updateLegacySchema($pdo) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS referentiel_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Insert some defaults if empty
            $stmt = $pdo->query("SELECT COUNT(*) FROM referentiel_vehicules");
            if ($stmt->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO referentiel_vehicules (marque, modele, carburant, conso_moyenne) VALUES 
                ('Renault', 'Clio V', 'Gazole', 4.5),
                ('Peugeot', '208', 'Essence', 5.2),
                ('Tesla', 'Model 3', 'Electrique', 15.0),
                ('Renault', 'Kangoo', 'Gazole', 5.8)
                ");
            }
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("            CREATE TABLE IF NOT EXISTS referentiel_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS flotte_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                societe_id INT NOT NULL,
                plaque VARCHAR(20) NOT NULL,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL,
                statut VARCHAR(20) DEFAULT 'Actif',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS societes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(255) NOT NULL,
                siren VARCHAR(9) NULL,
                code_interne VARCHAR(50) NULL,
                code_postal VARCHAR(10) NULL,
                ville VARCHAR(100) NULL,
                adresse TEXT NULL,
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(10,8) NULL,
                est_defaut TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\PDOException $e) {}
                        try { $pdo->exec("ALTER TABLE sources_csv ADD COLUMN societe_id INT NULL"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE sources_csv ADD COLUMN annee INT NOT NULL DEFAULT 2024"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE societes ADD COLUMN code_interne VARCHAR(50) NULL"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE societes ADD COLUMN code_postal VARCHAR(10) NULL"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE societes ADD COLUMN ville VARCHAR(100) NULL"); } catch (\PDOException $e) {}
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM societes");
            if ($stmt->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO societes (nom, est_defaut) VALUES ('Mon Entreprise (Par défaut)', 1)");
            }
        } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE sources_csv ADD COLUMN montant DECIMAL(10,2) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE sources_csv ADD COLUMN poids DECIMAL(10,2) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN statut_juridique VARCHAR(100) DEFAULT 'Actif'"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN latitude DECIMAL(10,8) NULL"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN longitude DECIMAL(10,8) NULL"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN est_ess TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN est_societe_mission TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN est_connu TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE codes_naf ADD COLUMN est_privilegie TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats DROP FOREIGN KEY api_resultats_ibfk_1"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats MODIFY source_id INT NULL"); } catch (\PDOException $e) {}
        try { $pdo->exec("ALTER TABLE api_resultats ADD COLUMN est_qualifie TINYINT(1) DEFAULT 0"); } catch (\PDOException $e) {}
        try { $pdo->exec("UPDATE api_resultats SET est_qualifie = 1 WHERE id IN (SELECT DISTINCT api_result_id_selectionne FROM sources_csv WHERE api_result_id_selectionne IS NOT NULL)"); } catch (\PDOException $e) {}
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS siren_connus (
                siren VARCHAR(9) PRIMARY KEY,
                date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\PDOException $e) {}
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS exercices_comptables (
                annee INT PRIMARY KEY,
                statut ENUM('ouvert', 'cloture') DEFAULT 'ouvert',
                date_cloture DATETIME NULL,
                commentaire VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\PDOException $e) {}
    }
}