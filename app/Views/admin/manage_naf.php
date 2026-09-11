<?php
function getSectorFromNaf($code) {
    $clean = preg_replace('/[^0-9]/', '', (string)$code);
    $div = substr($clean, 0, 2);
    $grp3 = substr($clean, 0, 3);
    $grp4 = substr($clean, 0, 4);

    // Alimentaire & Restauration
    if (in_array($div, ['01', '02', '03', '10', '11', '56']) || in_array($grp3, ['462', '463', '472', '478']) || in_array($grp4, ['4711'])) {
        return ['id' => 'alimentaire', 'label' => 'Alimentaire', 'icon' => 'fas fa-apple-alt', 'badge' => 'bg-yellow text-white'];
    }
    // Transport & Logistique
    if (in_array($div, ['49', '50', '51', '52', '53'])) {
        return ['id' => 'transport', 'label' => 'Transport & Fret', 'icon' => 'fas fa-truck', 'badge' => 'bg-blue text-white'];
    }
    // Énergie, Eau & Déchets
    if (in_array($div, ['35', '36', '37', '38', '39'])) {
        return ['id' => 'energie', 'label' => 'Énergie & Déchets', 'icon' => 'fas fa-bolt', 'badge' => 'bg-teal text-white'];
    }
    // BTP & Construction
    if (in_array($div, ['41', '42', '43'])) {
        return ['id' => 'btp', 'label' => 'BTP & Construction', 'icon' => 'fas fa-hard-hat', 'badge' => 'bg-orange text-white'];
    }
    // Numérique & Télécoms
    if (in_array($div, ['58', '59', '60', '61', '62', '63'])) {
        return ['id' => 'numerique', 'label' => 'Numérique & Tech', 'icon' => 'fas fa-laptop-code', 'badge' => 'bg-purple text-white'];
    }
    // Industrie & Matériaux
    if (in_array($div, ['13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33'])) {
        return ['id' => 'industrie', 'label' => 'Industrie & Fab.', 'icon' => 'fas fa-industry', 'badge' => 'bg-secondary text-white'];
    }
    // Services, Conseil & Tertiaire
    if (in_array($div, ['69', '70', '71', '72', '73', '74', '77', '78', '80', '81', '82'])) {
        return ['id' => 'conseil', 'label' => 'Services & Conseil', 'icon' => 'fas fa-briefcase', 'badge' => 'bg-azure text-white'];
    }
    return ['id' => 'autre', 'label' => 'Autre', 'icon' => 'fas fa-tag', 'badge' => 'bg-light text-muted'];
}

// Pré-comptage par secteur
$counts = [
    'all' => count($nafList),
    'alimentaire' => 0,
    'transport' => 0,
    'energie' => 0,
    'btp' => 0,
    'numerique' => 0,
    'industrie' => 0,
    'conseil' => 0,
    'autre' => 0,
    'selected' => 0
];

