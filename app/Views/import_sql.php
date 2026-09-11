<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title"><i class="fas fa-database-import me-2 text-red"></i> Restaurer une sauvegarde (SQL)</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="?" class="btn btn-secondary">Annuler & Retour</a>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-md-8 mx-auto">
        
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-title"><i class="fas fa-exclamation-triangle me-2"></i> Avertissement de sécurité</h4>
            <div class="text-muted">
                L'importation d'un fichier <strong>.sql</strong> va supprimer vos tables actuelles et les remplacer intégralement par le contenu de la sauvegarde. Toutes les données non sauvegardées seront définitivement perdues.
            </div>
        </div>

        <form action="?action=import_sql_process" method="POST" enctype="multipart/form-data" class="card shadow-sm border-red">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required fw-bold text-red">Fichier de base de données (.sql)</label>
                    <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                </div>
            </div>
            <div class="card-footer text-end bg-red-lt">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous absolument certain de vouloir écraser votre base de données avec ce fichier ?');">
                    <i class="fas fa-fire me-2"></i> Écraser et Restaurer
                </button>
            </div>
        </form>

    </div>
</div>