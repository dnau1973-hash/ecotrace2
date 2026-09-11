<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title"><i class="fas fa-highlighter me-2 text-teal"></i> Surligner via SIREN</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="?" class="btn btn-secondary">Annuler & Retour</a>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-md-8 mx-auto">
        
        <!-- ÉTAPE 1 : FORMULAIRE -->
        <div id="importSirenFormCard">
            <div class="card mb-3">
                <div class="card-header bg-teal-lt">
                    <h3 class="card-title text-teal"><i class="fas fa-info-circle me-2"></i> Fonctionnement</h3>
                </div>
                <div class="card-body">
                    <p>Importez un fichier CSV contenant une liste de numéros SIREN (9 chiffres) de vos partenaires certifiés. L'application mémorisera ces numéros et surlignera automatiquement en vert les candidats correspondants.</p>
                </div>
            </div>
            
            <form id="uploadSirenForm" enctype="multipart/form-data" class="card shadow-sm border-teal">
                <input type="hidden" name="_csrf" value="<?= \App\Helpers\SecurityHelper::getToken() ?>">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Fichier CSV (SIREN)</label>
                        <input type="file" name="siren_file" class="form-control" accept=".csv" required>
                    </div>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_header" value="1" checked>
                        <span class="form-check-label">Ignorer la première ligne (En-tête)</span>
                    </label>
                </div>
                <div class="card-footer text-end bg-light">
                    <button type="submit" class="btn btn-teal"><i class="fas fa-play me-2"></i> Démarrer le surlignage</button>
                </div>
            </form>
        </div>

        <!-- ÉTAPE 2 : BARRE DE PROGRESSION -->
        <div id="progressSirenCard" class="card border-teal" style="display: none;">
            <div class="card-body text-center py-5">
                <h3 class="mb-4">Mémorisation et mise à jour de l'historique... <span id="progressSirenText" class="text-teal fw-bold">0 / 0</span></h3>
                <div class="progress progress-lg">
                    <div id="progressSirenBar" class="progress-bar progress-bar-striped progress-bar-animated bg-teal" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- ÉTAPE 3 : RAPPORT FINAL -->
        <div id="reportSirenCard" class="card border-success" style="display: none;">
            <div class="card-header bg-success text-white">
                <h3 class="card-title text-white"><i class="fas fa-check me-2"></i> Traitement terminé !</h3>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4 mt-2">
                    <div class="col-6">
                        <div class="fs-1 fw-bold text-success" id="rep-siren-total">0</div>
                        <div class="text-muted">Nouveaux SIREN mémorisés</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-1 fw-bold text-teal" id="rep-siren-maj">0</div>
                        <div class="text-muted">Candidats peints en vert (Historique)</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    <button class="btn btn-primary" onclick="window.location.href='?'; localStorage.setItem('activeTabEcotrace', 'tab-matching');">Voir le résultat dans les Rapprochements</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById('uploadSirenForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    let formData = new FormData(this);

    // 1. Basculer l'affichage
    document.getElementById('importSirenFormCard').style.display = 'none';
    document.getElementById('progressSirenCard').style.display = 'block';

    try {
        // 2. Envoyer le fichier pour lecture
        let res = await fetch('?action=import_siren_process', { method: 'POST', body: formData });
        let result = await res.json();

        if(!result.success) {
            alert(result.message || "Erreur lors de la lecture du fichier CSV.");
            location.reload();
            return;
        }

        let sirens = result.data;
        let total = sirens.length;
        let done = 0;
        let report = { ajoutes: 0, mis_a_jour: 0 };

        if (total === 0) {
            alert("Aucun SIREN valide (9 chiffres) trouvé dans le fichier.");
            location.reload();
            return;
        }

        // 3. Boucler sur chaque SIREN
        for(let siren of sirens) {
            let fd = new FormData();
            fd.append('action', 'surligner_siren_single');
            fd.append('siren', siren);

            try {
                let sRes = await fetch('index.php', { method: 'POST', body: fd });
                let sData = await sRes.json();
                
                if (sData.success) {
                    report.ajoutes++;
                    report.mis_a_jour += sData.candidats_surlignes;
                }
            } catch(err) {
                console.error("Erreur sur le SIREN :", siren);
            }

            // Mise à jour de la barre
            done++;
            let pct = Math.round((done / total) * 100);
            document.getElementById('progressSirenBar').style.width = pct + '%';
            document.getElementById('progressSirenText').innerText = done + ' / ' + total;
        }

        // 4. Afficher le rapport final
        setTimeout(() => {
            document.getElementById('progressSirenCard').style.display = 'none';
            document.getElementById('reportSirenCard').style.display = 'block';
            
            document.getElementById('rep-siren-total').innerText = report.ajoutes;
            document.getElementById('rep-siren-maj').innerText = report.mis_a_jour;
        }, 800);

    } catch (error) {
        alert("Une erreur de communication est survenue.");
        location.reload();
    }
});
</script>