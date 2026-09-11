<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoTrace 🍃 - Configuration</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- NOUVEAU LIEN VERS LE FICHIER CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="install-box">
        <h1>EcoTrace 🍃</h1>
        <div class="app-version">Version 2.1.0 (MVC)</div>
        <p style="text-align:center; color:#666;"><?= $is_editing_config ? "Modification des paramètres de configuration." : "Veuillez configurer les paramètres avant l'installation." ?></p>
        <form method="POST" action="?">
            <input type="hidden" name="setup_env_action" value="1">
            <h2>⚙️ Base de données (MySQL)</h2>
            <div class="row">
                <div class="form-group"><label>Hôte</label><input type="text" name="db_host" value="<?= htmlspecialchars($db_host) ?>" required></div>
                <div class="form-group"><label>Nom de la base</label><input type="text" name="db_name" value="<?= htmlspecialchars($db_name) ?>" required></div>
            </div>
            <div class="row">
                <div class="form-group"><label>Utilisateur</label><input type="text" name="db_user" value="<?= htmlspecialchars($db_user) ?>" required></div>
                <div class="form-group"><label>Mot de passe</label><input type="password" name="db_pass" value="<?= htmlspecialchars($db_pass) ?>"></div>
            </div>
            <h2>👤 Compte Administrateur</h2>
            <div class="row">
                <div class="form-group"><label>Identifiant</label><input type="text" name="admin_user" value="<?= htmlspecialchars($admin_user) ?>" required></div>
                <div class="form-group"><label>Mot de passe</label><input type="text" name="admin_pass" value="<?= htmlspecialchars($admin_pass) ?>" required></div>
            </div>            
            <h2>🔑 Clés API et Auto-Updater</h2>
            <div class="row">
                <div class="form-group"><label>Lien dépôt GitHub (Auto-Updater)</label><input type="text" name="github_repo" value="<?= htmlspecialchars($github_repo) ?>" placeholder="Ex: https://ghp_XXXX@github.com/.../ecotrace.git"></div>
            </div>

            <button type="submit">💾 Enregistrer et Continuer</button>
            <?php if($is_editing_config): ?>
                <a href="?" class="btn-cancel">❌ Annuler et retourner à l'application</a>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>