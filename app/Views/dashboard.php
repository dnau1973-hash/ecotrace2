<?php use App\Helpers\EcoHelper; ?>
<style>
    /* Fix Bootstrap 5 table-striped hiding TR background colors */
    .table-striped > tbody > tr.bg-purple-lt > td,
    .table-striped > tbody > tr.bg-green-lt > td {
        --bs-table-accent-bg: transparent !important;
        --bs-table-bg: transparent !important;
        background-color: transparent !important;
        box-shadow: none !important;
    }
    
    /* Make sure the text color is enforced for Tabler light colors */
    .table-striped > tbody > tr.bg-purple-lt {
        background-color: rgba(138, 43, 226, 0.1) !important;
    }
    .table-striped > tbody > tr.bg-green-lt {
        background-color: rgba(47, 179, 68, 0.1) !important;
    }
</style>

<!-- ONGLET 1 : STATISTIQUES RSE -->
<div id="tab-stats" class="tab-content active">
    
    <!-- INDICATEURS VOLUMES & EMPREINTE -->
    <div class="row row-cards mb-3">
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-indigo text-white avatar"><i class="fas fa-industry"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Scope 1 & 2</div>
                            <div class="text-muted fs-3 fw-bold"><?= number_format($totalScope1 ?? 0, 2, ',', ' ') ?> kg</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-orange text-white avatar"><i class="fas fa-cloud"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Empreinte CO2 Estimée</div>
                            <div class="text-muted fs-3 fw-bold"><?= number_format($stats['totalCO2'] + ($totalScope1 ?? 0), 2, ',', ' ') ?> kg <span style='font-size: 12px; font-weight: normal;'>(Scopes 1,2,3)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-blue text-white avatar"><i class="fas fa-euro-sign"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Volume d'Achats Rapprochés</div>
                            <div class="text-muted fs-3 fw-bold"><?= number_format($stats['totalAchats'], 2, ',', ' ') ?> €</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-green text-white avatar"><i class="fas fa-box"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Poids Total Rapproché</div>
                            <div class="text-muted fs-3 fw-bold"><?= number_format($stats['totalPoids'], 2, ',', ' ') ?> kg</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NOUVEAUX INDICATEURS QUALITATIFS RSE -->
    <div class="row row-cards mb-4">
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-teal text-white avatar"><i class="fas fa-building-check"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Fournisseurs Validés</div>
                            <div class="text-muted fs-3 fw-bold"><?= $stats['countValides'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-pink text-white avatar"><i class="fas fa-handshake"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Acteurs Engagés ESS</div>
                            <div class="text-muted fs-3 fw-bold"><?= $stats['countEss'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-red text-white avatar"><i class="fas fa-exclamation-triangle"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Acteurs à Risque / Difficulté</div>
                            <div class="text-muted fs-3 fw-bold"><?= $stats['countRisque'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RANGÉE DES GRAPHIQUES -->
    <div class="row row-cards mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top 10 Secteurs d'Activité (Volume €)</h3></div>
                <div class="card-body"><canvas id="chartSecteurs" style="max-height: 280px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Répartition Alimentaire</h3></div>
                <div class="card-body"><canvas id="chartAlim" style="max-height: 250px;"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Impact Géographique</h3></div>
                <div class="card-body"><canvas id="chartGeo" style="max-height: 250px;"></canvas></div>
            </div>
        </div>
    </div>

    <!-- CARTOGRAPHIE -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Cartographie des Fournisseurs</h3></div>
                <div class="card-body p-0"><div id="fournisseursMap" style="height: 400px; z-index: 1;"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ONGLET SCOPE 1 & 2 -->
<div id="tab-scope1" class="tab-content">
    <div class="row row-cards mb-3">
        <div class="col-sm-6">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-blue text-white avatar"><i class="fas fa-industry"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">Total Scope 1 & 2</div>
                            <div class="text-muted fs-3 fw-bold"><?= number_format($totalScope1 ?? 0, 2, ',', ' ') ?> kg CO2e</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SOUS-ONGLETS SCOPE 1 & 2 -->
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header border-bottom-0 py-2 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills card-header-pills" style="margin: 0;">
                <li class="nav-item">
                    <a href="#subtab-scope1-livre" class="nav-link active fw-bold" onclick="openSubTab('subtab-scope1-livre', this)">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Livre de bord des consommations
                        <span class="badge bg-blue text-white ms-2"><?= count($scope1Emissions ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-scope1-flotte" class="nav-link fw-bold" onclick="openSubTab('subtab-scope1-flotte', this)">
                        <i class="fas fa-car text-blue me-2"></i> Inventaire de la flotte automobile
                        <span class="badge bg-purple text-white ms-2"><?= count($flotteVehicules ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-scope1-referentiel" class="nav-link fw-bold" onclick="openSubTab('subtab-scope1-referentiel', this)">
                        <i class="fas fa-car-side text-orange me-2"></i> Référentiel Véhicules
                        <span class="badge bg-orange text-white ms-2"><?= count($referentielVehicules ?? []) ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- SOUS-ONGLET 1 : LIVRE DE BORD DES CONSOMMATIONS -->
    <div id="subtab-scope1-livre" class="subtab-content active">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-clipboard-list text-primary me-2"></i> Livre de bord des consommations</h3>
                <button class="btn btn-primary btn-pill btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddScope1">
                    <i class="fas fa-plus me-2"></i> Ajouter une consommation
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Catégorie</th>
                            <th>Description</th>
                            <th>Quantité</th>
                            <th>Facteur d'émission</th>
                            <th>Total CO2</th>
                            <th class="w-1">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scope1Emissions)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune consommation enregistrée pour cette société.</td></tr>
                        <?php else: ?>
                            <?php foreach ($scope1Emissions as $s1): ?>
                                <tr>
                                    <td><span class="badge bg-azure"><?= htmlspecialchars($s1['annee']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($s1['categorie']) ?></strong></td>
                                    <td><?= htmlspecialchars($s1['description']) ?></td>
                                    <td><?= number_format($s1['quantite'], 2, ',', ' ') ?> <?= htmlspecialchars($s1['unite']) ?></td>
                                    <td class="text-muted"><?= number_format($s1['facteur_emission'], 4, ',', ' ') ?> kgCO2/unité</td>
                                    <td class="text-orange fw-bold"><?= number_format($s1['total_co2'], 2, ',', ' ') ?> kg</td>
                                    <td>
                                        <a href="?action=delete_scope1&id=<?= $s1['id'] ?>" class="btn btn-danger btn-sm" onclick="confirmGenericDelete('?action=delete_scope1&id=<?= $s1['id'] ?>', 'Êtes-vous sûr ?'); return false;"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SOUS-ONGLET 2 : INVENTAIRE DE LA FLOTTE AUTOMOBILE -->
    <div id="subtab-scope1-flotte" class="subtab-content">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-car text-blue me-2"></i> Inventaire de la Flotte Automobile</h3>
                <div>
                    <button class="btn btn-primary btn-pill btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalAddVehicle">
                        <i class="fas fa-plus me-2"></i> Ajouter un véhicule
                    </button>
                    <button class="btn btn-outline-success btn-pill btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportMileage">
                        <i class="fas fa-file-excel me-2"></i> Import Kilométrique (CSV)
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Plaque</th>
                            <th>Véhicule</th>
                            <th>Carburant</th>
                            <th>Conso moyenne</th>
                            <th>Statut</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($flotteVehicules)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Aucun véhicule dans l'inventaire.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($flotteVehicules as $veh): ?>
                            <tr>
                                <td><span class="badge bg-blue-lt fw-bold fs-4"><?= htmlspecialchars($veh['plaque']) ?></span></td>
                                <td><div class="fw-bold"><?= htmlspecialchars($veh['marque']) ?></div><div class="text-muted fs-5"><?= htmlspecialchars($veh['modele']) ?></div></td>
                                <td><?= htmlspecialchars($veh['carburant']) ?></td>
                                <td><?= htmlspecialchars($veh['conso_moyenne']) ?> L/100km</td>
                                <td>
                                    <?php if($veh['statut'] === 'Actif'): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white">Sorti</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <button class="btn btn-primary btn-sm btn-icon btn-pill" data-bs-toggle="modal" data-bs-target="#modalEditVehicle<?= $veh['id'] ?>"><i class="fas fa-edit"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm btn-icon btn-pill" onclick="confirmGenericDelete('?action=delete_vehicle&id=<?= $veh['id'] ?>', 'Retirer ce véhicule de l\'inventaire ?')"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if(!empty($flotteVehicules)): ?>
                <?php foreach($flotteVehicules as $veh): ?>
                <!-- Modal Edit Vehicle -->
                <div class="modal modal-blur fade" id="modalEditVehicle<?= $veh['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <form action="?action=edit_vehicle" method="POST" class="no-loader">
                                <input type="hidden" name="id" value="<?= $veh['id'] ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title">Éditer le véhicule</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Société / Filiale</label>
                                        <select name="societe_id" class="form-select" required>
                                            <?php foreach ($societes as $s): ?>
                                                <option value="<?= $s['id'] ?>" <?= ($veh['societe_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Plaque d'immatriculation</label>
                                        <input type="text" name="plaque" class="form-control" value="<?= htmlspecialchars($veh['plaque']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select name="statut" class="form-select" required>
                                            <option value="Actif" <?= ($veh['statut'] === 'Actif') ? 'selected' : '' ?>>Actif (En service)</option>
                                            <option value="Sorti" <?= ($veh['statut'] === 'Sorti') ? 'selected' : '' ?>>Sorti (Inactif / Vendu)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modifier le Modèle (Optionnel)</label>
                                        <select name="referentiel_id" class="form-select">
                                            <option value="">-- Ne pas modifier -- (Actuel: <?= htmlspecialchars($veh['marque'].' '.$veh['modele']) ?>)</option>
                                            <?php foreach ($referentielVehicules as $ref): ?>
                                                <option value="<?= $ref['id'] ?>">
                                                    <?= htmlspecialchars($ref['marque'] . ' ' . $ref['modele'] . ' (' . $ref['carburant'] . ' - ' . $ref['conso_moyenne'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary btn-pill">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SOUS-ONGLET 3 : RÉFÉRENTIEL VÉHICULES -->
    <div id="subtab-scope1-referentiel" class="subtab-content">
        <div class="row row-cards">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-car-side text-orange me-2"></i> Catalogue des modèles de référence</h3>
                        <span class="badge bg-orange text-white"><?= count($referentielVehicules ?? []) ?> modèles enregistrés</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Marque</th>
                                    <th>Modèle</th>
                                    <th>Carburant</th>
                                    <th>Conso Moyenne</th>
                                    <th class="w-1 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($referentielVehicules)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Aucun véhicule dans le référentiel.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($referentielVehicules as $ref): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ref['marque']) ?></td>
                                        <td><?= htmlspecialchars($ref['modele']) ?></td>
                                        <td><span class="badge bg-blue-lt"><?= htmlspecialchars($ref['carburant']) ?></span></td>
                                        <td><?= htmlspecialchars($ref['conso_moyenne']) ?> <?= stripos($ref['carburant'], 'electri') !== false ? 'kWh' : 'L' ?>/100km</td>
                                        <td class="text-center">
                                            <div class="btn-list flex-nowrap justify-content-center">
                                                <button type="button" class="btn btn-primary btn-sm btn-icon btn-pill" onclick="editVehiculeRef(<?= $ref['id'] ?>, '<?= addslashes(htmlspecialchars($ref['marque'])) ?>', '<?= addslashes(htmlspecialchars($ref['modele'])) ?>', '<?= addslashes(htmlspecialchars($ref['carburant'])) ?>', '<?= $ref['conso_moyenne'] ?>')" title="Éditer"><i class="fas fa-edit"></i></button>
                                                <button type="button" class="btn btn-danger btn-sm btn-icon btn-pill" onclick="confirmGenericDelete('?action=delete_referentiel&id=<?= $ref['id'] ?>&redirect_to=index.php', 'Supprimer ce modèle du catalogue de référence ?')" title="Supprimer"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle text-success me-2"></i> Ajouter au catalogue</h3>
                    </div>
                    <div class="card-body">
                        <form action="?action=save_referentiel" method="POST" class="no-loader">
                            <input type="hidden" name="redirect_to" value="index.php">
                            <datalist id="list-marques-dash">
                                <?php if(!empty($marquesExistantes)): ?>
                                    <?php foreach($marquesExistantes as $m): ?>
                                        <option value="<?= htmlspecialchars($m) ?>"></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </datalist>
                            <div class="mb-3">
                                <label class="form-label">Marque</label>
                                <input type="text" name="marque" id="input_marque_dash" list="list-marques-dash" class="form-control" placeholder="Ex: Renault" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Modèle</label>
                                <input type="text" name="modele" id="input_modele_dash" class="form-control" placeholder="Ex: Kangoo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Carburant</label>
                                <select name="carburant" id="input_carburant_dash" class="form-select" required>
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
                                    <input type="number" step="0.1" name="conso_moyenne" id="input_conso_dash" class="form-control" placeholder="Ex: 5.4" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="estimerConsoRef(this)" title="Recherche automatique ADEME"><i class="fas fa-magic text-orange me-1"></i> Auto</button>
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

<!-- Modal : Édition d'un véhicule du référentiel -->
<div class="modal modal-blur fade" id="modalEditVehiculeRef" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit text-orange me-2"></i> Éditer le véhicule de référence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?action=save_referentiel" method="POST" class="no-loader">
                    <input type="hidden" name="id" id="edit_ref_vehicule_id">
                    <input type="hidden" name="redirect_to" value="index.php">

                    <div class="mb-3">
                        <label class="form-label">Marque</label>
                        <input type="text" name="marque" id="edit_ref_marque" list="list-marques-dash" class="form-control" required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Modèle</label>
                        <input type="text" name="modele" id="edit_ref_modele" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Carburant</label>
                        <select name="carburant" id="edit_ref_carburant" class="form-select" required>
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
                            <input type="number" step="0.1" name="conso_moyenne" id="edit_ref_conso" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="estimerConsoEditRef(this)" title="Recherche automatique ADEME"><i class="fas fa-magic text-orange me-1"></i> Auto</button>
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
function editVehiculeRef(id, marque, modele, carburant, conso) {
    document.getElementById('edit_ref_vehicule_id').value = id;
    document.getElementById('edit_ref_marque').value = marque;
    document.getElementById('edit_ref_modele').value = modele;
    document.getElementById('edit_ref_carburant').value = carburant;
    document.getElementById('edit_ref_conso').value = conso;
    new bootstrap.Modal(document.getElementById('modalEditVehiculeRef')).show();
}

async function estimerConsoRef(btn) {
    let marque = document.getElementById('input_marque_dash').value.trim();
    let modele = document.getElementById('input_modele_dash').value.trim();
    let carburant = document.getElementById('input_carburant_dash').value;

    if(!marque || !modele) {
        alert("Veuillez d'abord saisir la marque et le modèle.");
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
                let input = document.getElementById('input_conso_dash');
                input.value = estimation;
                input.style.transition = "background-color 0.5s ease";
                input.style.backgroundColor = "#e6fcf5"; 
                setTimeout(() => { input.style.backgroundColor = ""; }, 1500);
            } else {
                alert("Véhicule trouvé dans la base ADEME, mais aucune donnée de consommation n'y figure.");
            }
        } else {
            alert("Aucune donnée trouvée pour ce modèle dans la base officielle ADEME (Car Labelling).");
        }
    } catch(e) {
        alert("Erreur de communication avec le serveur ADEME.");
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}

async function estimerConsoEditRef(btn) {
    let marque = document.getElementById('edit_ref_marque').value.trim();
    let modele = document.getElementById('edit_ref_modele').value.trim();
    let carburant = document.getElementById('edit_ref_carburant').value;

    if(!marque || !modele) {
        alert("Veuillez d'abord saisir la marque et le modèle.");
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
                let input = document.getElementById('edit_ref_conso');
                input.value = estimation;
                input.style.transition = "background-color 0.5s ease";
                input.style.backgroundColor = "#e6fcf5"; 
                setTimeout(() => { input.style.backgroundColor = ""; }, 1500);
            } else {
                alert("Véhicule trouvé dans la base ADEME, mais aucune donnée de consommation n'y figure.");
            }
        } else {
            alert("Aucune donnée trouvée pour ce modèle dans la base officielle ADEME (Car Labelling).");
        }
    } catch(e) {
        alert("Erreur de communication avec le serveur ADEME.");
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}
</script>

<!-- Modal Add Vehicle -->
<div class="modal modal-blur fade" id="modalAddVehicle" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=add_vehicle" method="POST" class="no-loader">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un véhicule au référentiel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Société / Filiale</label>
                        <select name="societe_id" class="form-select" required>
                            <?php foreach ($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($activeSocieteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plaque d'immatriculation</label>
                            <input type="text" name="plaque" class="form-control" placeholder="AB-123-CD" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Modèle de référence</label>
                            <select name="referentiel_id" class="form-select" required>
                                <option value="" disabled selected>-- Choisir le véhicule --</option>
                                <?php foreach ($referentielVehicules as $ref): ?>
                                    <option value="<?= $ref['id'] ?>">
                                        <?= htmlspecialchars($ref['marque'] . ' ' . $ref['modele'] . ' (' . $ref['carburant'] . ' - ' . $ref['conso_moyenne'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter à l'inventaire</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Mileage -->
<div class="modal modal-blur fade" id="modalImportMileage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=import_mileage" method="POST" enctype="multipart/form-data" class="no-loader">
                <div class="modal-header">
                    <h5 class="modal-title">Import Kilométrique (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Le fichier CSV doit contenir 2 colonnes (sans en-tête) :<br>
                        <strong>1. Plaque</strong> (ex: AB-123-CD)<br>
                        <strong>2. Kilométrage</strong> (ex: 15000)
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Société / Filiale cible</label>
                        <select name="societe_id" class="form-select" required>
                            <option value="">-- Sélectionner la filiale --</option>
                            <?php foreach ($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($activeSocieteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Année d'imputation</label>
                        <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier CSV</label>
                        <input type="file" name="file_mileage" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload me-2"></i> Lancer l'import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Scope 1 -->
<div class="modal modal-blur fade" id="modalAddScope1" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=add_scope1" method="POST" class="no-loader">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une consommation (Scope 1 & 2)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Société / Filiale</label>
                            <select name="societe_id" id="scope1_societe" class="form-select" required onchange="updateScope1Defaults()">
                                <?php foreach ($societes as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($activeSocieteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="categorie" id="scope1_categorie" required onchange="updateScope1Defaults()">
                                <option value="" disabled selected>-- Sélectionner --</option>
                                <option value="Combustion Mobile (Gazole)" data-unite="Litres" data-fe="2.8">Véhicules (Gazole/Diesel)</option>
                                <option value="Combustion Mobile (Essence)" data-unite="Litres" data-fe="2.5">Véhicules (Essence)</option>
                                <option value="Combustion Fixe (Gaz Naturel)" data-unite="kWh PCS" data-fe="0.227">Chauffage (Gaz Naturel)</option>
                                <option value="Combustion Fixe (Fioul)" data-unite="Litres" data-fe="2.68">Chauffage (Fioul domestique)</option>
                                <option value="Électricité (Réseau Français)" data-unite="kWh" data-fe="0.05">Électricité (Scope 2)</option>
                                <option value="Fuites Frigorigènes" data-unite="kg" data-fe="2000">Climatisation (Fuite R410A etc.)</option>
                                <option value="Autre" data-unite="Unités" data-fe="1.0">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Année de reporting</label>
                            <input type="number" name="annee" id="scope1_annee" class="form-control" value="<?= date('Y') ?>" required onchange="updateScope1Defaults()" oninput="updateScope1Defaults()">
                        </div>
                        
                        <!-- DUAL DESCRIPTION FIELD -->
                        <div class="col-md-12 mb-3" id="wrapper_desc_text">
                            <label class="form-label">Description (Ex: Chaudière siège social)</label>
                            <input type="text" name="description" id="input_desc_text" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3" id="wrapper_desc_vehicule" style="display:none;">
                            <label class="form-label">Véhicule concerné</label>
                            <select id="input_desc_vehicule" class="form-select">
                            </select>
                            <small class="form-hint text-blue"><i class="fas fa-info-circle"></i> Seuls les véhicules n'ayant pas encore de saisie pour cette année s'affichent.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantité</label>
                            <input type="number" step="0.01" name="quantite" id="scope1_quantite" class="form-control" required oninput="calcScope1()">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unité</label>
                            <input type="text" name="unite" id="scope1_unite" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Facteur d'émission (ADEME)</label>
                            <input type="number" step="0.0001" name="facteur_emission" id="scope1_fe" class="form-control" required oninput="calcScope1()">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div class="me-auto text-muted">
                        Total estimé : <strong class="text-orange fs-3" id="scope1_total">0</strong> kg CO2e
                    </div>
                    <button type="button" class="btn me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const allVehicles = <?= json_encode($flotteVehicules ?? []) ?>;
const allEmissions = <?= json_encode($scope1Emissions ?? []) ?>;

function updateScope1Defaults() {
    let sel = document.getElementById('scope1_categorie');
    let opt = sel.options[sel.selectedIndex];
    if(opt && opt.value !== "") {
        document.getElementById('scope1_unite').value = opt.getAttribute('data-unite');
        document.getElementById('scope1_fe').value = opt.getAttribute('data-fe');
        calcScope1();
    }
    
    // Combo Box Logic
    let isVehicule = opt && opt.value.includes('Mobile');
    let wrapperText = document.getElementById('wrapper_desc_text');
    let inputText = document.getElementById('input_desc_text');
    let wrapperSelect = document.getElementById('wrapper_desc_vehicule');
    let inputSelect = document.getElementById('input_desc_vehicule');
    
    if (isVehicule) {
        wrapperText.style.display = 'none';
        inputText.removeAttribute('name');
        inputText.removeAttribute('required');
        
        wrapperSelect.style.display = 'block';
        inputSelect.setAttribute('name', 'description');
        inputSelect.setAttribute('required', 'required');
        
        let targetAnnee = document.getElementById('scope1_annee').value;
        let targetSoc = document.getElementById('scope1_societe').value;
        let targetCarburantStr = opt.value.toLowerCase(); // e.g. "combustion mobile (gazole)"
        
        // Populate select
        inputSelect.innerHTML = '<option value="" disabled selected>-- Choisir un véhicule --</option>';
        
        allVehicles.forEach(veh => {
            // Check if vehicle belongs to selected society
                        if (veh.societe_id != targetSoc) return;
            if (veh.statut === 'Sorti') return; // Ne pas afficher les véhicules sortis
            
            // Basic fuel matching based on category
            if (targetCarburantStr.includes('gazole') && veh.carburant.toLowerCase() !== 'gazole') return;
            if (targetCarburantStr.includes('essence') && veh.carburant.toLowerCase() !== 'essence') return;
            // Electric vehicles usually not in Scope 1, but if it is mapped to a specific category, same logic applies.
            
            // Check if this vehicle already has a consumption for targetAnnee
            let alreadyHas = allEmissions.some(e => 
                e.annee == targetAnnee && 
                e.societe_id == targetSoc &&
                (e.description.includes(veh.plaque) || e.description === "Véhicule " + veh.plaque)
            );
            
            if (!alreadyHas) {
                let generatedDescription = "Véhicule " + veh.plaque + " (" + veh.marque + " " + veh.modele + ")";
                let option = document.createElement('option');
                option.value = generatedDescription;
                option.text = veh.plaque + ' - ' + veh.marque + ' ' + veh.modele;
                // Save the consumption average as a data attribute so we can auto-calculate if needed
                option.setAttribute('data-conso', veh.conso_moyenne);
                inputSelect.appendChild(option);
            }
        });
        
    } else {
        wrapperSelect.style.display = 'none';
        inputSelect.removeAttribute('name');
        inputSelect.removeAttribute('required');
        
        wrapperText.style.display = 'block';
        inputText.setAttribute('name', 'description');
        inputText.setAttribute('required', 'required');
    }
}
function calcScope1() {
    let q = parseFloat(document.getElementById('scope1_quantite').value) || 0;
    let f = parseFloat(document.getElementById('scope1_fe').value) || 0;
    document.getElementById('scope1_total').innerText = (q * f).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>


<!-- ONGLET SCOPE 3 (Wrappe tous les anciens onglets Scope 3) -->
<div id="tab-scope3" class="tab-content">
    <style>
        .subtab-content { display: none; }
        .subtab-content.active { display: block; }
        .scope3-main-tab { display: none; }
        .scope3-main-tab.active { display: block; }
    </style>
    
    <!-- 3 SOUS-ONGLETS PRINCIPAUX DU SCOPE 3 -->
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-header border-bottom-0 py-2 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills card-header-pills" style="margin: 0;">
                <li class="nav-item">
                    <a href="#subtab-scope3-livre" class="nav-link active fw-bold scope3-main-nav-link" onclick="openScope3Tab('subtab-scope3-livre', this)">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Livre de bord des Achats & Fret
                        <span class="badge bg-blue text-white ms-2" id="badgeTotalAchats"><?= count($sourcesValides ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-scope3-inventaire" class="nav-link fw-bold scope3-main-nav-link" onclick="openScope3Tab('subtab-scope3-inventaire', this)">
                        <i class="fas fa-building text-purple me-2"></i> Inventaire & Qualification des Sociétés
                        <span class="badge bg-purple text-white ms-2"><?= count($fournisseursQualifies ?? []) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-scope3-naf" class="nav-link fw-bold scope3-main-nav-link" onclick="openScope3Tab('subtab-scope3-naf', this)">
                        <i class="fas fa-tags text-indigo me-2"></i> Gestion des Codes NAF
                        <span class="badge bg-indigo text-white ms-2"><?= count($nafPrivilegies ?? []) ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- 1. SOUS-ONGLET PRINCIPAL : LIVRE DE BORD DES ACHATS & FRET -->
    <div id="subtab-scope3-livre" class="scope3-main-tab active">
        <?php
        // Calcul des métriques globales Scope 3
        $totalMontantScope3 = 0;
        $totalPoidsScope3 = 0;
        $totalCo2Scope3 = 0;
        $totalLocalScope3 = 0;
        $totalCountScope3 = count($sourcesValides ?? []);

        foreach ($sourcesValides as $src) {
            $m = (float)($src['montant'] ?? 0);
            $p = (float)($src['poids'] ?? 0);
            $d = $src['distance'];
            $naf = $src['activite_principale'] ?? '';
            $co2 = \App\Helpers\EcoHelper::estimerCO2($naf, $m, $p, $d);
            $totalMontantScope3 += $m;
            $totalPoidsScope3 += $p;
            $totalCo2Scope3 += $co2;
            if ($d !== null && (float)$d <= 50) {
                $totalLocalScope3++;
            }
        }
        $tauxLocalScope3 = $totalCountScope3 > 0 ? round(($totalLocalScope3 / $totalCountScope3) * 100, 1) : 0;
        ?>

        <!-- Synthèse KPIs Scope 3 -->
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="fas fa-euro-sign"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Dépenses Totales</div>
                                <div class="text-muted fs-2 fw-bold" id="kpi-scope3-montant"><?= number_format($totalMontantScope3, 2, ',', ' ') ?> €</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-teal text-white avatar"><i class="fas fa-truck-loading"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Tonnage Fret</div>
                                <div class="text-muted fs-2 fw-bold" id="kpi-scope3-poids"><?= number_format($totalPoidsScope3 / 1000, 2, ',', ' ') ?> T</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-purple text-white avatar"><i class="fas fa-cloud"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Émissions Scope 3</div>
                                <div class="text-muted fs-2 fw-bold text-purple" id="kpi-scope3-co2"><?= number_format($totalCo2Scope3 / 1000, 2, ',', ' ') ?> tCO₂e</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Alliance Locale (&lt; 50km)</div>
                                <div class="text-muted fs-2 fw-bold text-success" id="kpi-scope3-local"><?= $tauxLocalScope3 ?> %</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau du Journal des Dépenses & Fret -->
        <div class="card mb-4 shadow-sm border-0">
            <form id="formBulkDeleteAchats" action="?action=delete_achat_scope3" method="POST" class="d-none"></form>
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="card-title m-0"><i class="fas fa-clipboard-list text-primary me-2"></i> Journal des Dépenses & Fret</h3>
                    <span class="badge bg-blue-lt ms-2" id="kpi-scope3-count"><?= count($sourcesValides ?? []) ?> flux enregistrés</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label mb-0 small text-muted fw-bold">Exercice :</label>
                        <select id="selectAnneeScope3" class="form-select form-select-sm" style="width: 150px;" onchange="filtrerAnneeScope3(this.value)">
                            <option value="all">📅 Toutes les années</option>
                            <?php foreach ($anneesScope3 as $yr): ?>
                                <option value="<?= $yr ?>" <?= ($yr == 2024 || $yr == date('Y')) ? 'selected' : '' ?>><?= $yr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-pill d-none" id="btnDeleteSelectedAchats" onclick="confirmBulkDeleteAchats()">
                        <i class="fas fa-trash me-1"></i> Supprimer (<span id="countSelectedAchats">0</span>)
                    </button>
                    <button class="btn btn-primary btn-sm btn-pill" id="btnAddAchatScope3" data-bs-toggle="modal" data-bs-target="#modalAddAchatScope3">
                        <i class="fas fa-plus me-1"></i> Ajouter un achat
                    </button>
                    <a href="?action=import_csv" class="btn btn-outline-success btn-sm btn-pill" id="btnImportExerciceCsv">
                        <i class="fas fa-file-excel me-1"></i> Importer Exercice (CSV)
                    </a>
                </div>
            </div>

            <!-- DEUXIÈME BARRE D'OUTILS (TOOLBAR SECONDAIRE) -->
            <div class="card-body bg-light-subtle py-2 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" id="toolbarScope3Secondary">
                <!-- Statut et Clôture de l'exercice -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small fw-bold"><i class="fas fa-calendar-check me-1"></i> État de l'exercice :</span>
                    <span id="badgeStatutExercice" class="badge bg-success-lt">
                        <i class="fas fa-lock-open me-1"></i> Exercice <span class="annee-active-label">2024</span> : Ouvert
                    </span>
                    <button type="button" id="btnClotureExercice" class="btn btn-sm btn-outline-warning btn-pill" onclick="ouvrirModalCloture()">
                        <i class="fas fa-lock me-1"></i> Clôturer l'exercice <span class="annee-active-label">2024</span>
                    </button>
                    <span id="labelDateCloture" class="text-muted small d-none"></span>
                </div>
                <!-- Actions avancées : Import par SIREN -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnImportSirenCsv" class="btn btn-teal btn-sm btn-pill" data-bs-toggle="modal" data-bs-target="#modalImportSirenCsv">
                        <i class="fas fa-file-import me-1"></i> Importer Dépenses par SIREN (CSV 3 colonnes)
                    </button>
                </div>
            </div>

            <!-- BANDEAU D'ALERTE QUAND L'EXERCICE EST CLÔTURÉ -->
            <div id="alertExerciceCloture" class="alert alert-warning m-3 mb-0 py-2 d-none">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock fa-lg text-warning me-3"></i>
                    <div>
                        <strong>Exercice <span class="annee-active-label">2024</span> clôturé et verrouillé.</strong>
                        <div class="text-muted small" id="alertExerciceClotureDetail">Les flux comptables et émissions de cet exercice sont figés pour conformité et audit. Les ajouts, modifications et suppressions sont verrouillés.</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped table-hover" id="tableAchatsScope3">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <input class="form-check-input" type="checkbox" id="checkAllAchatsScope3" onchange="toggleSelectAllAchats(this)" title="Tout sélectionner (exercice actif)">
                            </th>
                            <th class="w-1">Année</th>
                            <th>Fournisseur (Inventaire)</th>
                            <th>Secteur / Code NAF</th>
                            <th class="text-end">Montant (€ HT)</th>
                            <th class="text-end">Fret (kg)</th>
                            <th class="text-center">Distance</th>
                            <th class="text-center">Facteur ADEME</th>
                            <th class="text-end">Émissions CO₂e</th>
                            <th class="w-1 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="rowEmptyAchats" style="<?= empty($sourcesValides) ? '' : 'display: none;' ?>">
                            <td colspan="10" class="text-center text-muted py-4">Aucune dépense enregistrée pour cet exercice dans le Scope 3.</td>
                        </tr>
                        <?php if (!empty($sourcesValides)): ?>
                            <?php foreach ($sourcesValides as $src): ?>
                                <?php 
                                $m = (float)($src['montant'] ?? 0);
                                $p = (float)($src['poids'] ?? 0);
                                $d = $src['distance'];
                                $naf = $src['activite_principale'] ?? '';
                                $co2 = \App\Helpers\EcoHelper::estimerCO2($naf, $m, $p, $d);
                                $facteur = $ademeFacteurs[$naf] ?? 0.2000;
                                $isLocal = ($d !== null && (float)$d <= 50);
                                $anneeSrc = (int)($src['annee'] ?? 2024);
                                $estCloture = (($statutsExercices[$anneeSrc]['statut'] ?? 'ouvert') === 'cloture');
                                ?>
                                <tr class="row-achat-scope3 <?= $estCloture ? 'row-cloturee' : '' ?>" 
                                    data-annee="<?= $anneeSrc ?>" 
                                    data-montant="<?= $m ?>" 
                                    data-poids="<?= $p ?>" 
                                    data-distance="<?= $d !== null ? (float)$d : '' ?>" 
                                    data-co2="<?= $co2 ?>" 
                                    data-local="<?= $isLocal ? '1' : '0' ?>"
                                    data-cloture="<?= $estCloture ? '1' : '0' ?>">
                                    <td class="text-center">
                                        <input class="form-check-input check-achat-scope3" type="checkbox" value="<?= $src['source_id'] ?>" onchange="updateSelectedAchatsScope3()" <?= $estCloture ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <span class="badge <?= $estCloture ? 'bg-secondary' : 'bg-azure' ?> text-white">
                                            <?= htmlspecialchars($anneeSrc) ?>
                                            <?= $estCloture ? ' 🔒' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($src['nom_complet'] ?: $src['nom_recherche']) ?></div>
                                        <div class="small text-muted d-flex align-items-center gap-2">
                                            <?php if (!empty($src['siren'])): ?>
                                                <span class="badge bg-dark-lt font-monospace"><?= htmlspecialchars($src['siren']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($src['siege_adresse'])): ?>
                                                <span><?= htmlspecialchars(substr($src['siege_adresse'], 0, 35)) ?>...</span>
                                            <?php endif; ?>
                                            <?php if ($isLocal): ?>
                                                <span class="badge bg-green-lt"><i class="fas fa-leaf me-1"></i> &lt; 50km</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($src['activite_principale'])): ?>
                                            <span class="badge bg-purple-lt fw-bold me-1"><?= htmlspecialchars($src['activite_principale']) ?></span>
                                        <?php endif; ?>
                                        <span class="small text-muted"><?= htmlspecialchars($src['activite_principale_libelle'] ?? 'Non défini') ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($m, 2, ',', ' ') ?> €</td>
                                    <td class="text-end"><?= $p > 0 ? number_format($p, 0, ',', ' ') . ' kg' : '<span class="text-muted">-</span>' ?></td>
                                    <td class="text-center">
                                        <?php if ($d !== null): ?>
                                            <span class="badge bg-blue-lt"><?= round($d) ?> km</span>
                                        <?php else: ?>
                                            <span class="text-muted fs-5">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace small"><?= number_format($facteur, 4, ',', ' ') ?></td>
                                    <td class="text-end fw-bold text-purple"><?= number_format($co2, 2, ',', ' ') ?> kg</td>
                                    <td class="text-center">
                                        <div class="btn-list flex-nowrap justify-content-center">
                                            <?php if ($estCloture): ?>
                                                <span class="badge bg-light text-muted" title="Exercice clôturé : modification impossible">
                                                    <i class="fas fa-lock"></i> Figé
                                                </span>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-primary btn-sm btn-icon btn-pill btn-edit-achat" 
                                                        onclick="editAchatScope3(<?= $src['source_id'] ?>, '<?= addslashes(htmlspecialchars($src['nom_complet'] ?: $src['nom_recherche'])) ?>', <?= $anneeSrc ?>, <?= $m ?>, <?= $p ?>)" 
                                                        title="Éditer">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-icon btn-pill btn-delete-achat" 
                                                        onclick="confirmGenericDelete('?action=delete_achat_scope3&id=<?= $src['source_id'] ?>&redirect_to=index.php', 'Supprimer cette dépense du livre de bord ?')" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. SOUS-ONGLET PRINCIPAL : INVENTAIRE & QUALIFICATION DES SOCIÉTÉS -->
    <div id="subtab-scope3-inventaire" class="scope3-main-tab">
        <div class="card mb-3 shadow-sm border-0">
        <div class="card-header border-bottom-0 py-2 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills card-header-pills" style="margin: 0;">
                <li class="nav-item">
                    <a href="#subtab-matching" class="nav-link active fw-bold" onclick="openSubTab('subtab-matching', this)">
                        <i class="fas fa-code-branch me-2"></i> Rapprochement 
                        <span class="badge bg-blue text-blue-fg ms-2"><?= $countEnAttente ?? 0 ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-introuvables" class="nav-link fw-bold" onclick="openSubTab('subtab-introuvables', this)">
                        <i class="fas fa-search-minus me-2"></i> Introuvables 
                        <span class="badge bg-red text-red-fg ms-2"><?= $countIntrouvables ?? 0 ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-validated" class="nav-link fw-bold" onclick="openSubTab('subtab-validated', this)">
                        <i class="fas fa-checks me-2"></i> Validées 
                        <span class="badge bg-green text-green-fg ms-2"><?= $countValides ?? 0 ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#subtab-manual" class="nav-link fw-bold" onclick="openSubTab('subtab-manual', this)">
                        <i class="fas fa-plus-circle me-2"></i> Ajout Manuel
                    </a>
                </li>
            </ul>

            <!-- Barre d'outils Scope 3 alignée à droite -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <!-- Bouton avec menu déroulant pour les actions d'Import Scope 3 -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i> Imports
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm">
                        <a href="?action=import_csv" class="dropdown-item text-blue">
                            <i class="fas fa-file-excel me-2"></i> Importer CSV enrichi
                        </a>
                        <a href="?action=import_siren" class="dropdown-item text-teal">
                            <i class="fas fa-highlighter me-2"></i> Surligner via SIREN
                        </a>
                    </div>
                </div>

                <!-- Bouton avec menu déroulant pour les actions de Traitement Scope 3 -->
                <div class="dropdown d-flex align-items-center">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs me-1"></i> Traitements
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm p-1" style="min-width: 290px;">
                        <div class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span onclick="lancerMajDistances()" class="text-purple cursor-pointer flex-grow-1">
                                <i class="fas fa-map-marker-alt me-2"></i> Maj Distances (Villes)
                            </span>
                            <span class="badge bg-purple-lt cursor-pointer ms-2" onclick="event.stopPropagation(); afficherAideTraitement('distances')" title="Explication de la fonctionnalité">
                                <i class="fas fa-question"></i>
                            </span>
                        </div>
                        <div class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span onclick="lancerMajNaf()" class="text-pink cursor-pointer flex-grow-1">
                                <i class="fas fa-book me-2"></i> Maj NAF (INSEE)
                            </span>
                            <span class="badge bg-pink-lt cursor-pointer ms-2" onclick="event.stopPropagation(); afficherAideTraitement('naf')" title="Explication de la fonctionnalité">
                                <i class="fas fa-question"></i>
                            </span>
                        </div>
                        <div class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span onclick="lancerValidationAuto()" class="text-green cursor-pointer flex-grow-1">
                                <i class="fas fa-magic me-2"></i> Validation Auto (Siren connus)
                            </span>
                            <span class="badge bg-green-lt cursor-pointer ms-2" onclick="event.stopPropagation(); afficherAideTraitement('connus')" title="Explication de la fonctionnalité">
                                <i class="fas fa-question"></i>
                            </span>
                        </div>
                        <div class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span onclick="lancerRelanceIntrouvables()" class="text-warning cursor-pointer flex-grow-1">
                                <i class="fas fa-broom me-2"></i> Nettoyer & Relancer (Introuvables)
                            </span>
                            <span class="badge bg-warning-lt cursor-pointer ms-2" onclick="event.stopPropagation(); afficherAideTraitement('introuvables')" title="Explication de la fonctionnalité">
                                <i class="fas fa-question"></i>
                            </span>
                        </div>
                        <div class="dropdown-divider my-1"></div>
                        <div class="dropdown-item d-flex align-items-center justify-content-between py-2">
                            <span onclick="lancerMajGeo()" class="text-lime cursor-pointer flex-grow-1">
                                <i class="fas fa-globe me-2"></i> Maj Origines Géo
                            </span>
                            <span class="badge bg-lime-lt cursor-pointer ms-2" onclick="event.stopPropagation(); afficherAideTraitement('geo')" title="Explication de la fonctionnalité">
                                <i class="fas fa-question"></i>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="afficherAideTraitement('general')" title="Explication globale des fonctionnalités">
                        <i class="fas fa-question"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SOUS-ONGLET RAPPROCHEMENT -->
    <div id="subtab-matching" class="subtab-content active">
    <?php if (empty($sourcesEnAttente)): ?>
        <div class="alert alert-success" role="alert">
            <h4 class="alert-title">Bravo !</h4>
            <div class="text-muted">Plus aucune entreprise n'est en attente de validation.</div>
        </div>
    <?php else: ?>
        <div class="mb-4">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="fas fa-search"></i></span>
                <input type="text" id="filterMatchingInput" onkeyup="filtrerRapprochement()" class="form-control" placeholder="Filtrer les entités en attente (par nom...)">
            </div>
        </div>
        
        <div id="list-matching">
            <?php foreach ($sourcesEnAttente as $source): ?>
                <div class="card mb-4 card-matching shadow-sm" id="card-<?= $source['id'] ?>">
                    <div class="card-header bg-light">
                        <div class="d-flex align-items-center w-100">
                            <span class="text-muted me-2 flex-shrink-0">Recherche CSV :</span> 
                            <strong class="source-title fs-3 text-primary text-truncate me-auto">
                                <?= htmlspecialchars($source['nom_recherche'] ?? '') ?>
                            </strong>
                            <?php if (!empty($source['societe_nom'])): ?>
                                <span class="badge bg-purple-lt ms-2" title="Société Importatrice">
                                    <i class="fas fa-building me-1"></i> <?= htmlspecialchars($source['societe_nom']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-blue-lt flex-shrink-0 text-center" style="width: 110px;">
                                <?= count($source['candidats'] ?? []) ?> candidat(s)
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="bg-white p-3 border-bottom" id="action-cell-<?= $source['id'] ?>">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <input type="text" id="input-nom-<?= $source['id'] ?>" value="<?= htmlspecialchars($source['nom_recherche'] ?? '') ?>" class="form-control">
                                </div>
                                <div class="col-md-auto mt-3 mt-md-0">
                                    <button class="btn btn-primary btn-sm btn-pill" onclick="lancerRechercheAjax(<?= $source['id'] ?>, true, 'gouv')"><i class="fas fa-search me-1"></i> Rechercher</button>
                                    
                                    <button class="btn btn-danger btn-sm btn-icon btn-pill ms-2" onclick="supprimerCandidatAjax(this, <?= $source['id'] ?>)" title="Supprimer cet enregistrement"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div id="feedback-<?= $source['id'] ?>" class="mt-2 small font-weight-bold"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped mb-0" style="table-layout: fixed; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;">Candidat (Détails)</th>
                                        <th class="text-center" style="width: 20%;">Labels RSE</th>
                                        <th class="text-center" style="width: 20%;">CO2 Est.</th>
                                        <th class="text-center" style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($source['candidats'])): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">Aucun candidat trouvé. Essayez une recherche manuelle via les boutons ci-dessus.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($source['candidats'] as $candidat): ?>
                                            <?php 
    $highlightClass = '';
    if (!empty($candidat['est_connu'])) {
        $highlightClass = 'bg-green-lt';
    } elseif (in_array($candidat['activite_principale'], $nafPrivilegies ?? [])) {
        $highlightClass = 'bg-purple-lt';
    }
?>
                                            <tr data-siren="<?= htmlspecialchars($candidat['siren'] ?? '') ?>" class="<?= $highlightClass ?>">
                                                <td>
                                                    <?php 
                                                    $sJ = $candidat['statut_juridique'] ?? 'Actif'; 
                                                    $badgeColor = ($sJ === 'Fermée' || strpos($sJ, 'Liquidation') !== false) ? 'bg-red' : (($sJ !== 'Actif') ? 'bg-orange' : 'bg-green'); 
                                                    ?>
                                                    <div class="font-weight-medium d-flex align-items-center">
                                                        <span class="badge <?= $badgeColor ?> text-white me-2 flex-shrink-0" style="width: 115px; text-align: center; cursor: pointer;" 
                                                              onclick="afficherBodacc('<?= htmlspecialchars($candidat['siren'] ?? '') ?>', '<?= addslashes(htmlspecialchars($candidat['nom_complet'] ?? '')) ?>')" title="Analyser le risque">
                                                            <?= htmlspecialchars($sJ) ?> <i class="fas fa-search ms-1"></i>
                                                        </span>
                                                        <span class="text-truncate" title="<?= htmlspecialchars($candidat['nom_complet'] ?? '') ?>"><?= htmlspecialchars($candidat['nom_complet'] ?? '') ?></span>
                                                    </div>
                                                    <div class="text-muted mt-1" style="font-size: 12px;">
                                                        SIREN : <?= htmlspecialchars($candidat['siren'] ?? '') ?><br>
                                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($candidat['siege_adresse'] ?? '') ?>
                                                    </div>
                                                    <div class="mt-2 text-truncate" style="font-size: 13px;">
                                                        <strong><?= htmlspecialchars($candidat['activite_principale'] ?? '') ?></strong> - <?= htmlspecialchars($candidat['activite_principale_libelle'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($candidat['est_alimentaire']): ?><span class="badge bg-yellow text-white mb-1" style="width: 90px;">Alimentaire</span><br><?php else: ?><span class="badge bg-secondary text-white mb-1" style="width: 90px;">NON Alim.</span><br><?php endif; ?>
                                                    <?php if ($candidat['est_ess']): ?><span class="badge bg-pink text-white mb-1" style="width: 90px;">🤝 ESS</span><br><?php endif; ?>
                                                    <?php if ($candidat['est_societe_mission']): ?><span class="badge bg-teal text-white" style="width: 90px;">🎯 Mission</span><?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="text-orange fw-bold fs-3"><?= EcoHelper::estimerCO2($candidat['activite_principale'], $source['montant'], $source['poids'], $candidat['distance']) ?> kg</div>
                                                    <div class="text-muted mt-1 small">
                                                        <?php if (empty($candidat['distance']) || (float)$candidat['distance'] <= 0): ?>
                                                            <span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i>Dist. inconnue</span>
                                                        <?php else: ?>
                                                            <i class="fas fa-route me-1"></i><?= number_format((float)$candidat['distance'], 1, ',', ' ') ?> km
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php 
                                                    $origC = $candidat['origine_geo'] ?? 'Inconnue';
                                                    if ($origC === 'Inconnue' && !empty($candidat['distance']) && (float)$candidat['distance'] > 0) {
                                                        $origC = \App\Helpers\ApiHelper::determinerOrigineGeo($candidat['distance'], $candidat['siege_adresse']);
                                                    }
                                                    $origColorC = 'bg-secondary';
                                                    if ($origC === 'Alliance Locale') $origColorC = 'bg-green'; elseif ($origC === 'Régionale') $origColorC = 'bg-yellow'; elseif ($origC === 'Nationale') $origColorC = 'bg-blue'; elseif ($origC === 'Internationale') $origColorC = 'bg-purple';
                                                    ?>
                                                    <?php if ($origC !== 'Inconnue'): ?><div class="mt-1"><span class="badge <?= $origColorC ?> text-white" style="font-size: 10px;"><i class="fas fa-globe me-1"></i> <?= $origC ?></span></div><?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-success btn-sm btn-pill w-100" onclick="validerCandidat(this, <?= $source['id'] ?>, <?= $candidat['id'] ?>)">
                                                        <i class="fas fa-check me-1"></i> Choisir
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

<?php if(!empty($flotteVehicules)): ?>
<?php foreach($flotteVehicules as $veh): ?>
<!-- Modal Edit Vehicle -->
<div class="modal modal-blur fade" id="modalEditVehicle<?= $veh['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=edit_vehicle" method="POST" class="no-loader">
                <input type="hidden" name="id" value="<?= $veh['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Éditer le véhicule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Société / Filiale</label>
                        <select name="societe_id" class="form-select" required>
                            <?php foreach ($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($veh['societe_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plaque d'immatriculation</label>
                        <input type="text" name="plaque" class="form-control" value="<?= htmlspecialchars($veh['plaque']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select" required>
                            <option value="Actif" <?= ($veh['statut'] === 'Actif') ? 'selected' : '' ?>>Actif (En service)</option>
                            <option value="Sorti" <?= ($veh['statut'] === 'Sorti') ? 'selected' : '' ?>>Sorti (Inactif / Vendu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modifier le Modèle (Optionnel)</label>
                        <select name="referentiel_id" class="form-select">
                            <option value="">-- Ne pas modifier -- (Actuel: <?= htmlspecialchars($veh['marque'].' '.$veh['modele']) ?>)</option>
                            <?php foreach ($referentielVehicules as $ref): ?>
                                <option value="<?= $ref['id'] ?>">
                                    <?= htmlspecialchars($ref['marque'] . ' ' . $ref['modele'] . ' (' . $ref['carburant'] . ' - ' . $ref['conso_moyenne'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-pill">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- SOUS-ONGLET INTROUVABLES -->
<div id="subtab-introuvables" class="subtab-content">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recherches sans résultats</h3></div>
        <?php if (empty($sourcesIntrouvables)): ?>
            <div class="card-body"><p class="text-muted mb-0">✅ Aucun enregistrement introuvable.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead><tr><th class="w-1">ID</th><th>Société</th><th>Nom recherché (CSV)</th><th class="w-1">Action possible</th></tr></thead>
                    <tbody>
                        <?php foreach ($sourcesIntrouvables as $introuvable): ?>
                            <tr id="row-<?= $introuvable['id'] ?>">
                                <td><span class="text-muted">#<?= htmlspecialchars($introuvable['id'] ?? '') ?></span></td>
                                <td><span class="badge bg-purple-lt"><i class="fas fa-building me-1"></i><?= htmlspecialchars($introuvable['societe_nom'] ?? '') ?></span></td>
                                <td><strong class="text-primary"><?= htmlspecialchars($introuvable['nom_recherche'] ?? '') ?></strong></td>
                                <td id="action-cell-<?= $introuvable['id'] ?>">
                                    <div class="btn-list flex-nowrap">
                                        <button class="btn btn-warning btn-sm btn-pill" onclick="gererIntrouvable(<?= $introuvable['id'] ?>, '<?= addslashes(htmlspecialchars($introuvable['nom_recherche'] ?? '')) ?>')">
                                            <i class="fas fa-edit me-1"></i> Gérer
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-icon btn-pill" onclick="supprimerCandidatAjax(this, <?= $introuvable['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

<?php if(!empty($flotteVehicules)): ?>
<?php foreach($flotteVehicules as $veh): ?>
<!-- Modal Edit Vehicle -->
<div class="modal modal-blur fade" id="modalEditVehicle<?= $veh['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=edit_vehicle" method="POST" class="no-loader">
                <input type="hidden" name="id" value="<?= $veh['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Éditer le véhicule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Société / Filiale</label>
                        <select name="societe_id" class="form-select" required>
                            <?php foreach ($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($veh['societe_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plaque d'immatriculation</label>
                        <input type="text" name="plaque" class="form-control" value="<?= htmlspecialchars($veh['plaque']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select" required>
                            <option value="Actif" <?= ($veh['statut'] === 'Actif') ? 'selected' : '' ?>>Actif (En service)</option>
                            <option value="Sorti" <?= ($veh['statut'] === 'Sorti') ? 'selected' : '' ?>>Sorti (Inactif / Vendu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modifier le Modèle (Optionnel)</label>
                        <select name="referentiel_id" class="form-select">
                            <option value="">-- Ne pas modifier -- (Actuel: <?= htmlspecialchars($veh['marque'].' '.$veh['modele']) ?>)</option>
                            <?php foreach ($referentielVehicules as $ref): ?>
                                <option value="<?= $ref['id'] ?>">
                                    <?= htmlspecialchars($ref['marque'] . ' ' . $ref['modele'] . ' (' . $ref['carburant'] . ' - ' . $ref['conso_moyenne'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-pill">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SOUS-ONGLET VALIDÉES -->
<div id="subtab-validated" class="subtab-content">
    <?php if (empty($societesValideesInventaire)): ?>
        <div class="alert alert-info" role="alert"><h4 class="alert-title">Inventaire vide</h4><div class="text-muted">Aucun fournisseur n'a encore été qualifié dans l'inventaire permanent.</div></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="input-icon" style="width: 300px;">
                <span class="input-icon-addon"><i class="fas fa-search"></i></span>
                <input type="text" id="filterValideesInput" onkeyup="filtrerValidees()" class="form-control" placeholder="Filtrer l'inventaire des sociétés...">
            </div>
            <div class="text-muted small">
                <span class="badge bg-green-lt fs-4 me-1"><?= count($societesValideesInventaire) ?></span> entreprise(s) qualifiée(s) dans l'inventaire
            </div>
        </div>
        
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped" style="table-layout: fixed; width: 100%;" id="table-validees">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Fournisseur Qualifié</th>
                            <th class="text-center" style="width: 15%;">Labels RSE</th>
                            <th class="text-center" style="width: 20%;">Activité / Dépenses</th>
                            <th class="text-center" style="width: 15%;">Empreinte / Géoloc</th>
                            <th class="text-center" style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($societesValideesInventaire as $valide): ?>
                            <?php 
                            $highlightClass = '';
                            if (!empty($valide['est_connu'])) {
                                $highlightClass = 'bg-green-lt';
                            } elseif (in_array($valide['activite_principale'], $nafPrivilegies ?? [])) {
                                $highlightClass = 'bg-purple-lt';
                            }
                            ?>
                            <tr class="<?= $highlightClass ?>" id="row-valide-<?= $valide['resultat_id'] ?>">
                                <td>
                                    <?php 
                                    $sJ = $valide['statut_juridique'] ?? 'Actif'; 
                                    $badgeColor = ($sJ === 'Fermée' || strpos($sJ, 'Liquidation') !== false) ? 'bg-red' : (($sJ !== 'Actif') ? 'bg-orange' : 'bg-green'); 
                                    ?>
                                    <div class="font-weight-medium d-flex align-items-center">
                                        <span class="badge <?= $badgeColor ?> text-white me-2 flex-shrink-0" style="width: 105px; text-align: center; cursor: pointer;" 
                                              onclick="afficherBodacc('<?= htmlspecialchars($valide['siren'] ?? '') ?>', '<?= addslashes(htmlspecialchars($valide['nom_complet'] ?? '')) ?>')">
                                            <?= htmlspecialchars($sJ) ?> <i class="fas fa-search ms-1"></i>
                                        </span>
                                        <span class="text-truncate fw-bold"><?= htmlspecialchars($valide['nom_complet'] ?? '') ?></span>
                                    </div>
                                    <?php if (empty($valide['distance']) || $valide['distance'] <= 0): ?>
                                        <span class="badge bg-red text-white mt-1" style="width: 115px; text-align: center; cursor: pointer;" onclick="lancerMajDistances()">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Anomalie GPS
                                        </span>
                                    <?php endif; ?>
                                    <div class="text-muted mt-1" style="font-size: 12px;">
                                        SIREN : <?= htmlspecialchars($valide['siren'] ?? 'N/A') ?><br>
                                        <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($valide['siege_adresse'] ?? 'Adresse non renseignée') ?>
                                    </div>
                                    <?php 
                                    $codeNafValide = $valide['activite_principale'] ?? '';
                                    $libelleNafValide = $valide['activite_principale_libelle'] ?? '';
                                    if (empty($libelleNafValide) && !empty($codeNafValide)) {
                                        $libelleNafValide = \App\Helpers\ApiHelper::getLibelleNafLocal($codeNafValide);
                                    }
                                    ?>
                                    <?php if (!empty($codeNafValide)): ?>
                                        <div class="mt-1 text-truncate" style="font-size: 12px;" title="<?= htmlspecialchars($codeNafValide . ' - ' . $libelleNafValide) ?>">
                                            <i class="fas fa-tag text-purple me-1"></i><strong><?= htmlspecialchars($codeNafValide) ?></strong><?= !empty($libelleNafValide) ? ' - ' . htmlspecialchars($libelleNafValide) : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($valide['est_alimentaire']): ?><span class="badge bg-yellow text-white mb-1" style="width: 90px;">Alimentaire</span><br><?php else: ?><span class="badge bg-secondary text-white mb-1" style="width: 90px;">NON Alim.</span><br><?php endif; ?>
                                    <?php if ($valide['est_ess']): ?><span class="badge bg-pink text-white mb-1" style="width: 90px;">🤝 ESS</span><br><?php endif; ?>
                                    <?php if ($valide['est_societe_mission']): ?><span class="badge bg-teal text-white" style="width: 90px;">🎯 Mission</span><?php endif; ?>
                                </td>
                                
                                <td class="text-center px-2">
                                    <span class="badge bg-blue-lt mb-1"><?= (int)$valide['nb_depenses'] ?> flux enregistré(s)</span>
                                    <div class="small fw-bold text-dark"><?= number_format((float)$valide['total_depenses'], 2, ',', ' ') ?> € HT</div>
                                    <?php if ((float)$valide['total_fret'] > 0): ?>
                                        <div class="small text-muted"><?= number_format((float)$valide['total_fret'], 1, ',', ' ') ?> kg fret</div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <div class="text-muted small">
                                        <?php if(empty($valide['distance']) || (float)$valide['distance'] <= 0): ?>
                                            <span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i>Dist. inconnue</span>
                                        <?php else: ?>
                                            <i class="fas fa-route me-1"></i><?= number_format((float)$valide['distance'], 1, ',', ' ') ?> km
                                        <?php endif; ?>
                                    </div>
                                    <?php 
                                    $orig = $valide['origine_geo'] ?? 'Inconnue';
                                    if ($orig === 'Inconnue' && !empty($valide['distance']) && (float)$valide['distance'] > 0) $orig = \App\Helpers\ApiHelper::determinerOrigineGeo($valide['distance'], $valide['siege_adresse']);
                                    $origColor = 'bg-secondary';
                                    if ($orig === 'Alliance Locale') $origColor = 'bg-green'; elseif ($orig === 'Régionale') $origColor = 'bg-yellow'; elseif ($orig === 'Nationale') $origColor = 'bg-blue'; elseif ($orig === 'Internationale') $origColor = 'bg-purple';
                                    ?>
                                    <?php if ($orig !== 'Inconnue'): ?><div class="mt-1"><span class="badge <?= $origColor ?> text-white" style="font-size: 10px;"><i class="fas fa-globe me-1"></i> <?= $orig ?></span></div><?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-green-lt d-block mb-2 w-100"><i class="fas fa-check me-1"></i> Qualifié</span>
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-pill" 
                                            onclick="confirmGenericDelete('?action=delete_fournisseur_inventaire&id=<?= $valide['resultat_id'] ?>', 'Retirer ce fournisseur de l\'inventaire permanent ?')" 
                                            title="Retirer de l'inventaire">
                                        <i class="fas fa-trash me-1"></i> Retirer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if(!empty($flotteVehicules)): ?>
<?php foreach($flotteVehicules as $veh): ?>
<!-- Modal Edit Vehicle -->
<div class="modal modal-blur fade" id="modalEditVehicle<?= $veh['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?action=edit_vehicle" method="POST" class="no-loader">
                <input type="hidden" name="id" value="<?= $veh['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Éditer le véhicule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Société / Filiale</label>
                        <select name="societe_id" class="form-select" required>
                            <?php foreach ($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($veh['societe_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plaque d'immatriculation</label>
                        <input type="text" name="plaque" class="form-control" value="<?= htmlspecialchars($veh['plaque']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select" required>
                            <option value="Actif" <?= ($veh['statut'] === 'Actif') ? 'selected' : '' ?>>Actif (En service)</option>
                            <option value="Sorti" <?= ($veh['statut'] === 'Sorti') ? 'selected' : '' ?>>Sorti (Inactif / Vendu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modifier le Modèle (Optionnel)</label>
                        <select name="referentiel_id" class="form-select">
                            <option value="">-- Ne pas modifier -- (Actuel: <?= htmlspecialchars($veh['marque'].' '.$veh['modele']) ?>)</option>
                            <?php foreach ($referentielVehicules as $ref): ?>
                                <option value="<?= $ref['id'] ?>">
                                    <?= htmlspecialchars($ref['marque'] . ' ' . $ref['modele'] . ' (' . $ref['carburant'] . ' - ' . $ref['conso_moyenne'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-pill">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- SOUS-ONGLET AJOUT MANUEL -->
<div id="subtab-manual" class="subtab-content">
    <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Procédure 1 : Recherche via API Open Data (File d'attente)</h3></div>
                <div class="card-body">
                    <form class="no-loader" onsubmit="soumettreRechercheApi(event, this)">
                        <input type="hidden" name="action" value="ajout_manuel">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label required">Nom de l'entité (Recherche via Open Data Gouv)</label>
                                <input type="text" name="nom_recherche" class="form-control" placeholder="Ex: EURL DUPONT..." required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i> Placer en Rapprochement</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    <div class="card">
                <div class="card-header"><h3 class="card-title">Procédure 2 : Saisie Manuelle Directe (Création Validée)</h3></div>
                <div class="card-body">
                    <form class="no-loader" onsubmit="soumettreFormulaireManuelAjax(event, this)">
                        <input type="hidden" name="action" value="ajout_manuel_direct">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Nom Complet</label>
                                    <input type="text" name="nom_complet" class="form-control" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SIREN</label>
                                        <input type="text" name="siren" class="form-control" pattern="[0-9]{9}" title="9 chiffres">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Code NAF</label>
                                        <input type="text" name="code_naf" class="form-control" pattern="[0-9]{4}[a-zA-Z]" title="4 chiffres et 1 lettre">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Activité Alimentaire</label>
                                        <select name="est_alimentaire" class="form-select">
                                            <option value="auto">Automatique (Déduit du NAF)</option>
                                            <option value="1">Oui</option>
                                            <option value="0">Non</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Statut Juridique</label>
                                        <select name="etat_administratif" class="form-select">
                                            <option value="A">Actif</option>
                                            <option value="D">En difficulté (Sauvegarde/Redressement)</option>
                                            <option value="C">Fermée / Radiée</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Adresse Complète (Géocodage automatique)</label>
                                    <textarea name="adresse" id="am_adresse" class="form-control" rows="2" placeholder="Saisissez l'adresse puis cliquez en dehors pour géocoder..."></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Latitude</label>
                                        <input type="text" name="latitude" id="am_lat" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Longitude</label>
                                        <input type="text" name="longitude" id="am_lon" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Distance (km)</label>
                                        <input type="text" name="distance" id="am_distance" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Recalage sur plan (Ajustez le marqueur si besoin)</label>
                                <div id="am_map" style="height: 380px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <div class="form-footer mt-4 text-end">
                            <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-2"></i> Ajouter et Valider l'entité</button>
                        </div>
                    </form>
                </div>
            </div>
</div>
</div> <!-- Fin subtab-scope3-inventaire -->

    <!-- 3. SOUS-ONGLET PRINCIPAL : GESTION DES CODES NAF -->
    <div id="subtab-scope3-naf" class="scope3-main-tab">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <div>
                    <h3 class="card-title m-0">
                        <i class="fas fa-tags text-indigo me-2"></i> Référentiel des Codes NAF Privilégiés
                        <span class="badge bg-purple-lt ms-2">Couleur Violette</span>
                    </h3>
                    <div class="text-muted small mt-1">
                        <span id="labelSelectedCountDash" class="fw-bold text-purple"><?= count($nafPrivilegies ?? []) ?></span> code(s) NAF actuellement privilégié(s) sur un total de <?= count($nafList ?? []) ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" onclick="toutCocherNafDash()">Tout cocher</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" onclick="toutDecocherNafDash()">Tout décocher</button>
                </div>
            </div>
            
            <!-- Filtres par secteurs -->
            <div class="card-body border-bottom py-2 bg-light">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-primary active btn-sector-dash" onclick="filterSectorDash('all', this)">Tous</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('alimentaire', this)">🍎 Alimentaire</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('transport', this)">🚛 Transport</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('energie', this)">⚡ Énergie</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('btp', this)">🏗️ BTP</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('numerique', this)">💻 Tech</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('industrie', this)">🏭 Industrie</button>
                        <button type="button" class="btn btn-outline-primary btn-sector-dash" onclick="filterSectorDash('conseil', this)">💼 Services</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success btn-pill" onclick="selectCurrentSectorDash()"><i class="fas fa-check-double me-1"></i> Cocher le secteur</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-pill" onclick="deselectCurrentSectorDash()"><i class="fas fa-times me-1"></i> Décocher</button>
                    </div>
                </div>
            </div>

            <!-- Barre de recherche NAF -->
            <div class="card-body border-bottom py-2">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchNafDash" class="form-control form-control-sm" placeholder="Rechercher par code (ex: 56.10A) ou libellé (ex: restaurant)..." onkeyup="applyFilterNafDash()">
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="?action=save_naf" class="no-loader">
                <input type="hidden" name="redirect_to" value="index.php">
                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-vcenter card-table table-hover table-striped table-sm" id="tableNafDash">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="w-1 text-center">Actif</th>
                                <th class="w-1">Secteur</th>
                                <th style="width: 120px;">Code NAF</th>
                                <th>Libellé Officiel de l'Activité</th>
                                <th class="text-center" style="width: 140px;">Ratio ADEME</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nafList as $n): ?>
                                <?php 
                                $sec = \App\Helpers\EcoHelper::getSectorFromNaf($n['code']);
                                $isPriv = in_array($n['code'], $nafPrivilegies ?? []);
                                $fact = $ademeFacteurs[$n['code']] ?? null;
                                ?>
                                <tr class="naf-row-dash" data-sector="<?= $sec['id'] ?>" data-code="<?= htmlspecialchars(strtolower($n['code'])) ?>" data-libelle="<?= htmlspecialchars(strtolower($n['libelle'])) ?>">
                                    <td class="text-center">
                                        <input class="form-check-input check-naf-dash" type="checkbox" name="naf_codes[]" value="<?= htmlspecialchars($n['code']) ?>" <?= $isPriv ? 'checked' : '' ?> onchange="updateSelectedCountDash()">
                                    </td>
                                    <td>
                                        <span class="badge <?= $sec['badge'] ?>"><i class="<?= $sec['icon'] ?> me-1"></i> <?= $sec['label'] ?></span>
                                    </td>
                                    <td>
                                        <strong class="font-monospace text-purple"><?= htmlspecialchars($n['code']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($n['libelle']) ?>
                                    </td>
                                    <td class="text-center font-monospace small">
                                        <?= $fact !== null ? number_format($fact, 4, ',', ' ') . ' kg/k€' : '<span class="text-muted">0,2000 (défaut)</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center bg-light">
                    <span class="text-muted small">Les codes NAF cochés sont automatiquement surlignés en violet dans vos listes.</span>
                    <button type="submit" class="btn btn-primary btn-pill"><i class="fas fa-save me-2"></i> Enregistrer les codes NAF privilégiés</button>
                </div>
            </form>
        </div>
    </div> <!-- Fin subtab-scope3-naf -->

    <!-- Modal : Ajouter un Achat / Flux Scope 3 -->
    <div class="modal modal-blur fade" id="modalAddAchatScope3" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="?action=add_achat_scope3" method="POST" class="no-loader">
                    <input type="hidden" name="redirect_to" value="index.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle text-primary me-2"></i> Ajouter une dépense / flux Scope 3</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Société / Filiale acheteuse</label>
                            <select name="societe_id" class="form-select" required>
                                <?php foreach ($societes as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($activeSocieteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Exercice comptable (Année)</label>
                                <input type="number" name="annee" id="add_achat_annee" class="form-control" value="<?= date('Y') ?>" min="2000" max="2050" required onchange="filtrerFournisseursDisponiblesAddModal()" oninput="filtrerFournisseursDisponiblesAddModal()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Montant Dépensé (€ HT)</label>
                                <input type="number" step="0.01" name="montant" class="form-control" placeholder="Ex: 1250.00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Fournisseur Qualifié (Inventaire)</label>
                            <select name="api_result_id" id="add_achat_api_result_id" class="form-select" required>
                                <option value="" disabled selected id="add_achat_default_option">-- Choisir le fournisseur dans l'inventaire --</option>
                                <?php foreach ($fournisseursQualifies as $fq): ?>
                                    <?php $anneesOccupees = $fournisseurAnneesOccupees[$fq['api_id']] ?? []; ?>
                                    <option value="<?= $fq['api_id'] ?>" data-annees="<?= implode(',', $anneesOccupees) ?>">
                                        <?= htmlspecialchars($fq['nom_complet']) ?> 
                                        <?= !empty($fq['ville']) ? '(' . htmlspecialchars($fq['ville']) . ')' : '' ?>
                                        <?= !empty($fq['siren']) ? ' - SIREN: ' . htmlspecialchars($fq['siren']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint" id="add_achat_hint">Seuls les fournisseurs sans dépense enregistrée pour cet exercice sont proposés.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tonnage Fret transporté (kg) <span class="text-muted small">(Optionnel)</span></label>
                            <input type="number" step="0.1" name="poids" class="form-control" placeholder="Ex: 250" value="0">
                            <small class="form-hint">Permet le calcul précis des tonnes-kilomètres (t.km).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-pill"><i class="fas fa-check me-1"></i> Enregistrer la dépense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal : Édition d'un Achat Scope 3 -->
    <div class="modal modal-blur fade" id="modalEditAchatScope3" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="?action=edit_achat_scope3" method="POST" class="no-loader">
                    <input type="hidden" name="source_id" id="edit_achat_id">
                    <input type="hidden" name="redirect_to" value="index.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit text-primary me-2"></i> Modifier la ligne d'achat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Fournisseur</label>
                            <div class="form-control-plaintext fw-bold text-primary" id="edit_achat_nom">-</div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Année</label>
                                <input type="number" name="annee" id="edit_achat_annee" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Montant (€ HT)</label>
                                <input type="number" step="0.01" name="montant" id="edit_achat_montant" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Poids (kg)</label>
                                <input type="number" step="0.1" name="poids" id="edit_achat_poids" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-pill"><i class="fas fa-save me-1"></i> Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal : Importer Dépenses par SIREN (CSV 3 colonnes) -->
    <div class="modal modal-blur fade" id="modalImportSirenCsv" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- ÉTAPE 1 : FORMULAIRE DE CHOIX DU FICHIER -->
                <div id="sirenImportSectionForm">
                    <form onsubmit="lancerImportSirenProgress(event, this)">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-file-import text-teal me-2"></i> Importer Dépenses par SIREN (CSV 3 colonnes)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="fas fa-info-circle me-1"></i> Ce traitement importe un CSV à 3 colonnes : <strong>SIREN</strong>, <strong>Montant (€ HT)</strong> et <strong>Tonnage</strong>. Les tiers sont automatiquement reconnus dans l'inventaire ou qualifiés via l'API Sirene de l'État avec suivi en direct.
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Société / Filiale acheteuse</label>
                                <select name="societe_id" class="form-select" required>
                                    <?php foreach ($societes as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($activeSocieteId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Exercice comptable (Année)</label>
                                    <input type="number" name="annee" id="import_siren_annee" class="form-control" value="<?= date('Y') ?>" min="2000" max="2050" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Unité du Tonnage</label>
                                    <select name="unite_tonnage" class="form-select" required>
                                        <option value="t" selected>Tonnes (T)</option>
                                        <option value="kg">Kilogrammes (kg)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">Fichier CSV (3 colonnes)</label>
                                <input type="file" name="csv_file_siren" class="form-control" accept=".csv,text/csv" required>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="has_header" value="1" id="import_siren_has_header" checked>
                                    <label class="form-check-label text-muted small" for="import_siren_has_header">
                                        Le fichier contient une ligne d'en-tête (ignorée lors de l'import)
                                    </label>
                                </div>
                            </div>

                            <div class="card bg-light p-2 mb-0">
                                <div class="text-muted small fw-bold mb-1"><i class="fas fa-table me-1"></i> Exemple de structure CSV attendue :</div>
                                <pre class="m-0 p-1 text-monospace small" style="background: #fff; border-radius: 3px; font-size: 11px;">SIREN;MONTANT_HT;TONNAGE
443061841;12500.50;3.5
751190315;8420.00;1.2</pre>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-teal btn-pill"><i class="fas fa-upload me-1"></i> Lancer l'importation</button>
                        </div>
                    </form>
                </div>

                <!-- ÉTAPE 2 : BARRE DE PROGRESSION EN DIRECT -->
                <div id="sirenImportSectionProgress" class="d-none p-4 text-center">
                    <div class="spinner-border text-teal mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h4 class="mb-2 text-teal fw-bold">Traitement et qualification en cours...</h4>
                    <p class="text-muted small mb-3">Recherche dans l'inventaire pérenne et enrichissement via l'API Sirene.</p>
                    
                    <div class="progress progress-lg mb-2 shadow-sm" style="height: 22px;">
                        <div id="sirenImportProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-teal fw-bold fs-5 text-white" style="width: 0%;">0%</div>
                    </div>

                    <div class="d-flex justify-content-between text-muted small mb-3 px-1">
                        <span id="sirenImportCount" class="fw-bold">0 / 0 flux traités</span>
                        <span id="sirenImportCurrentSiren" class="font-monospace text-primary text-truncate ms-2" style="max-width: 250px;">Initialisation...</span>
                    </div>

                    <div class="alert alert-light border py-2 text-muted small text-start">
                        <i class="fas fa-shield-alt text-teal me-1"></i> <strong>Traitement sécurisé :</strong> Les requêtes sont traitées séquentiellement pour assurer l'exactitude du géocodage et de l'annuaire. Veuillez ne pas fermer cette fenêtre.
                    </div>
                </div>

                <!-- ÉTAPE 3 : RAPPORT FINAL COMPLET -->
                <div id="sirenImportSectionReport" class="d-none p-4 text-center">
                    <div class="text-success mb-2" style="font-size: 3.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-success mb-1">Importation terminée avec succès !</h3>
                    <p class="text-muted small mb-4">L'ensemble des dépenses et tonnages a été intégré au livre de bord.</p>

                    <div class="row g-2 mb-4 text-center">
                        <div class="col-4">
                            <div class="card p-2 bg-blue-lt border-0">
                                <div class="fs-2 fw-bold text-blue" id="repSirenTotal">0</div>
                                <div class="text-muted small">Lignes importées</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 bg-green-lt border-0">
                                <div class="fs-2 fw-bold text-green" id="repSirenConnus">0</div>
                                <div class="text-muted small">Reconnus</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card p-2 bg-teal-lt border-0">
                                <div class="fs-2 fw-bold text-teal" id="repSirenQualifies">0</div>
                                <div class="text-muted small">Qualifiés API</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 bg-yellow-lt border-0">
                                <div class="fs-2 fw-bold text-yellow" id="repSirenCumules">0</div>
                                <div class="text-muted small">Cumulés sur existants</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 bg-secondary-lt border-0">
                                <div class="fs-2 fw-bold text-secondary" id="repSirenAttente">0</div>
                                <div class="text-muted small">En attente</div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-teal btn-pill px-4" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i> Actualiser le journal des dépenses
                    </button>
                </div>

                <!-- ÉTAPE 4 : ERREUR D'IMPORTATION -->
                <div id="sirenImportSectionError" class="d-none p-4 text-center">
                    <div class="text-danger mb-2" style="font-size: 3rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4 class="text-danger mb-2">Erreur lors de l'importation</h4>
                    <div class="alert alert-danger text-start py-2 small" id="sirenImportErrorMessage">
                        Une erreur est survenue lors du traitement du fichier CSV.
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-pill px-4" onclick="resetSirenImportModal()">
                        <i class="fas fa-redo me-1"></i> Réessayer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal : Confirmation Clôture / Réouverture Exercice -->
    <div class="modal modal-blur fade" id="modalConfirmCloture" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="?action=toggle_cloture_exercice" method="POST" class="no-loader">
                    <input type="hidden" name="annee" id="cloture_annee_input" value="">
                    <input type="hidden" name="statut" id="cloture_statut_input" value="">
                    <input type="hidden" name="redirect_to" value="index.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clotureModalTitle"><i class="fas fa-lock text-warning me-2"></i> Clôturer l'exercice</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="clotureModalBody">
                        <!-- Rempli par JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto btn-pill" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-pill" id="clotureModalBtnSubmit">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    var statutsExercices = <?= json_encode($statutsExercices ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function openScope3Tab(tabName, element) {
        if (window.event) window.event.preventDefault();
        document.querySelectorAll('.scope3-main-tab').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.scope3-main-nav-link').forEach(el => el.classList.remove('active'));
        var target = document.getElementById(tabName);
        if (target) target.classList.add('active');
        if (element) {
            element.classList.add('active');
        } else {
            var link = document.querySelector('.scope3-main-nav-link[href="#' + tabName + '"]');
            if (link) link.classList.add('active');
        }
        localStorage.setItem('activeScope3SubTab', tabName);
    }

    function mettreAJourBarreCloture(annee) {
        let curAnnee = annee || (document.getElementById('selectAnneeScope3') ? document.getElementById('selectAnneeScope3').value : 'all');
        let badgeStatut = document.getElementById('badgeStatutExercice');
        let btnCloture = document.getElementById('btnClotureExercice');
        let alertBanner = document.getElementById('alertExerciceCloture');
        let alertDetail = document.getElementById('alertExerciceClotureDetail');
        let btnAdd = document.getElementById('btnAddAchatScope3');
        let btnImportCsv = document.getElementById('btnImportExerciceCsv');
        let btnImportSiren = document.getElementById('btnImportSirenCsv');
        let btnBulkDelete = document.getElementById('btnDeleteSelectedAchats');
        let labelsAnnee = document.querySelectorAll('.annee-active-label');

        labelsAnnee.forEach(el => el.innerText = (curAnnee === 'all' ? 'Globale' : curAnnee));

        if (curAnnee === 'all') {
            if (badgeStatut) {
                badgeStatut.className = 'badge bg-secondary-lt';
                badgeStatut.innerHTML = '<i class="fas fa-layer-group me-1"></i> Vue consolidée (Toutes années)';
            }
            if (btnCloture) btnCloture.classList.add('d-none');
            if (alertBanner) alertBanner.classList.add('d-none');
            if (btnAdd) { btnAdd.disabled = false; btnAdd.classList.remove('disabled'); }
            if (btnImportCsv) { btnImportCsv.classList.remove('disabled'); }
            if (btnImportSiren) { btnImportSiren.disabled = false; btnImportSiren.classList.remove('disabled'); }
            return;
        }

        if (btnCloture) btnCloture.classList.remove('d-none');

        let exData = statutsExercices[curAnnee] || { annee: parseInt(curAnnee), statut: 'ouvert' };
        let isCloture = (exData.statut === 'cloture');

        if (isCloture) {
            if (badgeStatut) {
                badgeStatut.className = 'badge bg-danger text-white';
                badgeStatut.innerHTML = `<i class="fas fa-lock me-1"></i> Exercice ${curAnnee} : Clôturé`;
            }
            if (btnCloture) {
                btnCloture.className = 'btn btn-sm btn-outline-secondary btn-pill';
                btnCloture.innerHTML = `<i class="fas fa-lock-open me-1"></i> Rouvrir l'exercice ${curAnnee}`;
            }
            if (alertBanner) {
                alertBanner.classList.remove('d-none');
                if (alertDetail && exData.date_cloture) {
                    let d = new Date(exData.date_cloture);
                    let formattedDate = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    alertDetail.innerText = `Cet exercice a été clôturé le ${formattedDate}. Les flux comptables et émissions sont figés pour conformité et audit. Les ajouts, modifications et suppressions sont verrouillés.`;
                }
            }
            // Verrouiller les boutons d'action
            if (btnAdd) { btnAdd.disabled = true; btnAdd.classList.add('disabled'); }
            if (btnImportCsv) { btnImportCsv.classList.add('disabled'); }
            if (btnImportSiren) { btnImportSiren.disabled = true; btnImportSiren.classList.add('disabled'); }
            if (btnBulkDelete) { btnBulkDelete.classList.add('d-none'); }
        } else {
            if (badgeStatut) {
                badgeStatut.className = 'badge bg-success text-white';
                badgeStatut.innerHTML = `<i class="fas fa-lock-open me-1"></i> Exercice ${curAnnee} : Ouvert`;
            }
            if (btnCloture) {
                btnCloture.className = 'btn btn-sm btn-outline-warning btn-pill';
                btnCloture.innerHTML = `<i class="fas fa-lock me-1"></i> Clôturer l'exercice ${curAnnee}`;
            }
            if (alertBanner) alertBanner.classList.add('d-none');
            // Déverrouiller les boutons
            if (btnAdd) { btnAdd.disabled = false; btnAdd.classList.remove('disabled'); }
            if (btnImportCsv) { btnImportCsv.classList.remove('disabled'); }
            if (btnImportSiren) { btnImportSiren.disabled = false; btnImportSiren.classList.remove('disabled'); }
        }
    }

    function ouvrirModalCloture() {
        let curAnnee = document.getElementById('selectAnneeScope3') ? document.getElementById('selectAnneeScope3').value : 'all';
        if (curAnnee === 'all') {
            alert("Veuillez sélectionner un exercice spécifique dans la liste déroulante pour gérer sa clôture.");
            return;
        }

        let exData = statutsExercices[curAnnee] || { annee: parseInt(curAnnee), statut: 'ouvert' };
        let isCloture = (exData.statut === 'cloture');
        let titleEl = document.getElementById('clotureModalTitle');
        let bodyEl = document.getElementById('clotureModalBody');
        let submitBtn = document.getElementById('clotureModalBtnSubmit');
        let anneeInp = document.getElementById('cloture_annee_input');
        let statutInp = document.getElementById('cloture_statut_input');

        anneeInp.value = curAnnee;

        if (isCloture) {
            statutInp.value = 'ouvert';
            titleEl.innerHTML = `<i class="fas fa-lock-open text-primary me-2"></i> Rouvrir l'exercice ${curAnnee}`;
            bodyEl.innerHTML = `
                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i> <strong>Réouverture de l'exercice ${curAnnee} :</strong>
                    Vous allez réactiver la saisie manuelle, l'import CSV et la modification des dépenses pour cet exercice comptable.
                </div>
                <p class="text-muted small mb-0">Confirmez-vous la réouverture de l'exercice ${curAnnee} ?</p>
            `;
            submitBtn.className = 'btn btn-primary btn-pill';
            submitBtn.innerText = "Rouvrir l'exercice";
        } else {
            statutInp.value = 'cloture';
            titleEl.innerHTML = `<i class="fas fa-lock text-warning me-2"></i> Clôturer l'exercice ${curAnnee}`;
            bodyEl.innerHTML = `
                <div class="alert alert-warning py-2 mb-3">
                    <i class="fas fa-shield-alt me-1"></i> <strong>Clôture officielle de l'exercice ${curAnnee} :</strong>
                    Cette action fige définitivement les données de l'exercice ${curAnnee}. Les ajouts, imports, modifications et suppressions seront bloqués.
                </div>
                <p class="text-muted small mb-0">Confirmez-vous la clôture de l'exercice comptable ${curAnnee} ?</p>
            `;
            submitBtn.className = 'btn btn-warning btn-pill';
            submitBtn.innerText = "Clôturer l'exercice";
        }

        new bootstrap.Modal(document.getElementById('modalConfirmCloture')).show();
    }

    async function lancerImportSirenProgress(e, form) {
        e.preventDefault();

        let secForm = document.getElementById('sirenImportSectionForm');
        let secProgress = document.getElementById('sirenImportSectionProgress');
        let secReport = document.getElementById('sirenImportSectionReport');
        let secError = document.getElementById('sirenImportSectionError');
        let pBar = document.getElementById('sirenImportProgressBar');
        let pCount = document.getElementById('sirenImportCount');
        let pSiren = document.getElementById('sirenImportCurrentSiren');

        secForm.classList.add('d-none');
        secError.classList.add('d-none');
        secReport.classList.add('d-none');
        secProgress.classList.remove('d-none');

        pBar.style.width = '0%';
        pBar.innerText = '0%';
        pCount.innerText = 'Lecture du fichier...';
        pSiren.innerText = 'Analyse du CSV';

        let fd = new FormData(form);

        try {
            // Étape 1 : Téléversement et extraction des lignes
            let parseRes = await fetch('?action=import_siren_csv_parse', {
                method: 'POST',
                body: fd
            });
            let parseJson = await parseRes.json();

            if (!parseJson.success) {
                throw new Error(parseJson.message || "Erreur lors de l'analyse du fichier CSV.");
            }

            let items = parseJson.items || [];
            let total = items.length;
            let totalBrutes = parseJson.total_lignes_brutes || total;
            let annee = parseJson.annee;
            let societeId = parseJson.societe_id;

            if (total === 0) {
                throw new Error("Aucune ligne valide trouvée dans le fichier.");
            }

            let stats = { total: total, connus: 0, qualifies: 0, cumules: 0, attente: 0, erreurs: 0 };
            let done = 0;

            // Étape 2 : Traitement séquentiel de chaque fournisseur unique avec mise à jour en direct de la barre
            for (let item of items) {
                pSiren.innerText = 'SIREN ' + item.siren + ' (' + item.montant.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €)';

                let itemFd = new FormData();
                itemFd.append('siren', item.siren);
                itemFd.append('montant', item.montant);
                itemFd.append('poids', item.poids);
                itemFd.append('societe_id', societeId);
                itemFd.append('annee', annee);

                try {
                    let itemRes = await fetch('?action=import_siren_item_process', {
                        method: 'POST',
                        body: itemFd
                    });
                    let itemJson = await itemRes.json();

                    if (itemJson.success) {
                        if (itemJson.type === 'connu') stats.connus++;
                        else if (itemJson.type === 'nouveau_qualifie') stats.qualifies++;
                        else if (itemJson.type === 'cumule') stats.cumules++;
                        else stats.attente++;

                        if (itemJson.nom) {
                            pSiren.innerText = itemJson.nom;
                        }
                    } else {
                        stats.erreurs++;
                    }
                } catch (itemErr) {
                    console.error("Erreur sur SIREN " + item.siren, itemErr);
                    stats.erreurs++;
                }

                done++;
                let pct = Math.round((done / total) * 100);
                pBar.style.width = pct + '%';
                pBar.innerText = pct + '%';
                pCount.innerText = done + ' / ' + total + ' fournisseur(s)' + (totalBrutes > total ? ' (' + totalBrutes + ' factures regroupées)' : '');
            }

            // Étape 3 : Afficher le rapport final
            setTimeout(() => {
                secProgress.classList.add('d-none');
                secReport.classList.remove('d-none');

                document.getElementById('repSirenTotal').innerText = stats.total;
                document.getElementById('repSirenConnus').innerText = stats.connus;
                document.getElementById('repSirenQualifies').innerText = stats.qualifies;
                document.getElementById('repSirenCumules').innerText = stats.cumules;
                document.getElementById('repSirenAttente').innerText = stats.attente;

                let repSubtitle = document.querySelector('#sirenImportSectionReport p.text-muted');
                if (repSubtitle) {
                    repSubtitle.innerText = (totalBrutes > stats.total)
                        ? totalBrutes + ' écritures du fichier CSV ont été consolidées en ' + stats.total + ' fournisseur(s) unique(s) pour l\'exercice ' + annee + '.'
                        : stats.total + ' fournisseur(s) traité(s) pour l\'exercice ' + annee + '.';
                }
            }, 500);

        } catch (err) {
            console.error(err);
            secProgress.classList.add('d-none');
            secError.classList.remove('d-none');
            document.getElementById('sirenImportErrorMessage').innerText = err.message || "Erreur imprévue lors de l'importation.";
        }
    }

    function resetSirenImportModal() {
        let secForm = document.getElementById('sirenImportSectionForm');
        let secProgress = document.getElementById('sirenImportSectionProgress');
        let secReport = document.getElementById('sirenImportSectionReport');
        let secError = document.getElementById('sirenImportSectionError');
        if (secForm) secForm.classList.remove('d-none');
        if (secProgress) secProgress.classList.add('d-none');
        if (secReport) secReport.classList.add('d-none');
        if (secError) secError.classList.add('d-none');
    }

    function filtrerAnneeScope3(annee) {
        let rows = document.querySelectorAll('.row-achat-scope3');
        let totalMontant = 0;
        let totalPoids = 0;
        let totalCo2 = 0;
        let totalLocal = 0;
        let countVisible = 0;

        rows.forEach(r => {
            let rAnnee = r.getAttribute('data-annee');
            if (annee === 'all' || rAnnee === annee) {
                r.style.display = '';
                countVisible++;
                totalMontant += parseFloat(r.getAttribute('data-montant') || 0);
                totalPoids += parseFloat(r.getAttribute('data-poids') || 0);
                totalCo2 += parseFloat(r.getAttribute('data-co2') || 0);
                if (r.getAttribute('data-local') === '1') totalLocal++;
            } else {
                r.style.display = 'none';
            }
        });

        let emptyRow = document.getElementById('rowEmptyAchats');
        if (emptyRow) {
            emptyRow.style.display = (countVisible === 0) ? '' : 'none';
        }

        let elMontant = document.getElementById('kpi-scope3-montant');
        if (elMontant) elMontant.innerText = totalMontant.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

        let elPoids = document.getElementById('kpi-scope3-poids');
        if (elPoids) elPoids.innerText = (totalPoids / 1000).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' T';

        let elCo2 = document.getElementById('kpi-scope3-co2');
        if (elCo2) elCo2.innerText = (totalCo2 / 1000).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' tCO₂e';

        let elLocal = document.getElementById('kpi-scope3-local');
        if (elLocal) {
            let taux = countVisible > 0 ? ((totalLocal / countVisible) * 100).toFixed(1) : 0;
            elLocal.innerText = taux + ' %';
        }

        let elCount = document.getElementById('kpi-scope3-count');
        if (elCount) elCount.innerText = countVisible + ' flux enregistrés';

        // Mettre à jour la deuxième barre d'outils et le statut de clôture
        mettreAJourBarreCloture(annee);

        // Réinitialiser la sélection lors du changement d'année
        let masterCb = document.getElementById('checkAllAchatsScope3');
        if (masterCb) masterCb.checked = false;
        rows.forEach(r => {
            if (r.style.display === 'none') {
                let cb = r.querySelector('.check-achat-scope3');
                if (cb) cb.checked = false;
            }
        });
        updateSelectedAchatsScope3();
    }

    function toggleSelectAllAchats(masterCb) {
        let checked = masterCb.checked;
        let rows = document.querySelectorAll('.row-achat-scope3');
        rows.forEach(r => {
            if (r.style.display !== 'none') {
                let cb = r.querySelector('.check-achat-scope3');
                if (cb && !cb.disabled) cb.checked = checked;
            }
        });
        updateSelectedAchatsScope3();
    }

    function updateSelectedAchatsScope3() {
        let checkedBoxes = document.querySelectorAll('.check-achat-scope3:checked');
        let count = checkedBoxes.length;
        let btn = document.getElementById('btnDeleteSelectedAchats');
        let countEl = document.getElementById('countSelectedAchats');
        if (countEl) countEl.innerText = count;
        if (btn) {
            if (count > 0) {
                btn.classList.remove('d-none');
            } else {
                btn.classList.add('d-none');
            }
        }
    }

    function confirmBulkDeleteAchats() {
        let checkedBoxes = document.querySelectorAll('.check-achat-scope3:checked');
        let count = checkedBoxes.length;
        if (count === 0) return;

        let msg = `Voulez-vous vraiment supprimer définitivement ces ${count} dépense(s) sélectionnée(s) du livre de bord ?`;
        document.getElementById('genericDeleteMessage').innerText = msg;
        let btn = document.getElementById('genericDeleteBtn');
        btn.onclick = function(e) {
            e.preventDefault();
            let form = document.getElementById('formBulkDeleteAchats');
            form.innerHTML = '<input type="hidden" name="redirect_to" value="index.php">';
            checkedBoxes.forEach(cb => {
                let inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = cb.value;
                form.appendChild(inp);
            });
            form.submit();
        };
        var myModal = new bootstrap.Modal(document.getElementById('modalGenericDelete'));
        myModal.show();
    }

    function editAchatScope3(id, nom, annee, montant, poids) {
        document.getElementById('edit_achat_id').value = id;
        document.getElementById('edit_achat_nom').innerText = nom;
        document.getElementById('edit_achat_annee').value = annee;
        document.getElementById('edit_achat_montant').value = montant;
        document.getElementById('edit_achat_poids').value = poids;
        new bootstrap.Modal(document.getElementById('modalEditAchatScope3')).show();
    }

    // Fonctions gestion Codes NAF intégrée
    var currentSectorDash = 'all';
    function filterSectorDash(sector, btn) {
        currentSectorDash = sector;
        document.querySelectorAll('.btn-sector-dash').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyFilterNafDash();
    }

    function applyFilterNafDash() {
        let q = (document.getElementById('searchNafDash').value || '').toLowerCase().trim();
        let rows = document.querySelectorAll('.naf-row-dash');
        rows.forEach(r => {
            let sec = r.getAttribute('data-sector');
            let code = r.getAttribute('data-code') || '';
            let libelle = r.getAttribute('data-libelle') || '';

            let matchSec = (currentSectorDash === 'all' || sec === currentSectorDash);
            let matchQ = (!q || code.includes(q) || libelle.includes(q));

            if (matchSec && matchQ) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    function toutCocherNafDash() {
        document.querySelectorAll('.check-naf-dash').forEach(cb => cb.checked = true);
        updateSelectedCountDash();
    }

    function toutDecocherNafDash() {
        document.querySelectorAll('.check-naf-dash').forEach(cb => cb.checked = false);
        updateSelectedCountDash();
    }

    function selectCurrentSectorDash() {
        document.querySelectorAll('.naf-row-dash').forEach(r => {
            if (currentSectorDash === 'all' || r.getAttribute('data-sector') === currentSectorDash) {
                let cb = r.querySelector('.check-naf-dash');
                if (cb) cb.checked = true;
            }
        });
        updateSelectedCountDash();
    }

    function deselectCurrentSectorDash() {
        document.querySelectorAll('.naf-row-dash').forEach(r => {
            if (currentSectorDash === 'all' || r.getAttribute('data-sector') === currentSectorDash) {
                let cb = r.querySelector('.check-naf-dash');
                if (cb) cb.checked = false;
            }
        });
        updateSelectedCountDash();
    }

    function updateSelectedCountDash() {
        let count = document.querySelectorAll('.check-naf-dash:checked').length;
        let el = document.getElementById('labelSelectedCountDash');
        if (el) el.innerText = count;
    }

    function filtrerFournisseursDisponiblesAddModal() {
        let anneeInput = document.getElementById('add_achat_annee');
        let selectFournisseur = document.getElementById('add_achat_api_result_id');
        if (!anneeInput || !selectFournisseur) return;
        
        let annee = String(anneeInput.value).trim();
        let options = selectFournisseur.querySelectorAll('option[data-annees]');
        let disponibles = 0;
        let total = options.length;

        options.forEach(opt => {
            let rawAnnees = opt.getAttribute('data-annees') || '';
            let annees = rawAnnees.split(',').map(s => s.trim()).filter(s => s !== '');
            if (annees.includes(annee)) {
                opt.style.display = 'none';
                opt.disabled = true;
                if (opt.selected) {
                    selectFournisseur.value = '';
                }
            } else {
                opt.style.display = '';
                opt.disabled = false;
                disponibles++;
            }
        });

        let defaultOpt = document.getElementById('add_achat_default_option');
        if (defaultOpt) {
            if (disponibles === 0) {
                defaultOpt.innerText = `-- Aucun fournisseur disponible pour l'exercice ${annee} --`;
            } else {
                defaultOpt.innerText = `-- Choisir le fournisseur (${disponibles} disponible(s)) --`;
            }
        }

        let hint = document.getElementById('add_achat_hint');
        if (hint) {
            if (disponibles === 0) {
                hint.innerHTML = `<span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Tous les fournisseurs qualifiés ont déjà une dépense enregistrée pour l'exercice ${annee}.</span>`;
            } else {
                hint.innerHTML = `<span class="badge bg-green-lt me-1"><i class="fas fa-check me-1"></i> ${disponibles} fournisseur(s) disponible(s)</span> sans enregistrement pour l'exercice <strong>${annee}</strong> (sur ${total} qualifiés).`;
            }
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        let savedScope3Sub = localStorage.getItem('activeScope3SubTab');
        if (savedScope3Sub && document.getElementById(savedScope3Sub)) {
            let link = document.querySelector('a[href="#' + savedScope3Sub + '"]');
            openScope3Tab(savedScope3Sub, link);
        }

        let selectAnnee = document.getElementById('selectAnneeScope3');
        let initialAnnee = selectAnnee ? selectAnnee.value : 'all';
        filtrerAnneeScope3(initialAnnee);

        let modalAdd = document.getElementById('modalAddAchatScope3');
        if (modalAdd) {
            modalAdd.addEventListener('show.bs.modal', function () {
                let curFilter = document.getElementById('selectAnneeScope3') ? document.getElementById('selectAnneeScope3').value : '';
                let anneeInput = document.getElementById('add_achat_annee');
                if (curFilter && curFilter !== 'all' && anneeInput) {
                    anneeInput.value = curFilter;
                }
                filtrerFournisseursDisponiblesAddModal();
            });
        }

        let modalImportSiren = document.getElementById('modalImportSirenCsv');
        if (modalImportSiren) {
            modalImportSiren.addEventListener('show.bs.modal', function () {
                let curFilter = document.getElementById('selectAnneeScope3') ? document.getElementById('selectAnneeScope3').value : '';
                let anneeInput = document.getElementById('import_siren_annee');
                if (curFilter && curFilter !== 'all' && anneeInput) {
                    anneeInput.value = curFilter;
                }
            });
            modalImportSiren.addEventListener('hidden.bs.modal', resetSirenImportModal);
        }
    });
    </script>
</div> <!-- End tab-scope3 -->

<!-- ONGLET COMPRENDRE LE BILAN CARBONE -->
<div id="tab-comprendre-carbone" class="tab-content">
    <iframe id="carbone-frame" src="docs/bilan_carbone_guide.html?v=<?= time() ?>" style="width: 100%; height: 85vh; border: none; border-radius: 4px;"></iframe>
</div>

<!-- ONGLET 6 : DOCUMENTATION -->
<div id="tab-doc" class="tab-content">
    <iframe id="doc-frame" src="docs/doc.html?v=<?= time() ?>" style="width: 100%; height: 85vh; border: none; border-radius: 4px;"></iframe>
</div>

<!-- ONGLET CHANGELOG -->
<div id="tab-changelog" class="tab-content">
    <iframe id="changelog-frame" src="docs/changelog.html?v=<?= time() ?>" style="width: 100%; height: 85vh; border: none; border-radius: 4px;"></iframe>
</div>

<!-- MODALES APPLICATIVES -->
<div class="modal modal-blur fade" id="modalSuccessInfo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle text-success mb-2" style="font-size: 3rem;"></i>
                <h3>Succès</h3>
                <div class="text-muted" id="modalSuccessInfoMessage">Opération réussie.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><button type="button" class="btn btn-success w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Actualiser</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal modal-blur fade" id="modalBodacc" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark-lt">
                <h5 class="modal-title" id="bodacc-title">Annonces légales BODACC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="bodacc-content"></div>
            <div class="modal-footer"><button type="button" class="btn me-auto" data-bs-dismiss="modal">Fermer</button></div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modalConfirmDelete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 3rem;"></i>
                <h3>Êtes-vous sûr ?</h3>
                <div class="text-muted">Voulez-vous vraiment supprimer cet enregistrement ? Cette action est immédiate et irréversible.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Annuler</a></div>
                        <div class="col"><button type="button" class="btn btn-danger w-100" onclick="executerSuppressionAjax()">Supprimer</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modalGererIntrouvable" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-lt">
                <h5 class="modal-title text-warning"><i class="fas fa-edit me-2"></i> Gérer l'entité introuvable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="formGererIntrouvable" class="no-loader" onsubmit="executerGererIntrouvableAjax(event)">
                <div class="modal-body">
                    <input type="hidden" id="gerer_introuvable_id">
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div><i class="fas fa-info-circle icon alert-icon"></i></div>
                            <div>Corrigez le nom ou saisissez un numéro SIREN. L'entité sera replacée instantanément dans l'onglet <strong>Rapprochement</strong>.</div>
                        </div>
                    </div>
                    <div class="mb-3" id="gerer_input_group">
                        <label class="form-label required">Nom à rechercher (ou SIREN)</label>
                        <input type="text" id="gerer_introuvable_nom" class="form-control" required>
                        <div class="mt-2 d-flex justify-content-between">
                            <span class="text-muted small align-self-center">Aide à l'investigation :</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.open('https://annuaire-entreprises.data.gouv.fr/recherche?terme=' + encodeURIComponent(document.getElementById('gerer_introuvable_nom').value), '_blank')"><i class="fas fa-search me-1"></i> Annuaire fr</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.open('https://www.google.com/search?q=siren+' + encodeURIComponent(document.getElementById('gerer_introuvable_nom').value), '_blank')"><i class="fab fa-google me-1"></i> Google</button>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <label class="form-label">Plan B : Pas de SIREN français ?</label>
                        <button type="button" class="btn btn-outline-primary w-100" onclick="ouvrirSaisieEtrangere()">
                            <i class="fas fa-globe-europe me-2"></i> Fournisseur étranger (Forcer Saisie Manuelle)
                        </button>
                    </div>
                    <div id="gerer_feedback" style="display:none;" class="mt-3 text-center"></div>
                    <div id="etranger_form" style="display:none;" class="mt-3 border-top pt-3 text-start">
                        <div class="mb-2">
                            <label class="form-label text-primary">Adresse ou Ville (Pour le calcul CO2)</label>
                            <input type="text" id="etranger_adresse" class="form-control" placeholder="ex: Berlin, Germany">
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="validerEtrangerAjax()">
                            <i class="fas fa-check-circle me-1"></i> Valider définitivement
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btn-submit-gerer" class="btn btn-warning"><i class="fas fa-sync-alt me-2"></i> Relancer l'analyse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS D'ANIMATION, CHARTS ET AJAX -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Chart Top 10 Secteurs
    new Chart(document.getElementById('chartSecteurs'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($stats['top10Labels']) ?>,
            datasets: [{
                label: 'Volume d\'achats (€)',
                data: <?= json_encode($stats['top10Values']) ?>,
                backgroundColor: '#206bc4',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { callback: function(v) { return v.toLocaleString() + ' €'; } } } }
        }
    });

    // 2. Chart Alimentaire
    new Chart(document.getElementById('chartAlim'), { 
        type: 'doughnut', 
        data: { 
            labels: ['Alimentaire', 'Non Alimentaire'], 
            datasets: [{ data: [<?= $stats['alimCount'] ?>, <?= $stats['nonAlimCount'] ?>], backgroundColor: ['#f39c12', '#3498db'] }] 
        } 
    });

    // 3. Chart Géographique
    new Chart(document.getElementById('chartGeo'), { 
        type: 'bar', 
        data: { 
            labels: ['Alliance Locale', 'Régionale', 'Nationale', 'Internationale'], 
            datasets: [{ 
                label: 'Fournisseurs', 
                data: [<?= $stats['geoCounts']['Alliance Locale'] ?>, <?= $stats['geoCounts']['Régionale'] ?>, <?= $stats['geoCounts']['Nationale'] ?>, <?= $stats['geoCounts']['Internationale'] ?>], 
                backgroundColor: ['#2ecc71', '#f39c12', '#3498db', '#9b59b6'] 
            }] 
        } 
    });

    // Définition de l'origine
    var originLat = <?= $activeSocieteLat ?? \App\Config\Database::getEnv('ORIGIN_LAT', 45.191655) ?>;
    var originLon = <?= $activeSocieteLon ?? \App\Config\Database::getEnv('ORIGIN_LON', 0.762627) ?>;
    var map = L.map('fournisseursMap').setView([originLat, originLon], 5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    L.marker([originLat, originLon]).addTo(map).bindPopup("<b>Point de Départ</b>");

    var markersData = <?= json_encode($stats['mapMarkers']) ?>;
    markersData.forEach(function(m) {
        var markerColor = m.alim ? '#f39c12' : '#3498db';
        var typeLibelle = m.alim ? 'Alimentaire' : 'Non Alimentaire';
        L.circleMarker([m.lat, m.lon], { 
            radius: 7, color: markerColor, fillColor: markerColor, fillOpacity: 0.8, weight: 2 
        }).addTo(map).bindPopup("<b>" + m.nom + "</b><br><span class='badge bg-secondary text-white mt-1'>" + typeLibelle + "</span><br><small>Impact : " + m.co2 + " kg CO2</small>");
    });
});

// FONCTION DE NETTOYAGE OVERLAY
function masquerOverlayBlanc() {
    let loader = document.getElementById('page-loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';
        }, 400);
    }
}

// FILTRES DE RECHERCHE
function filtrerRapprochement() {
    let input = document.getElementById('filterMatchingInput');
    if (!input) return;
    let filter = input.value.toLowerCase();
    let cards = document.querySelectorAll('#list-matching .card-matching');
    cards.forEach(card => {
        let textContent = card.textContent || card.innerText;
        card.style.display = (textContent.toLowerCase().indexOf(filter) > -1) ? "" : "none";
    });
}

function filtrerValidees() {
    let input = document.getElementById('filterValideesInput');
    if (!input) return;
    let filter = input.value.toLowerCase();
    let table = document.getElementById('table-validees');
    if (!table) return;
    let trs = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (let i = 0; i < trs.length; i++) {
        let textContent = trs[i].textContent || trs[i].innerText;
        trs[i].style.display = (textContent.toLowerCase().indexOf(filter) > -1) ? "" : "none";
    }
}

function updateDonneesSource(sourceId) {
    let montant = document.getElementById('montant-' + sourceId).value;
    let poids = document.getElementById('poids-' + sourceId).value;
    
    let formData = new FormData();
    formData.append('action', 'enrichir_donnees');
    formData.append('source_id', sourceId);
    formData.append('montant', montant);
    formData.append('poids', poids);
    
    fetch('index.php', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'} })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour recalculer instantanément le CO2 affiché
            window.location.reload();
        } else {
            alert("Erreur lors de l'enregistrement des données.");
        }
    })
    .catch(() => alert('Erreur réseau lors de la mise à jour des données.'));
}

// ACTIONS AJAX
function validerCandidat(btnElement, sourceId, resultatId) {
    if (!btnElement) return;
    btnElement.disabled = true;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validation...';

    let formData = new FormData();
    formData.append('action', 'valider');
    formData.append('source_id', sourceId);
    formData.append('resultat_id', resultatId);

    fetch('index.php', { method: 'POST', body: formData })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau');
        return response.text();
    })
    .then(() => {
        let card = document.getElementById('card-' + sourceId);
        if (card) {
            card.style.transition = "all 0.3s ease-out";
            card.style.opacity = "0";
            card.style.transform = "scale(0.98)";
            setTimeout(() => {
                card.remove();
                let remainingCards = document.querySelectorAll('#list-matching .card-matching');
                if (remainingCards.length === 0) {
                    let container = document.getElementById('tab-matching');
                    if (container) {
                        container.innerHTML = `<div class="alert alert-success" role="alert"><h4 class="alert-title">Bravo !</h4><div class="text-muted">Plus aucune entreprise n'est en attente de validation.</div></div>`;
                    }
                }
            }, 300);
        }
        let badgeAttente = document.querySelector('#nav-tab-matching .badge');
        if (badgeAttente) badgeAttente.innerText = Math.max(0, parseInt(badgeAttente.innerText || '0') - 1);
        
        let badgeValidees = document.querySelector('#nav-tab-rapprochees .badge');
        if (badgeValidees) badgeValidees.innerText = parseInt(badgeValidees.innerText || '0') + 1;
    })
    .catch(error => {
        console.error('Erreur lors du rapprochement :', error);
        alert('Une erreur est survenue lors de la validation.');
        btnElement.disabled = false;
        btnElement.innerHTML = '<i class="fas fa-check me-1"></i> Choisir';
    })
    .finally(() => { masquerOverlayBlanc(); });
}

let deleteTargetId = null;
let deleteTargetBtn = null;

function supprimerCandidatAjax(btnElement, sourceId) {
    deleteTargetId = sourceId;
    deleteTargetBtn = btnElement;
    let modalEl = document.getElementById('modalConfirmDelete');
    let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function executerSuppressionAjax() {
    if (!deleteTargetId || !deleteTargetBtn) return;
    let modalEl = document.getElementById('modalConfirmDelete');
    let modalInstance = bootstrap.Modal.getInstance(modalEl);
    if(modalInstance) modalInstance.hide();

    let btnElement = deleteTargetBtn;
    let sourceId = deleteTargetId;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btnElement.disabled = true;

    let formData = new FormData();
    formData.append('action', 'delete_record');
    formData.append('id', sourceId);

    fetch('index.php', { method: 'POST', body: formData })
    .then(() => {
        let card = document.getElementById('card-' + sourceId) || document.getElementById('row-' + sourceId);
        if (card) {
            card.style.transition = "all 0.4s ease-out";
            card.style.opacity = "0";
            setTimeout(() => {
                card.style.height = "0px"; card.style.margin = "0px"; card.style.padding = "0px"; card.style.overflow = "hidden";
                setTimeout(() => card.remove(), 400);
            }, 400);
        }
    })
    .finally(() => { masquerOverlayBlanc(); });
}

function gererIntrouvable(id, nomActuel) {
    document.getElementById('gerer_introuvable_id').value = id;
    document.getElementById('gerer_introuvable_nom').value = nomActuel;
    document.getElementById('gerer_input_group').style.display = 'block';
    document.getElementById('gerer_feedback').style.display = 'none';
    let btn = document.getElementById('btn-submit-gerer');
    btn.style.display = 'inline-block';
    btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Relancer l\'analyse';
    btn.disabled = false;
    
    let cancelBtn = document.querySelector('#modalGererIntrouvable .btn.me-auto');
    cancelBtn.innerText = 'Annuler';
    cancelBtn.className = 'btn me-auto';
    cancelBtn.removeAttribute('onclick');
    let modalEl = document.getElementById('modalGererIntrouvable');
    let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function executerGererIntrouvableAjax(event) {
    event.preventDefault(); 
    let id = document.getElementById('gerer_introuvable_id').value;
    let nom = document.getElementById('gerer_introuvable_nom').value;
    let btn = document.getElementById('btn-submit-gerer');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Recherche...';
    btn.disabled = true;

    let formData = new FormData();
    formData.append('action', 'ajax_search');
    formData.append('source_id', id);
    formData.append('nouveau_nom', nom);
    formData.append('api_source', 'gouv'); // Recherche par défaut sur l'API Gouv

    fetch('index.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        let feedback = document.getElementById('gerer_feedback');
        if(data.success) {
            document.getElementById('gerer_input_group').style.display = 'none';
            btn.style.display = 'none';
            feedback.style.display = 'block';

            if (data.statut === 'valide_auto') {
                feedback.innerHTML = '<div class="alert alert-success m-0"><i class="fas fa-check-circle me-2"></i> L\'entreprise a été trouvée et validée automatiquement !</div>';
            } else if (data.statut === 'en_attente') {
                feedback.innerHTML = '<div class="alert alert-info m-0"><i class="fas fa-search me-2"></i> Plusieurs candidats trouvés. L\'entité est en attente de rapprochement.</div>';
            } else {
                feedback.innerHTML = '<div class="alert alert-danger m-0"><i class="fas fa-times-circle me-2"></i> Toujours aucun résultat trouvé avec cette recherche.</div>';
            }
            
            let cancelBtn = document.querySelector('#modalGererIntrouvable .btn.me-auto');
            cancelBtn.innerText = 'Fermer & Actualiser';
            cancelBtn.classList.remove('btn-light'); // or other if used
            cancelBtn.classList.add('btn-primary');
            cancelBtn.setAttribute('onclick', 'location.reload()');
            
        } else {
            feedback.style.display = 'block';
            feedback.innerHTML = '<div class="alert alert-danger m-0">Erreur : ' + (data.message || 'Impossible d\'analyser l\'entité.') + '</div>';
            btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Relancer l\'analyse';
            btn.disabled = false;
        }
    })
    .catch(error => {
        let feedback = document.getElementById('gerer_feedback');
        feedback.style.display = 'block';
        feedback.innerHTML = '<div class="alert alert-danger m-0">Erreur réseau : ' + error.message + '</div>';
        btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Relancer l\'analyse';
    });
}

// --------------------------------------------------------
// AJOUT MANUEL DIRECT - LOGIQUE CARTE & GÉOCODAGE
// --------------------------------------------------------
let amMap = null;
let amMarker = null;
let originLat = <?= $activeSocieteLat ?? \App\Config\Database::getEnv('ORIGIN_LAT', 45.191655) ?>;
let originLon = <?= $activeSocieteLon ?? \App\Config\Database::getEnv('ORIGIN_LON', 0.762627) ?>;

function initAmMap() {
    if (amMap) return;
    let amMapDiv = document.getElementById('am_map');
    if (!amMapDiv) return;
    
    amMap = L.map('am_map').setView([originLat, originLon], 5);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(amMap);
    
    amMarker = L.marker([originLat, originLon], { draggable: true }).addTo(amMap);
    
    amMarker.on('dragend', function (e) {
        let latlng = amMarker.getLatLng();
        document.getElementById('am_lat').value = latlng.lat.toFixed(6);
        document.getElementById('am_lon').value = latlng.lng.toFixed(6);
        calculateAmDistance(latlng.lat, latlng.lng);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du champ adresse pour géocodage à la volée
    let addrInput = document.getElementById('am_adresse');
    if (addrInput) {
        addrInput.addEventListener('blur', function() {
            let adresse = this.value.trim();
            if (adresse.length > 5) {
                // Désactiver le champ pour montrer le chargement
                this.style.opacity = '0.5';
                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(adresse))
                .then(res => res.json())
                .then(data => {
                    this.style.opacity = '1';
                    if (data && data.length > 0) {
                        let lat = parseFloat(data[0].lat);
                        let lon = parseFloat(data[0].lon);
                        document.getElementById('am_lat').value = lat.toFixed(6);
                        document.getElementById('am_lon').value = lon.toFixed(6);
                        
                        initAmMap();
                        amMap.setView([lat, lon], 14);
                        amMarker.setLatLng([lat, lon]);
                        
                        calculateAmDistance(lat, lon);
                    }
                }).catch(e => {
                    this.style.opacity = '1';
                });
            }
        });
    }

    // Gérer le rafraîchissement de la carte si on bascule sur l'onglet Ajout Manuel
    // On écoute les clics sur les tabs (spécifique à la façon dont tabler/ce projet fonctionne)
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if(this.getAttribute('onclick') && this.getAttribute('onclick').includes('tab-ajout')) {
                setTimeout(() => {
                    initAmMap();
                    if(amMap) amMap.invalidateSize();
                }, 200);
            }
        });
    });
    
    // Si l'onglet actif au chargement est "tab-ajout", initialiser la carte
    if (localStorage.getItem('activeTabEcotrace') === 'tab-ajout') {
        setTimeout(() => {
            initAmMap();
            if(amMap) amMap.invalidateSize();
        }, 500);
    }
});

function calculateAmDistance(lat, lon) {
    let distUrl = `http://router.project-osrm.org/route/v1/driving/${originLon},${originLat};${lon},${lat}?overview=false`;
    fetch(distUrl)
    .then(res => res.json())
    .then(data => {
        if (data && data.routes && data.routes.length > 0) {
            let distKm = (data.routes[0].distance / 1000).toFixed(2);
            document.getElementById('am_distance').value = distKm;
        } else {
            document.getElementById('am_distance').value = '';
        }
    })
    .catch(e => {
        console.error("OSRM Error:", e);
        document.getElementById('am_distance').value = '';
    });
}

function soumettreFormulaireManuelAjax(event, formElement) {
    event.preventDefault();
    let btn = formElement.querySelector('button[type="submit"]');
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Traitement...';
    btn.disabled = true;

    let formData = new FormData(formElement);

    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        
        if (data.success) {
            document.getElementById('modalSuccessInfoMessage').innerText = data.message || 'Opération réussie avec succès !';
            let modalEl = document.getElementById('modalSuccessInfo');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            alert("Erreur : " + (data.message || "Une erreur est survenue lors de l'ajout."));
        }
    })
    .catch(error => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert("Erreur réseau : " + error.message);
    });
}

function soumettreRechercheApi(event, formElement) {
    event.preventDefault();
    let btn = formElement.querySelector('button[type="submit"]');
    let nomRecherche = formElement.querySelector('input[name="nom_recherche"]').value;
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Création...';
    btn.disabled = true;

    let formData = new FormData(formElement);

    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        
        if (data.success && data.source_id) {
            // Ouvrir la modale "Gérer" et lancer la recherche
            document.getElementById('gerer_introuvable_id').value = data.source_id;
            document.getElementById('gerer_introuvable_nom').value = nomRecherche;
            
            // Nettoyer feedback précédent
            document.getElementById('gerer_feedback').style.display = 'none';
            document.getElementById('gerer_input_group').style.display = 'block';
            let submitBtn = document.getElementById('btn-submit-gerer');
            submitBtn.style.display = 'block';
            submitBtn.innerHTML = '<i class="fas fa-search me-1"></i> Rechercher';
            submitBtn.disabled = false;
            
            let modalEl = document.getElementById('modalGererIntrouvable');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
            
            // Déclencher automatiquement la recherche dans la modale
            setTimeout(() => {
                let fakeEvent = new Event('submit');
                executerGererIntrouvableAjax(fakeEvent);
            }, 300);
            
            // Réinitialiser le champ du formulaire
            formElement.reset();
        } else {
            alert("Erreur : " + (data.message || "Impossible de créer l'entité."));
        }
    })
    .catch(error => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert("Erreur réseau : " + error.message);
    });
}
</script>
<script>
function ouvrirSaisieEtrangere() {
    let btnGroup = document.getElementById('gerer_input_group');
    if(btnGroup) btnGroup.style.display = 'none';
    
    // Hide the 'Plan B' button
    let planB = document.querySelector('#modalGererIntrouvable .btn-outline-primary');
    if(planB) planB.closest('.mb-2').style.display = 'none';
    
    document.getElementById('etranger_form').style.display = 'block';
}

function validerEtrangerAjax() {
    let sourceId = document.getElementById('gerer_introuvable_id').value;
    let originalName = document.getElementById('gerer_introuvable_nom').value;
    let adresse = document.getElementById('etranger_adresse').value;
    
    let fd = new FormData();
    fd.append('source_id', sourceId);
    fd.append('nom', originalName);
    fd.append('adresse', adresse);
    
    fetch('index.php?action=api_valider_etranger', {
        method: 'POST',
        body: fd
    }).then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    }).catch(e => {
        alert('Erreur réseau');
    });
}
</script>

<!-- Modal Generic Delete (Liens) -->
<div class="modal modal-blur fade" id="modalGenericDelete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-danger display-4 mb-3"></i>
                <h3>Confirmation</h3>
                <div class="text-muted mb-3" id="genericDeleteMessage">
                    Êtes-vous sûr de vouloir continuer ?
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100 btn-pill" data-bs-dismiss="modal">Annuler</a></div>
                        <div class="col">
                            <a href="#" id="genericDeleteBtn" class="btn btn-danger w-100 btn-pill">Supprimer</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function confirmGenericDelete(url, message) {
    document.getElementById('genericDeleteMessage').innerText = message;
    let btn = document.getElementById('genericDeleteBtn');
    btn.onclick = null;
}
</script>

<!-- Modal d'Aide & Explication des Traitements Scope 3 -->
<div class="modal modal-blur fade" id="modalAideTraitements" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fs-3" id="modalAideTitre"><i class="fas fa-info-circle text-primary me-2"></i> Explication de la fonctionnalité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4" id="modalAideCorps">
                <!-- Contenu injecté dynamiquement par afficherAideTraitement() -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">J'ai compris</button>
            </div>
        </div>
    </div>
</div>

<script>
function afficherAideTraitement(type) {
    const aides = {
        'distances': {
            titre: '<i class="fas fa-map-marker-alt text-purple me-2"></i> Maj Distances (Villes)',
            corps: `<p class="mb-3">Cette fonctionnalité permet de <strong>recalculer automatiquement les distances kilométriques routières</strong> pour toutes les entreprises de la base :</p>
                    <ul class="mb-3">
                        <li class="mb-2"><strong>Origine :</strong> Utilise les coordonnées GPS exactes du siège de votre société enregistrée en base de données.</li>
                        <li class="mb-2"><strong>Destination :</strong> Exploite la latitude et longitude officielles du fournisseur issues de l'API Open Data Gouv (ou du géocodage OpenStreetMap).</li>
                        <li class="mb-2"><strong>Moteur routier (OSRM) :</strong> Calcule la distance de trajet par la route en kilomètres via le serveur OpenStreetMap OSRM (avec repli en vol d'oiseau si le réseau routier est injoignable).</li>
                        <li class="mb-2"><strong>Actualisation :</strong> Met à jour simultanément la distance, l'alliance géographique (Locale, Régionale, Nationale...) et répare les anomalies GPS du tableau de bord.</li>
                    </ul>
                    <div class="alert alert-info py-2 mb-0"><i class="fas fa-info-circle me-1"></i> Ce traitement parcourt l'ensemble des enregistrements nécessitant une réparation sans limitation de volume.</div>`
        },
        'naf': {
            titre: '<i class="fas fa-book text-pink me-2"></i> Maj NAF (INSEE)',
            corps: `<p class="mb-3">Cette fonctionnalité synchronise les <strong>secteurs d'activité</strong> de toutes vos entreprises avec la nomenclature officielle de l'INSEE :</p>
                    <ul class="mb-3">
                        <li class="mb-2"><strong>Normalisation :</strong> Fait correspondre chaque code NAF (ex: <code>46.31Z</code>) avec son libellé officiel complet stocké dans la table <code>codes_naf</code>.</li>
                        <li class="mb-2"><strong>Opposabilité :</strong> Permet d'assurer la cohérence et l'exactitude des facteurs d'émissions monétaires ADEME associés aux activités pour votre Bilan Carbone officiel.</li>
                    </ul>`
        },
        'connus': {
            titre: '<i class="fas fa-magic text-green me-2"></i> Validation Auto (Siren connus)',
            corps: `<p class="mb-3">Cette fonctionnalité permet de <strong>valider en un clic</strong> les fournisseurs récurrents en attente :</p>
                    <ul class="mb-3">
                        <li class="mb-2"><strong>Détection intelligente :</strong> Parcourt les lignes du sous-onglet <em>Rapprochement</em> et compare leur SIREN avec la table de vos partenaires connus (<code>siren_connus</code>).</li>
                        <li class="mb-2"><strong>Gain de temps :</strong> Si une entreprise a déjà été identifiée et validée par le passé, elle passe automatiquement au statut <strong>Validée</strong> sans intervention manuelle répétitive.</li>
                    </ul>`
        },
        'introuvables': {
            titre: '<i class="fas fa-broom text-warning me-2"></i> Nettoyer & Relancer (Introuvables)',
            corps: `<p class="mb-3">Cette fonctionnalité permet de <strong>résoudre et récupérer les entités introuvables</strong> :</p>
                    <ul class="mb-3">
                        <li class="mb-2"><strong>Nettoyage algorithmique :</strong> Supprime automatiquement des noms de recherche les mentions juridiques polluantes (<em>SARL, SAS, SA, SASU, EURL, SCI, FRANCE, SERVICES</em>...) ou les numéros de facturation.</li>
                        <li class="mb-2"><strong>Nouvelle requête API :</strong> Relance l'interrogation de l'API Open Data Gouv.fr avec le nom épuré.</li>
                        <li class="mb-2"><strong>Résultat :</strong> Les entreprises retrouvées sont réinjectées directement dans le circuit (soit en attente de rapprochement, soit validées d'office).</li>
                    </ul>`
        },
        'geo': {
            titre: '<i class="fas fa-globe text-lime me-2"></i> Maj Origines Géo',
            corps: `<p class="mb-3">Cette fonctionnalité recalcule la <strong>segmentation territoriale RSE</strong> de vos achats :</p>
                    <ul class="mb-3">
                        <li class="mb-2"><span class="badge bg-green text-white me-1">Alliance Locale</span> Fournisseurs situés à <strong>moins de 50 km</strong> de votre siège.</li>
                        <li class="mb-2"><span class="badge bg-yellow text-white me-1">Régionale</span> Fournisseurs situés entre <strong>50 km et 200 km</strong>.</li>
                        <li class="mb-2"><span class="badge bg-blue text-white me-1">Nationale</span> Fournisseurs français situés à <strong>plus de 200 km</strong>.</li>
                        <li class="mb-2"><span class="badge bg-purple text-white me-1">Internationale</span> Fournisseurs établis hors de France (code pays étranger ou SIREN étranger).</li>
                    </ul>`
        },
        'general': {
            titre: '<i class="fas fa-cogs text-primary me-2"></i> Guide des Traitements Scope 3',
            corps: `<p class="mb-3">Les <strong>traitements par lots</strong> permettent de qualifier, enrichir et corriger massivement les données du Scope 3 :</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-2">
                            <strong class="text-purple"><i class="fas fa-map-marker-alt me-1"></i> Maj Distances (Villes) :</strong> Recalcule les trajets routiers réels (OSRM) depuis votre siège vers les fournisseurs et résout les anomalies GPS.
                        </div>
                        <div class="list-group-item px-0 py-2">
                            <strong class="text-pink"><i class="fas fa-book me-1"></i> Maj NAF (INSEE) :</strong> Aligne les codes d'activité sur la nomenclature officielle de l'INSEE pour fiabiliser le ratio monétaire ADEME.
                        </div>
                        <div class="list-group-item px-0 py-2">
                            <strong class="text-green"><i class="fas fa-magic me-1"></i> Validation Auto (Siren connus) :</strong> Valide instantanément les entités en attente dont le SIREN est déjà reconnu dans votre historique.
                        </div>
                        <div class="list-group-item px-0 py-2">
                            <strong class="text-warning"><i class="fas fa-broom me-1"></i> Nettoyer & Relancer (Introuvables) :</strong> Épurgé les dénominations d'entreprises (suppression SARL, SAS, etc.) et réinterroge l'API Open Data Gouv.
                        </div>
                        <div class="list-group-item px-0 py-2">
                            <strong class="text-lime"><i class="fas fa-globe me-1"></i> Maj Origines Géo :</strong> Attribue les labels territoriaux (Alliance Locale, Régionale, Nationale, Internationale) selon la distance.
                        </div>
                    </div>`
        }
    };

    let item = aides[type] || aides['general'];
    document.getElementById('modalAideTitre').innerHTML = item.titre;
    document.getElementById('modalAideCorps').innerHTML = item.corps;
    new bootstrap.Modal(document.getElementById('modalAideTraitements')).show();
}
</script>