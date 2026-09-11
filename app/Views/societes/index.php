<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title"><i class="fas fa-building me-2 text-primary"></i> Gestion des Sociétés & Filiales</h2>
                <div class="text-muted mt-1">Gérez la structure du groupe pour la consolidation multi-entités.</div>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSociete" onclick="resetFormSociete()">
                    <i class="fas fa-plus me-2"></i> Ajouter une société
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped" id="table-societes">
                <thead>
                    <tr>
                        <th>Nom de la Société</th>
                        <th>SIREN</th>
                        <th>Code Interne</th>
                        <th>Adresse</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th class="text-center">Données associées</th>
                        <th class="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($societes as $soc): ?>
                        <tr id="soc-row-<?= $soc['id'] ?>">
                            <td><strong class="soc-nom"><?= htmlspecialchars($soc['nom']) ?></strong></td>
                            <td><span class="text-muted soc-siren"><?= htmlspecialchars($soc['siren'] ?? '-') ?></span></td>
                            <td><span class="badge bg-blue-lt soc-code"><?= htmlspecialchars($soc['code_interne'] ?? 'N/A') ?></span></td>
                            <td><span class="text-muted soc-adresse"><?= htmlspecialchars(trim(($soc['adresse'] ?? '') . ' ' . ($soc['code_postal'] ?? '') . ' ' . ($soc['ville'] ?? ''))) ?: '-' ?></span></td>
                            <td><span class="text-muted soc-lat"><?= htmlspecialchars($soc['latitude'] ?? '-') ?></span></td>
                            <td><span class="text-muted soc-lon"><?= htmlspecialchars($soc['longitude'] ?? '-') ?></span></td>
                            <td class="text-center">
                                <span class="badge bg-green-lt"><?= $soc['total_imports'] ?> import(s)</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-list flex-nowrap justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick='editSociete(<?= json_encode($soc) ?>)'>
                                        <i class="fas fa-edit me-1"></i> Éditer
                                    </button>
                                    <?php if ((int)$soc['id'] !== 1): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="supprimerSocieteAjax(<?= $soc['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FORMULAIRE SOCIÉTÉ -->
