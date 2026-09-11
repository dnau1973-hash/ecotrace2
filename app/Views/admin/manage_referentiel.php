<div class="container-xl">
    <div class="page-header d-print-none text-orange">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Administration</div>
                <h2 class="page-title"><i class="fas fa-car-side me-2"></i> Référentiel Véhicules</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="?" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i> Retour au Tableau de bord</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catalogue des véhicules</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Marque</th>
                                    <th>Modèle</th>
                                    <th>Carburant</th>
                                    <th>Conso Moyenne</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($vehicules)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Aucun véhicule dans le référentiel.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($vehicules as $veh): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($veh['marque']) ?></td>
                                        <td><?= htmlspecialchars($veh['modele']) ?></td>
                                        <td><span class="badge bg-blue-lt"><?= htmlspecialchars($veh['carburant']) ?></span></td>
                                        <td><?= htmlspecialchars($veh['conso_moyenne']) ?> <?= stripos($veh['carburant'], 'electri') !== false ? 'kWh' : 'L' ?>/100km</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-primary btn-sm btn-icon btn-pill" onclick="editVehicule(<?= $veh['id'] ?>, '<?= addslashes(htmlspecialchars($veh['marque'])) ?>', '<?= addslashes(htmlspecialchars($veh['modele'])) ?>', '<?= addslashes(htmlspecialchars($veh['carburant'])) ?>', '<?= $veh['conso_moyenne'] ?>')" title="Éditer"><i class="fas fa-edit"></i></button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ajouter un véhicule</h3>
                    </div>
                    <div class="card-body">
                        <form action="?action=save_referentiel" method="POST">
                            <datalist id="list-marques">
                                <?php if(!empty($marquesExistantes)): ?>
                                    <?php foreach($marquesExistantes as $m): ?>
                                        <option value="<?= htmlspecialchars($m) ?>"></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </datalist>
                            <div class="mb-3">
                                <label class="form-label">Marque</label>
                                <input type="text" name="marque" id="input_marque" list="list-marques" class="form-control" placeholder="Ex: Renault" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Modèle</label>
                                <input type="text" name="modele" class="form-control" placeholder="Ex: Kangoo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Carburant</label>
                                <select name="carburant" class="form-select" required>
                                    <option value="Gazole">Gazole</option>
                                    <option value="Essence">Essence</option>
                                    <option value="Electrique">Electrique</option>
                                    <option value="Hybride">Hybride</option>
                                    <option value="GPL">GPL</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Consommation moyenne (/100km)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="conso_moyenne" id="input_conso" class="form-control" placeholder="Ex: 5.4" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="estimerConso(this)" title="Recherche automatique sur Internet"><i class="fas fa-magic text-orange me-1"></i> Auto</button>
                                </div>
                                <small class="form-hint">En Litres pour les thermiques, en kWh pour l'électrique.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-pill">Ajouter au catalogue</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Generic Delete (Liens) -->
<div class="modal modal-blur fade" id="modalGenericDelete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-danger display-4 mb-3"></i>
                <h3>Confirmation</h3>
                <div class="text-muted mb-3" id="genericDeleteMessage">Êtes-vous sûr de vouloir continuer ?</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100 btn-pill" data-bs-dismiss="modal">Annuler</a></div>
                        <div class="col"><a href="#" id="genericDeleteBtn" class="btn btn-danger w-100 btn-pill">Supprimer</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function confirmGenericDelete(url, message) {
    document.getElementById('genericDeleteMessage').innerText = message;
    document.getElementById('genericDeleteBtn').href = url;
    var myModal = new bootstrap.Modal(document.getElementById('modalGenericDelete'));
    myModal.show();
}
</script>
<script>
async function estimerConso(btn) {
    let marque = document.getElementById('input_marque').value.trim();
    let modele = document.querySelector('input[name="modele"]').value.trim();
    let carburant = document.querySelector('select[name="carburant"]').value;

    if(!marque || !modele) {
        showCustomAlert("Veuillez d'abord saisir la marque et le modèle.");
        return;
    }

    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btn.disabled = true;

    try {
        // Utilisation de l'API Open Data de l'ADEME (Car Labelling) directement depuis le navigateur
        let query = marque + " " + modele + " " + carburant;
        let url = "https://data.ademe.fr/data-fair/api/v1/datasets/ademe-car-labelling/lines?q=" + encodeURIComponent(query) + "&size=1";

        let response = await fetch(url);
        let data = await response.json();
        
        if (data && data.results && data.results.length > 0) {
            let car = data.results[0];
            let estimation = null;
            
            // Cherche la conso mixte en L/100 ou kWh/100
            if (car.Conso_vitesse_mixte_Max) {
                estimation = car.Conso_vitesse_mixte_Max;
            } else if (car.Conso_vitesse_mixte_Min) {
                estimation = car.Conso_vitesse_mixte_Min;
            } else if (car.Conso_energie_electrique_Max) {
                estimation = car.Conso_energie_electrique_Max;
            } else if (car.Conso_energie_electrique_Min) {
                estimation = car.Conso_energie_electrique_Min;
            }

            if (estimation) {
                document.getElementById('input_conso').value = estimation;
                let input = document.getElementById('input_conso');
                input.style.transition = "background-color 0.5s ease";
                input.style.backgroundColor = "#e6fcf5"; 
                setTimeout(() => { input.style.backgroundColor = ""; }, 1500);
            } else {
                showCustomAlert("Véhicule trouvé dans la base ADEME, mais aucune donnée de consommation n'y figure.");
            }
        } else {
            showCustomAlert("Aucune donnée trouvée pour ce modèle dans la base officielle ADEME (Car Labelling).");
        }
    } catch(e) {
        showCustomAlert("Erreur de communication avec le serveur.");
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}
</script>

<div class="modal modal-blur fade" id="modalCustomAlert" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-warning"></div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <div class="text-muted mb-3" id="customAlertMessage">
                    Message
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100 btn-pill" data-bs-dismiss="modal">OK</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function showCustomAlert(message) {
    document.getElementById('customAlertMessage').innerText = message;
    var myModal = new bootstrap.Modal(document.getElementById('modalCustomAlert'));
    myModal.show();
}
</script>

