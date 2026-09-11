<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title"><i class="fas fa-file-upload me-2 text-primary"></i> Traitement de masse (CSV)</h2>
        </div>
        <div class="col-auto ms-auto">
            <a href="?" class="btn btn-secondary">Annuler & Retour</a>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-md-8 mx-auto">
        
        <!-- ÉTAPE 1 : FORMULAIRE -->
        <div id="importFormCard">
            <div class="card mb-3">
                <div class="card-header bg-azure-lt">
                    <h3 class="card-title text-azure"><i class="fas fa-info-circle me-2"></i> Format attendu</h3>
                </div>
                <div class="card-body">
                    <p>Le système traitera automatiquement les recherches Gouv.fr et le géocodage pour chaque ligne importée.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nom (Obligatoire)</th>
                                    <th>Montant € (Optionnel)</th>
                                    <th>Poids kg (Optionnel)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Dupont & Fils</strong></td>
                                    <td><strong>1500.50</strong></td>
                                    <td><strong>450</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <form id="uploadForm" enctype="multipart/form-data" class="card shadow-sm border-primary">
                <input type="hidden" name="_csrf" value="<?= \App\Helpers\SecurityHelper::getToken() ?>">
                <div class="card-body">
                    
                    <!-- SÉLECTION DE LA SOCIÉTÉ / FILIALE -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Société / Filiale de destination</label>
                        <select name="societe_id" class="form-select" required>
                            <?php foreach ($societes as $soc): ?>
                                <?php $selected = ($activeSocieteId == $soc['id']) ? 'selected' : ''; ?>
                                <option value="<?= $soc['id'] ?>" <?= $selected ?>>
                                    🏢 <?= htmlspecialchars($soc['nom']) ?> <?= !empty($soc['code_interne']) ? '('.htmlspecialchars($soc['code_interne']).')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Toutes les lignes du fichier seront attribuées à cette entité juridique.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required fw-bold">Exercice comptable (Année)</label>
                            <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" min="2000" max="2050" required>
                            <small class="form-hint">Année de reporting Bilan Carbone associée à ces dépenses.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required fw-bold">Fichier CSV de fournisseurs</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                    </div>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_header" value="1" checked>
                        <span class="form-check-label">Ignorer la première ligne (En-tête)</span>
                    </label>
                </div>
                <div class="card-footer text-end bg-light">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-play me-2"></i> Démarrer le traitement</button>
                </div>
            </form>
        </div>

        <!-- ÉTAPE 2 : BARRE DE PROGRESSION (Cachée par défaut) -->
        <div id="progressCard" class="card border-primary" style="display: none;">
            <div class="card-body text-center py-5">
                <h3 class="mb-4">Traitement API en cours... <span id="progressText" class="text-primary fw-bold">0 / 0</span></h3>
                <div class="progress progress-lg">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%"></div>
                </div>
                <div class="text-muted mt-3 small">Veuillez ne pas fermer cette page. Les requêtes sont envoyées séquentiellement pour ne pas surcharger les serveurs de l'État.</div>
            </div>
        </div>

        <!-- ÉTAPE 3 : RAPPORT FINAL (Caché par défaut) -->
        <div id="reportCard" class="card border-success" style="display: none;">
            <div class="card-header bg-success text-white">
                <h3 class="card-title text-white"><i class="fas fa-check me-2"></i> Importation terminée !</h3>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4 mt-2">
                    <div class="col-4">
                        <div class="fs-1 fw-bold text-success" id="rep-auto">0</div>
                        <div class="text-muted">Rapprochés Auto</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-1 fw-bold text-blue" id="rep-attente">0</div>
                        <div class="text-muted">Homonymes (Attente)</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-1 fw-bold text-danger" id="rep-introuvable">0</div>
                        <div class="text-muted">Introuvables</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button class="btn btn-outline-primary" onclick="window.location.href='?'; localStorage.setItem('activeTabEcotrace', 'tab-matching');">Voir les Homonymes</button>
                    <button class="btn btn-primary" onclick="window.location.href='?'; localStorage.setItem('activeTabEcotrace', 'tab-stats');">Retour au Dashboard</button>
                </div>
            </div>
        </div>

        <!-- ERREUR IMPORT (Caché par défaut) -->
        <div id="importErrorCard" class="card border-danger" style="display: none;">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title text-white"><i class="fas fa-exclamation-triangle me-2"></i> Erreur lors de l'importation</h3>
            </div>
            <div class="card-body">
                <pre id="importErrorMessage" class="bg-dark text-light p-3 rounded" style="white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 13px;"></pre>
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <button class="btn btn-outline-danger" onclick="location.reload()"><i class="fas fa-sync-alt me-2"></i>Réessayer</button>
                    <button class="btn btn-primary" onclick="window.location.href='?'"><i class="fas fa-home me-2"></i>Retour au Dashboard</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function showImportError(message) {
    document.getElementById('importFormCard').style.display = 'none';
    document.getElementById('progressCard').style.display = 'none';
    document.getElementById('reportCard').style.display = 'none';
    document.getElementById('importErrorCard').style.display = 'block';
    document.getElementById('importErrorMessage').textContent = message;
}