<div class="modal modal-blur fade" id="modalSociete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="formSocieteAjax" onsubmit="enregistrerSocieteAjax(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSocieteTitle">Ajouter une Société</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="soc_id">
                    <div class="mb-3">
                        <label class="form-label required">Nom de l'entité / filiale</label>
                        <input type="text" name="nom" id="soc_nom" class="form-control" placeholder="ex: EcoTrace Sud-Ouest" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SIREN</label>
                            <input type="text" name="siren" id="soc_siren" class="form-control" maxlength="9" placeholder="9 chiffres">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code Interne / ERP</label>
                            <input type="text" name="code_interne" id="soc_code_interne" class="form-control" placeholder="ex: FIL-002">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse (Numéro et Rue)</label>
                        <input type="text" name="adresse" id="soc_adresse" class="form-control" placeholder="ex: 12 Rue de la Paix">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Code Postal</label>
                            <input type="text" name="code_postal" id="soc_code_postal" class="form-control" placeholder="ex: 75000">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Ville</label>
                            <div class="input-group">
                                <input type="text" name="ville" id="soc_ville" class="form-control" placeholder="ex: Paris">
                                <button class="btn btn-outline-secondary" type="button" onclick="geocodeSocieteBtn(this)" id="btn-geocode" title="Rechercher les coordonnées (lat/lon)">
                                    <i class="fas fa-search-location"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" id="soc_latitude" class="form-control" placeholder="ex: 45.19165526">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" name="longitude" id="soc_longitude" class="form-control" placeholder="ex: 0.76262712">
                        </div>
                    </div>
                    <div id="mapSociete" style="height: 250px; display: none; border-radius: 4px; border: 1px solid #e6e8e9; z-index: 1;" class="mb-3"></div>
                    <div id="soc-feedback" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btn-submit-soc" class="btn btn-primary"><i class="fas fa-save me-2"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let societeMap = null;
let societeMarker = null;

function updateSocieteMap(lat, lon) {
    let mapDiv = document.getElementById('mapSociete');
    mapDiv.style.display = 'block';
    
    if (!societeMap) {
        societeMap = L.map('mapSociete').setView([lat, lon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(societeMap);
        
        societeMarker = L.marker([lat, lon], { draggable: true }).addTo(societeMap);
        
        societeMarker.on('dragend', function (e) {
            let position = societeMarker.getLatLng();
            document.getElementById('soc_latitude').value = position.lat.toFixed(8);
            document.getElementById('soc_longitude').value = position.lng.toFixed(8);
        });
    } else {
        societeMap.setView([lat, lon], 13);
        societeMarker.setLatLng([lat, lon]);
    }
    
    setTimeout(() => { societeMap.invalidateSize(); }, 300);
}

function resetFormSociete() {
    document.getElementById('soc_id').value = '';
    document.getElementById('soc_nom').value = '';
    document.getElementById('soc_siren').value = '';
    document.getElementById('soc_code_interne').value = '';
    document.getElementById('soc_adresse').value = '';
    document.getElementById('soc_code_postal').value = '';
    document.getElementById('soc_ville').value = '';
    document.getElementById('soc_latitude').value = '';
    document.getElementById('soc_longitude').value = '';
    document.getElementById('soc-feedback').innerText = '';
    document.getElementById('modalSocieteTitle').innerText = 'Ajouter une Société';
    document.getElementById('mapSociete').style.display = 'none';
}

function editSociete(data) {
    document.getElementById('soc_id').value = data.id;
    document.getElementById('soc_nom').value = data.nom;
    document.getElementById('soc_siren').value = data.siren || '';
    document.getElementById('soc_code_interne').value = data.code_interne || '';
    document.getElementById('soc_adresse').value = data.adresse || '';
    document.getElementById('soc_code_postal').value = data.code_postal || '';
    document.getElementById('soc_ville').value = data.ville || '';
    document.getElementById('soc_latitude').value = data.latitude || '';
    document.getElementById('soc_longitude').value = data.longitude || '';
    document.getElementById('soc-feedback').innerText = '';
    document.getElementById('modalSocieteTitle').innerText = 'Éditer la Société';
    
    if (data.latitude && data.longitude) {
        updateSocieteMap(data.latitude, data.longitude);
    } else {
        document.getElementById('mapSociete').style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('modalSociete')).show();
}

function enregistrerSocieteAjax(event) {
    event.preventDefault();
    let btn = document.getElementById('btn-submit-soc');
    let feedback = document.getElementById('soc-feedback');
    feedback.innerText = '';
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enregistrement...';

    let formData = new FormData(document.getElementById('formSocieteAjax'));
    formData.append('action', 'save_societe_ajax');

    fetch('index.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Rafraîchit proprement sans redirection non désirée
        } else {
            feedback.innerText = data.message || 'Erreur lors de l\'enregistrement.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i> Enregistrer';
        }
    })
    .catch(() => {
        feedback.innerText = 'Erreur réseau.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i> Enregistrer';
    });
}

function supprimerSocieteAjax(id) {
    if (!confirm('Voulez-vous vraiment supprimer cette société ?')) return;

    let formData = new FormData();
    formData.append('action', 'delete_societe_ajax');
    formData.append('id', id);

    fetch('index.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let row = document.getElementById('soc-row-' + id);
            if (row) row.remove();
        } else {
            alert(data.message || 'Erreur lors de la suppression.');
        }
    });
}

function geocodeSocieteBtn(btn) {
    let adresse = document.getElementById('soc_adresse').value;
    let codePostal = document.getElementById('soc_code_postal').value;
    let ville = document.getElementById('soc_ville').value;
    
    if (!adresse && !codePostal && !ville) {
        alert("Veuillez saisir au moins une adresse, code postal ou ville.");
        return;
    }
    
    let originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    let formData = new FormData();
    formData.append('action', 'geocode_societe_ajax');
    formData.append('adresse', adresse);
    formData.append('code_postal', codePostal);
    formData.append('ville', ville);
    
    fetch('index.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (data.success) {
            document.getElementById('soc_latitude').value = data.lat;
            document.getElementById('soc_longitude').value = data.lon;
            updateSocieteMap(data.lat, data.lon);
        } else {
            alert(data.message || 'Introuvable.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Erreur réseau lors de la recherche.');
    });
}
</script>