<!-- Modal : Édition d'un véhicule -->
<div class="modal modal-blur fade" id="modalEditVehicule" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit text-orange me-2"></i> Éditer le véhicule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?action=save_referentiel" method="POST">
                    <input type="hidden" name="id" id="edit_vehicule_id">

                    <div class="mb-3">
                        <label class="form-label">Marque</label>
                        <input type="text" name="marque" id="edit_marque" list="list-marques" class="form-control" required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Modèle</label>
                        <input type="text" name="modele" id="edit_modele" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Carburant</label>
                        <select name="carburant" id="edit_carburant" class="form-select" required>
                            <option value="Gazole">Gazole</option>
                            <option value="Essence">Essence</option>
                            <option value="Electrique">Electrique</option>
                            <option value="Hybride">Hybride</option>
                            <option value="GPL">GPL</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Consommation moyenne (/100km)</label>
                        <div class="input-group">
                            <input type="number" step="0.1" name="conso_moyenne" id="edit_conso" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="estimerConsoEdit(this)" title="Recherche automatique ADEME"><i class="fas fa-magic text-orange me-1"></i> Auto</button>
                        </div>
                        <small class="form-hint">En Litres pour les thermiques, en kWh pour l'électrique.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-pill"><i class="fas fa-save me-2"></i>Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editVehicule(id, marque, modele, carburant, conso) {
    document.getElementById('edit_vehicule_id').value = id;
    document.getElementById('edit_marque').value = marque;
    document.getElementById('edit_modele').value = modele;
    document.getElementById('edit_carburant').value = carburant;
    document.getElementById('edit_conso').value = conso;
    new bootstrap.Modal(document.getElementById('modalEditVehicule')).show();
}

async function estimerConsoEdit(btn) {
    let marque = document.getElementById('edit_marque').value.trim();
    let modele = document.getElementById('edit_modele').value.trim();
    let carburant = document.getElementById('edit_carburant').value;

    if (!marque || !modele) {
        showCustomAlert("Veuillez d'abord saisir la marque et le modèle.");
        return;
    }

    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btn.disabled = true;

    try {
        let query = marque + " " + modele + " " + carburant;
        let url = "https://data.ademe.fr/data-fair/api/v1/datasets/ademe-car-labelling/lines?q=" + encodeURIComponent(query) + "&size=1";
        let response = await fetch(url);
        let data = await response.json();

        if (data && data.results && data.results.length > 0) {
            let car = data.results[0];
            let estimation = car.Conso_vitesse_mixte_Max || car.Conso_vitesse_mixte_Min
                           || car.Conso_energie_electrique_Max || car.Conso_energie_electrique_Min || null;
            if (estimation) {
                let input = document.getElementById('edit_conso');
                input.value = estimation;
                input.style.transition = "background-color 0.5s ease";
                input.style.backgroundColor = "#e6fcf5";
                setTimeout(() => { input.style.backgroundColor = ""; }, 1500);
            } else {
                showCustomAlert("Véhicule trouvé dans la base ADEME, mais aucune donnée de consommation n'y figure.");
            }
        } else {
            showCustomAlert("Aucune donnée trouvée pour ce modèle dans la base officielle ADEME.");
        }
    } catch(e) {
        showCustomAlert("Erreur de communication avec l'API ADEME.");
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}
</script>