document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    let formData = new FormData(this);

    document.getElementById('importFormCard').style.display = 'none';
    document.getElementById('progressCard').style.display = 'block';

    try {
        // 1. Envoi du fichier CSV
        let res = await fetch('?action=import_csv_process', { method: 'POST', body: formData });
        let textResult = await res.text();
        let result;

        try {
            result = JSON.parse(textResult);
        } catch(err) {
            showImportError("Erreur PHP/JSON lors de l'import CSV :\n\n" + textResult);
            return;
        }

        if (!result.success) {
            showImportError("Erreur lors de l'import : " + (result.message || "Fichier invalide."));
            return;
        }

        let items = result.data;
        let total = items.length;
        let done = 0;
        let report = { valide_auto: result.total_known || 0, en_attente: 0, introuvable: 0, erreurs: 0 };

        if (total === 0) {
            document.getElementById('progressCard').style.display = 'none';
            document.getElementById('summaryCard').style.display = 'block';
            document.getElementById('countValideAuto').textContent = result.total_known || 0;
            document.getElementById('countEnAttente').textContent = 0;
            document.getElementById('countIntrouvable').textContent = 0;
            document.getElementById('countErreurs').textContent = 0;
            return;
        }

        // 2. Traitement séquentiel des requêtes API
        for (let item of items) {
            let fd = new FormData();
            fd.append('action', 'ajax_search');
            fd.append('source_id', item.id);
            fd.append('nouveau_nom', item.nom);
            fd.append('api_source', 'gouv');

            try {
                let sRes = await fetch('index.php', { method: 'POST', body: fd });
                let sText = await sRes.text();
                let sData;

                try {
                    sData = JSON.parse(sText);
                } catch(jErr) {
                    console.error("Réponse API invalide pour l'élément ID " + item.id + ":", sText);
                    report.erreurs++;
                    done++;
                    continue;
                }

                if (sData.success && sData.statut) {
                    report[sData.statut] = (report[sData.statut] || 0) + 1;
                } else {
                    report.erreurs++;
                }
            } catch(err) {
                console.error("Erreur réseau sur l'élément ID " + item.id, err);
                report.erreurs++;
            }

            done++;
            let pct = Math.round((done / total) * 100);
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressText').innerText = done + ' / ' + total;
        }

        // 3. Affichage du rapport final
        setTimeout(() => {
            document.getElementById('progressCard').style.display = 'none';
            document.getElementById('reportCard').style.display = 'block';

            document.getElementById('rep-auto').innerText = report.valide_auto || 0;
            document.getElementById('rep-attente').innerText = report.en_attente || 0;
            document.getElementById('rep-introuvable').innerText = report.introuvable || 0;
        }, 800);

    } catch (error) {
        console.error(error);
        alert("Erreur réseau ou arrêt du serveur : " + error.message);
        location.reload();
    }
});
</script>