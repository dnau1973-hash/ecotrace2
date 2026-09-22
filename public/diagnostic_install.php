<?php
/**
 * EcoTrace — Diagnostic & Réparation d'Installation
 * Accédez à ce fichier via votre navigateur pour diagnostiquer
 * et réparer l'installation sur le serveur distant.
 * IMPORTANT : Supprimez ce fichier après usage !
 */

// Charger le .env
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile) ?: [];
}

$host   = $env['DB_HOST']   ?? 'localhost';
$dbName = $env['DB_NAME']   ?? 'ecotrace';
$user   = $env['DB_USER']   ?? 'root';
$pass   = $env['DB_PASS']   ?? '';
$adminLogin = $env['ADMIN_USER'] ?? 'admin';
$adminPass  = $env['ADMIN_PASS'] ?? 'admin';

$action = $_GET['do'] ?? '';
$messages = [];
$pdo = null;

// Connexion MySQL
$connOk = false;
$dbExists = false;
$tables = [];
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connOk = true;

    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->fetch()) {
        $dbExists = true;
        $pdo->exec("USE `$dbName`");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (\Exception $e) {
    $connError = $e->getMessage();
}

// Tables requises
$required = [
    'societes','sources_csv','api_resultats','codes_naf','ademe_facteurs',
    'siren_connus','exercices_comptables','referentiel_vehicules',
    'flotte_vehicules','scope1_emissions','utilisateurs'
];

// ACTION : Installer / réparer
if ($action === 'install' && $connOk) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbName`");

        $creates = [
            "societes" => "CREATE TABLE IF NOT EXISTS societes (
                id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(255) NOT NULL,
                siren VARCHAR(9) NULL, code_interne VARCHAR(50) NULL,
                code_postal VARCHAR(10) NULL, ville VARCHAR(100) NULL,
                adresse TEXT NULL, latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(10,8) NULL, est_defaut TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "sources_csv" => "CREATE TABLE IF NOT EXISTS sources_csv (
                id INT AUTO_INCREMENT PRIMARY KEY, nom_recherche VARCHAR(255) NOT NULL,
                societe_id INT NULL, montant DECIMAL(10,2) DEFAULT 0,
                poids DECIMAL(10,2) DEFAULT 0, statut VARCHAR(50) DEFAULT 'en_attente',
                api_result_id_selectionne INT NULL, annee INT NOT NULL DEFAULT 2024,
                date_import DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "api_resultats" => "CREATE TABLE IF NOT EXISTS api_resultats (
                id INT AUTO_INCREMENT PRIMARY KEY, source_id INT NULL,
                siren VARCHAR(9) NULL, code_interne VARCHAR(50) NULL,
                code_postal VARCHAR(10) NULL, ville VARCHAR(100) NULL,
                nom_complet VARCHAR(255) NULL, activite_principale VARCHAR(10) NULL,
                activite_principale_libelle VARCHAR(255) NULL,
                est_alimentaire TINYINT(1) DEFAULT 0, est_ess TINYINT(1) DEFAULT 0,
                est_societe_mission TINYINT(1) DEFAULT 0, est_connu TINYINT(1) DEFAULT 0,
                est_qualifie TINYINT(1) DEFAULT 0,
                statut_juridique VARCHAR(100) DEFAULT 'Actif',
                siege_adresse TEXT NULL, latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(10,8) NULL, distance FLOAT NULL,
                origine_geo VARCHAR(50) NULL,
                date_requete DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "codes_naf" => "CREATE TABLE IF NOT EXISTS codes_naf (
                code VARCHAR(10) PRIMARY KEY, libelle VARCHAR(255),
                est_privilegie TINYINT(1) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "ademe_facteurs" => "CREATE TABLE IF NOT EXISTS ademe_facteurs (
                code_naf VARCHAR(10) PRIMARY KEY, facteur DECIMAL(10,4) DEFAULT 0.2000,
                mis_a_jour_le DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "siren_connus" => "CREATE TABLE IF NOT EXISTS siren_connus (
                siren VARCHAR(9) PRIMARY KEY,
                date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "exercices_comptables" => "CREATE TABLE IF NOT EXISTS exercices_comptables (
                annee INT PRIMARY KEY, statut ENUM('ouvert','cloture') DEFAULT 'ouvert',
                date_cloture DATETIME NULL, commentaire VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "referentiel_vehicules" => "CREATE TABLE IF NOT EXISTS referentiel_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY, marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL, carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "flotte_vehicules" => "CREATE TABLE IF NOT EXISTS flotte_vehicules (
                id INT AUTO_INCREMENT PRIMARY KEY, societe_id INT NOT NULL,
                plaque VARCHAR(20) NOT NULL, marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL, carburant VARCHAR(50) NOT NULL,
                conso_moyenne DECIMAL(10,2) NOT NULL, statut VARCHAR(20) DEFAULT 'Actif',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "scope1_emissions" => "CREATE TABLE IF NOT EXISTS scope1_emissions (
                id INT AUTO_INCREMENT PRIMARY KEY, societe_id INT NOT NULL,
                annee INT NOT NULL, categorie VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL, quantite DECIMAL(12,2) NOT NULL,
                unite VARCHAR(20) NOT NULL, facteur_emission DECIMAL(10,4) NOT NULL,
                total_co2 DECIMAL(12,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "utilisateurs" => "CREATE TABLE IF NOT EXISTS utilisateurs (
                id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) DEFAULT '', login VARCHAR(80) NOT NULL UNIQUE,
                email VARCHAR(150) DEFAULT '', password_hash VARCHAR(255) NOT NULL,
                role ENUM('superadmin','admin','lecteur') NOT NULL DEFAULT 'lecteur',
                actif TINYINT(1) NOT NULL DEFAULT 1, societe_ids TEXT DEFAULT NULL,
                derniere_connexion DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($creates as $tbl => $sql) {
            try {
                $pdo->exec($sql);
                $messages[] = ['ok', "Table `$tbl` créée ou déjà existante."];
            } catch (\Exception $e) {
                $messages[] = ['err', "Erreur table `$tbl` : " . $e->getMessage()];
            }
        }

        // Société par défaut
        if ($pdo->query("SELECT COUNT(*) FROM societes")->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO societes (nom, est_defaut) VALUES ('Mon Entreprise (Par défaut)', 1)");
            $messages[] = ['ok', "Société par défaut créée."];
        }

        // Véhicules de référence
        if ($pdo->query("SELECT COUNT(*) FROM referentiel_vehicules")->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO referentiel_vehicules (marque, modele, carburant, conso_moyenne) VALUES
                ('Renault','Clio V','Gazole',4.5),('Peugeot','208','Essence',5.2),
                ('Tesla','Model 3','Electrique',15.0),('Renault','Kangoo','Gazole',5.8)");
            $messages[] = ['ok', "Véhicules de référence insérés."];
        }

        // Superadmin
        if ($pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn() == 0) {
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, email, password_hash, role, actif) VALUES (?,?,?,?,?,'superadmin',1)");
            $stmt->execute(['Administrateur', 'Super', $adminLogin, 'admin@ecotrace.local', $hash]);
            $messages[] = ['ok', "Superadmin '$adminLogin' créé en base."];
        } else {
            // Vérifier si superadmin existe, sinon créer
            $existing = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='superadmin'")->fetchColumn();
            if ($existing == 0) {
                $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, email, password_hash, role, actif) VALUES (?,?,?,?,?,'superadmin',1)");
                $stmt->execute(['Administrateur', 'Super', $adminLogin, 'admin@ecotrace.local', $hash]);
                $messages[] = ['ok', "Superadmin '$adminLogin' créé (aucun superadmin n'existait)."];
            }
        }

        // Recharger les tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbExists = true;

    } catch (\Exception $e) {
        $messages[] = ['err', "Erreur installation : " . $e->getMessage()];
    }
}

// ACTION : Réinitialiser le mot de passe du superadmin
if ($action === 'reset_pwd' && $connOk && $dbExists) {
    try {
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE utilisateurs SET password_hash=? WHERE role='superadmin'")->execute([$hash]);
        $messages[] = ['ok', "Mot de passe du superadmin réinitialisé à la valeur du .env ($adminLogin / $adminPass)."];
    } catch (\Exception $e) {
        $messages[] = ['err', "Erreur : " . $e->getMessage()];
    }
}

$missing = array_diff($required, $tables);
$allOk = $connOk && $dbExists && empty($missing);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EcoTrace — Diagnostic Installation</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f0; color: #2d3436; padding: 20px; }
.container { max-width: 860px; margin: 0 auto; }
.header { background: linear-gradient(135deg,#1a7f37,#2da44e); color: #fff; border-radius: 16px; padding: 24px 28px; margin-bottom: 20px; }
.header h1 { font-size: 1.7rem; margin-bottom: 4px; }
.header p { opacity: .85; font-size: .95rem; }
.card { background: #fff; border-radius: 12px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.card h2 { font-size: 1.1rem; margin-bottom: 14px; color: #1a7f37; border-bottom: 1px solid #e0e8e0; padding-bottom: 8px; }
.row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #f5f5f5; font-size: .92rem; }
.row:last-child { border-bottom: none; }
.ok  { color: #2da44e; font-weight: 600; }
.err { color: #e74c3c; font-weight: 600; }
.warn { color: #f39c12; font-weight: 600; }
.badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .78rem; font-weight: 700; }
.badge-ok   { background: #d4edda; color: #155724; }
.badge-err  { background: #f8d7da; color: #721c24; }
.badge-warn { background: #fff3cd; color: #856404; }
.btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: .92rem; cursor: pointer; border: none; margin-right: 8px; margin-top: 4px; }
.btn-green  { background: #2da44e; color: #fff; }
.btn-orange { background: #f39c12; color: #fff; }
.btn-red    { background: #e74c3c; color: #fff; }
.btn:hover  { opacity: .88; }
.msgs { margin-bottom: 16px; }
.msg { padding: 8px 14px; border-radius: 8px; margin-bottom: 6px; font-size: .9rem; }
.msg-ok  { background: #d4edda; color: #155724; border-left: 4px solid #2da44e; }
.msg-err { background: #f8d7da; color: #721c24; border-left: 4px solid #e74c3c; }
.warning-box { background: #fff3cd; border: 1.5px solid #ffc107; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: .88rem; color: #856404; }
</style>
</head>
<body>
<div class="container">

<div class="header">
    <h1>🍃 EcoTrace — Diagnostic d'Installation</h1>
    <p>Vérification et réparation de l'architecture base de données</p>
</div>

<div class="warning-box">
    ⚠️ <strong>Sécurité :</strong> Supprimez ce fichier (<code>public/diagnostic_install.php</code>) après utilisation.
</div>

<?php if (!empty($messages)): ?>
<div class="msgs">
<?php foreach ($messages as $m): ?>
    <div class="msg msg-<?= $m[0] ?>"><?= $m[0] === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($m[1]) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Connexion -->
<div class="card">
    <h2>🔌 Connexion MySQL</h2>
    <div class="row"><span>Hôte</span><strong><?= htmlspecialchars($host) ?></strong></div>
    <div class="row"><span>Base de données</span><strong><?= htmlspecialchars($dbName) ?></strong></div>
    <div class="row"><span>Utilisateur</span><strong><?= htmlspecialchars($user) ?></strong></div>
    <div class="row"><span>Connexion au serveur</span>
        <?php if ($connOk): ?>
            <span class="badge badge-ok">✅ OK</span>
        <?php else: ?>
            <span class="badge badge-err">❌ ÉCHEC : <?= htmlspecialchars($connError ?? '') ?></span>
        <?php endif; ?>
    </div>
    <div class="row"><span>Base de données</span>
        <span class="badge <?= $dbExists ? 'badge-ok' : 'badge-err' ?>"><?= $dbExists ? '✅ Existe' : '❌ Absente' ?></span>
    </div>
</div>

<!-- Tables -->
<div class="card">
    <h2>📋 Tables requises (<?= count($tables) ?>/<?= count($required) ?> présentes)</h2>
    <?php foreach ($required as $tbl): ?>
    <div class="row">
        <span><code><?= $tbl ?></code></span>
        <span class="badge <?= in_array($tbl, $tables) ? 'badge-ok' : 'badge-err' ?>">
            <?= in_array($tbl, $tables) ? '✅ OK' : '❌ MANQUANTE' ?>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<!-- Utilisateurs -->
<?php if ($connOk && $dbExists && in_array('utilisateurs', $tables)): ?>
<div class="card">
    <h2>👤 Comptes utilisateurs</h2>
    <?php
    $users = $pdo->query("SELECT id, login, nom, prenom, role, actif, derniere_connexion FROM utilisateurs ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)): ?>
        <p class="err">Aucun utilisateur ! L'installation est incomplète.</p>
    <?php else: foreach ($users as $u): ?>
    <div class="row">
        <span><strong><?= htmlspecialchars($u['login']) ?></strong> — <?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></span>
        <div>
            <span class="badge <?= $u['role']==='superadmin' ? 'badge-err' : ($u['role']==='admin' ? 'badge-ok' : 'badge-warn') ?>"><?= $u['role'] ?></span>
            <span class="badge <?= $u['actif'] ? 'badge-ok' : 'badge-err' ?>"><?= $u['actif'] ? 'Actif' : 'Inactif' ?></span>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
<?php endif; ?>

<!-- Configuration .env -->
<div class="card">
    <h2>⚙️ Configuration .env</h2>
    <div class="row"><span>Fichier .env</span>
        <span class="badge <?= file_exists($envFile) ? 'badge-ok' : 'badge-err' ?>"><?= file_exists($envFile) ? '✅ Trouvé' : '❌ Absent' ?></span>
    </div>
    <div class="row"><span>Login admin (.env)</span><strong><?= htmlspecialchars($adminLogin) ?></strong></div>
    <div class="row"><span>Mot de passe admin (.env)</span><strong><?= htmlspecialchars($adminPass) ?></strong></div>
</div>

<!-- Actions -->
<div class="card">
    <h2>🔧 Actions</h2>
    <p style="margin-bottom:12px;color:#636e72;font-size:.9rem;">
        <?php if ($allOk): ?>
            ✅ <strong style="color:#2da44e">Installation complète.</strong> Toutes les tables sont présentes. Connectez-vous à l'application normalement.
        <?php else: ?>
            ⚠️ Des tables sont manquantes. Cliquez sur <strong>Installer / Réparer</strong> pour les créer.
        <?php endif; ?>
    </p>
    <a href="?do=install" class="btn btn-green">⚙️ Installer / Réparer les tables</a>
    <?php if ($connOk && $dbExists && in_array('utilisateurs', $tables)): ?>
    <a href="?do=reset_pwd" class="btn btn-orange" onclick="return confirm('Réinitialiser le mot de passe du superadmin à la valeur du .env ?')">
        🔑 Réinitialiser le mot de passe superadmin
    </a>
    <?php endif; ?>
    <br><br>
    <a href="index.php" class="btn" style="background:#636e72;color:#fff;">🏠 Retour à l'application</a>
</div>

</div>
</body>
</html>

