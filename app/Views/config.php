<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title"><i class="fas fa-tools me-2 text-blue"></i> Éditeur de Configuration (.env)</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="?" class="btn btn-secondary">Retour au Dashboard</a>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div><i class="fas fa-check icon alert-icon"></i></div>
        <div>
            <h4 class="alert-title">Configuration sauvegardée !</h4>
            <div class="text-muted">Les modifications ont été écrites dans le fichier .env avec succès.</div>
        </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<form action="?action=save_config" method="POST" class="card shadow-sm border-blue">
    <div class="card-body">
        <div class="row">
            <!-- BLOC 1 : BASE DE DONNÉES -->
            <div class="col-md-6 mb-4">
                <h3 class="card-title text-primary border-bottom pb-2"><i class="fas fa-database me-2"></i> Base de données</h3>
                <div class="mb-3">
                    <label class="form-label">Hôte (DB_HOST)</label>
                    <input type="text" name="env[DB_HOST]" class="form-control" value="<?= htmlspecialchars($envVars['DB_HOST'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nom de la base (DB_NAME)</label>
                    <input type="text" name="env[DB_NAME]" class="form-control" value="<?= htmlspecialchars($envVars['DB_NAME'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Utilisateur (DB_USER)</label>
                    <input type="text" name="env[DB_USER]" class="form-control" value="<?= htmlspecialchars($envVars['DB_USER'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe (DB_PASS)</label>
                    <input type="password" name="env[DB_PASS]" class="form-control" value="<?= htmlspecialchars($envVars['DB_PASS'] ?? '') ?>">
                </div>
            </div>

            <!-- BLOC 2 : ADMINISTRATEUR -->
            <div class="col-md-6 mb-4">
                <h3 class="card-title text-primary border-bottom pb-2"><i class="fas fa-user me-2"></i> Administrateur</h3>
                <div class="mb-3">
                    <label class="form-label">Utilisateur (ADMIN_USER)</label>
                    <input type="text" name="env[ADMIN_USER]" class="form-control" value="<?= htmlspecialchars($envVars['ADMIN_USER'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe (ADMIN_PASS)</label>
                    <input type="password" name="env[ADMIN_PASS]" class="form-control" value="<?= htmlspecialchars($envVars['ADMIN_PASS'] ?? '') ?>">
                </div>
            </div>

            <!-- BLOC 3 : COORDONNÉES GPS -->
            <div class="col-md-6 mb-4">
                <h3 class="card-title text-primary border-bottom pb-2"><i class="fas fa-map-marker-alt me-2"></i> Point de départ logistique</h3>
                <div class="mb-3">
                    <label class="form-label">Latitude (ORIGIN_LAT)</label>
                    <input type="text" name="env[ORIGIN_LAT]" class="form-control" value="<?= htmlspecialchars($envVars['ORIGIN_LAT'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Longitude (ORIGIN_LON)</label>
                    <input type="text" name="env[ORIGIN_LON]" class="form-control" value="<?= htmlspecialchars($envVars['ORIGIN_LON'] ?? '') ?>">
                </div>
            </div>

            <!-- BLOC 4 : CLÉS API & GITHUB -->
            <div class="col-md-6 mb-4">
                <h3 class="card-title text-primary border-bottom pb-2"><i class="fas fa-key me-2"></i> Services Externes</h3>
                <div class="mb-3">
                    <label class="form-label">Clé API Pappers (Optionnelle)</label>
                    <input type="text" name="env[PAPPERS_API_KEY]" class="form-control" value="<?= htmlspecialchars($envVars['PAPPERS_API_KEY'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Clé API Societe.com (Optionnelle)</label>
                    <input type="text" name="env[SOCIETE_API_KEY]" class="form-control" value="<?= htmlspecialchars($envVars['SOCIETE_API_KEY'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Dépôt GitHub (Auto-Updater)</label>
                    <input type="text" name="env[GITHUB_REPO]" class="form-control" value="<?= htmlspecialchars($envVars['GITHUB_REPO'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-end bg-light">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Sauvegarder la configuration</button>
    </div>
</form>