foreach ($nafList as $n) {
    $sec = getSectorFromNaf($n['code'])['id'];
    $counts[$sec] = ($counts[$sec] ?? 0) + 1;
    if (!empty($n['est_privilegie'])) {
        $counts['selected']++;
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administration</div>
                <h2 class="page-title"><i class="fas fa-tags me-2 text-purple"></i> Gestion des Codes NAF Privilégiés</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="?" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i> Retour au Dashboard</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible shadow-sm" role="alert">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST" action="?action=save_naf">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <div>
                        <h3 class="card-title m-0">
                            Sélectionnez les NAF à mettre en évidence 
                            <span class="badge bg-purple-lt ms-2">Couleur Violette</span>
                        </h3>
                        <div class="text-muted small mt-1">
                            <span id="labelSelectedCount" class="fw-bold text-purple"><?= $counts['selected'] ?></span> code(s) NAF actuellement privilégié(s) sur un total de <?= $counts['all'] ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Menu Déroulant de Sélection Rapide -->
                        <div class="dropdown">
                            <button class="btn btn-purple dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-magic me-1"></i> Sélection rapide par secteur
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 280px;">
                                <li class="dropdown-header text-uppercase fs-6">Secteur Alimentaire</li>
                                <li>
                                    <a class="dropdown-item text-yellow fw-bold" href="#" onclick="event.preventDefault(); cocherSecteur('alimentaire', true)">
                                        <i class="fas fa-plus-circle me-2"></i> Cocher tous les Alimentaires (<?= $counts['alimentaire'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-muted" href="#" onclick="event.preventDefault(); cocherSecteur('alimentaire', false)">
                                        <i class="fas fa-minus-circle me-2"></i> Décocher les Alimentaires
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="dropdown-header text-uppercase fs-6">Autres Grands Secteurs</li>
                                <li>
                                    <a class="dropdown-item text-blue" href="#" onclick="event.preventDefault(); cocherSecteur('transport', true)">
                                        <i class="fas fa-truck me-2"></i> + Cocher Transport & Fret (<?= $counts['transport'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-teal" href="#" onclick="event.preventDefault(); cocherSecteur('energie', true)">
                                        <i class="fas fa-bolt me-2"></i> + Cocher Énergie & Déchets (<?= $counts['energie'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-orange" href="#" onclick="event.preventDefault(); cocherSecteur('btp', true)">
                                        <i class="fas fa-hard-hat me-2"></i> + Cocher BTP & Construction (<?= $counts['btp'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-purple" href="#" onclick="event.preventDefault(); cocherSecteur('numerique', true)">
                                        <i class="fas fa-laptop-code me-2"></i> + Cocher Numérique & Tech (<?= $counts['numerique'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-azure" href="#" onclick="event.preventDefault(); cocherSecteur('conseil', true)">
                                        <i class="fas fa-briefcase me-2"></i> + Cocher Services & Conseil (<?= $counts['conseil'] ?>)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-secondary" href="#" onclick="event.preventDefault(); cocherSecteur('industrie', true)">
                                        <i class="fas fa-industry me-2"></i> + Cocher Industrie & Fab. (<?= $counts['industrie'] ?>)
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-success fw-bold" href="#" onclick="event.preventDefault(); cocherVisibles(true)">
                                        <i class="fas fa-check-double me-2"></i> Tout cocher (visibles)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="#" onclick="event.preventDefault(); cocherVisibles(false)">
                                        <i class="fas fa-times me-2"></i> Tout décocher (visibles)
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </div>

                <!-- Barre de Filtrage & Recherche -->
                <div class="card-body border-bottom bg-light py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="fas fa-search"></i></span>
                                <input type="text" id="searchNaf" class="form-control form-control-sm" placeholder="Rechercher code NAF ou mot clé...">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap gap-1 align-items-center justify-content-md-end">
                                <span class="text-muted small me-1">Filtre secteur :</span>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-primary" onclick="filtrerParSecteur('all', this)">
                                    Tous <span class="badge bg-white text-primary ms-1"><?= $counts['all'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('alimentaire', this)">
                                    🍏 Alimentaire <span class="badge bg-yellow text-white ms-1"><?= $counts['alimentaire'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('transport', this)">
                                    🚚 Transport <span class="badge bg-blue text-white ms-1"><?= $counts['transport'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('energie', this)">
                                    ⚡ Énergie <span class="badge bg-teal text-white ms-1"><?= $counts['energie'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('btp', this)">
                                    🏗️ BTP <span class="badge bg-orange text-white ms-1"><?= $counts['btp'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('numerique', this)">
                                    💻 Tech <span class="badge bg-purple text-white ms-1"><?= $counts['numerique'] ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-pill filter-pill btn-outline-secondary" onclick="filtrerParSecteur('selected', this)">
                                    ⭐ Sélectionnés <span class="badge bg-purple text-white ms-1" id="pillSelectedCount"><?= $counts['selected'] ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 620px; overflow-y: auto;">
                    <table class="table card-table table-vcenter table-hover table-striped text-nowrap" id="nafTable">
                        <thead class="sticky-top bg-white shadow-sm">
                            <tr>
                                <th class="w-1 text-center">
                                    <input class="form-check-input m-0 align-middle" type="checkbox" aria-label="Sélectionner tout" id="checkAllNaf" title="Tout cocher / décocher">
                                </th>
                                <th style="width: 140px;">Code NAF</th>
                                <th style="width: 180px;">Secteur</th>
                                <th>Libellé Officiel de l'Activité (INSEE)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nafList as $naf): ?>
                            <?php 
                                $sec = getSectorFromNaf($naf['code']); 
                                $isChecked = !empty($naf['est_privilegie']);
                            ?>
                            <tr class="naf-row <?= $isChecked ? 'table-purple' : '' ?>" data-sector="<?= $sec['id'] ?>">
                                <td class="text-center">
                                    <input class="form-check-input m-0 align-middle naf-check" type="checkbox" name="naf_codes[]" value="<?= htmlspecialchars($naf['code']) ?>" <?= $isChecked ? 'checked' : '' ?> onchange="onCheckboxChange(this)">
                                </td>
                                <td>
                                    <span class="fw-bold font-monospace text-dark"><?= htmlspecialchars($naf['code']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $sec['badge'] ?>" style="font-size: 11px;">
                                        <i class="<?= $sec['icon'] ?> me-1"></i> <?= htmlspecialchars($sec['label']) ?>
                                    </span>
                                </td>
                                <td class="naf-libelle text-wrap"><?= htmlspecialchars($naf['libelle']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between py-3 bg-light">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1 text-primary"></i> Les codes NAF cochés seront surlignés en <span class="badge bg-purple-lt">violet</span> lors du rapprochement des fournisseurs pour vous guider en priorité.
                    </div>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="fas fa-save me-2"></i> Enregistrer la sélection
                    </button>
                </div>
            </div>
        </form>
        
    </div>
</div>

<style>
.table-purple {
    background-color: rgba(112, 44, 169, 0.08) !important;
}
.naf-row:hover {
    background-color: rgba(112, 44, 169, 0.04);
}
</style>

<script>
let currentSectorFilter = 'all';

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchNaf');
    const checkAll = document.getElementById('checkAllNaf');

    searchInput.addEventListener('input', function() {
        appliquerFiltres();
    });

    checkAll.addEventListener('change', function(e) {
        cocherVisibles(e.target.checked);
    });

    updateCounters();
});

function onCheckboxChange(checkbox) {
    const row = checkbox.closest('.naf-row');
    if (checkbox.checked) {
        row.classList.add('table-purple');
    } else {
        row.classList.remove('table-purple');
    }
    updateCounters();
}

function appliquerFiltres() {
    const term = document.getElementById('searchNaf').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.naf-row');

    rows.forEach(row => {
        const code = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const libelle = row.querySelector('.naf-libelle').textContent.toLowerCase();
        const sector = row.getAttribute('data-sector');
        const isChecked = row.querySelector('.naf-check').checked;

        let matchSearch = !term || code.includes(term) || libelle.includes(term);
        let matchSector = (currentSectorFilter === 'all') || 
                          (currentSectorFilter === 'selected' && isChecked) || 
                          (currentSectorFilter === sector);

        if (matchSearch && matchSector) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filtrerParSecteur(secteur, btn) {
    currentSectorFilter = secteur;
    document.querySelectorAll('.filter-pill').forEach(p => {
        p.classList.remove('btn-primary');
        p.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
    }
    appliquerFiltres();
}

function cocherSecteur(secteur, checkState) {
    const rows = document.querySelectorAll('.naf-row');
    rows.forEach(row => {
        if (row.getAttribute('data-sector') === secteur) {
            const chk = row.querySelector('.naf-check');
            chk.checked = checkState;
            if (checkState) {
                row.classList.add('table-purple');
            } else {
                row.classList.remove('table-purple');
            }
        }
    });
    updateCounters();
}

function cocherVisibles(checkState) {
    const rows = document.querySelectorAll('.naf-row');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const chk = row.querySelector('.naf-check');
            chk.checked = checkState;
            if (checkState) {
                row.classList.add('table-purple');
            } else {
                row.classList.remove('table-purple');
            }
        }
    });
    updateCounters();
}

function updateCounters() {
    const allChecked = document.querySelectorAll('.naf-check:checked').length;
    const label = document.getElementById('labelSelectedCount');
    const pill = document.getElementById('pillSelectedCount');
    if (label) label.textContent = allChecked;
    if (pill) pill.textContent = allChecked;
}
</script>
