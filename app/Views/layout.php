<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>EcoTrace 🍃 - Dashboard Multi-Sociétés</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f4f7f6; }
        h1, .navbar-brand { font-family: 'Fredoka', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .subtab-content { display: none; }
        .subtab-content.active { display: block; }
        
        /* MASQUE DE CHARGEMENT GLOBAL */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #ffffff;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.4s ease-out;
        }
    </style>
</head>
<body class="layout-fluid">

    <!-- OVERLAY DE CHARGEMENT -->
    <div id="page-loader">
        <div class="spinner-border text-green mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h2 class="text-muted" style="font-family: 'Fredoka', sans-serif;">Traitement en cours...</h2>
    </div>

    <div class="page">
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="?" class="text-decoration-none text-green d-flex align-items-center">
                        <i class="fas fa-leaf me-2 fs-2"></i> EcoTrace
                    </a>
                    <span class="badge bg-green-lt ms-2" style="font-size: 10px;">v2.1.0 Multi</span>
                </h1>
<div class="navbar-nav flex-row order-md-last ms-auto">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Ouvrir le menu">
                            <span class="avatar avatar-sm bg-green-lt"><i class="fas fa-cogs"></i></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= htmlspecialchars($_SESSION['ecotrace_user_nom'] ?? 'Utilisateur') ?></div>
                                <div class="mt-1 small text-muted">
                                    <?php
                                    $roleLabel = match($_SESSION['ecotrace_user_role'] ?? '') {
                                        'superadmin' => '👑 Super Admin',
                                        'admin'      => '🔧 Administrateur',
                                        'lecteur'    => '👁 Lecteur',
                                        default      => 'Connecté'
                                    };
                                    echo $roleLabel;
                                    ?>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="#" class="dropdown-item" onclick="openTab('tab-doc')"><i class="fas fa-book me-2"></i>Documentation</a>
                            <a href="#" class="dropdown-item" onclick="openTab('tab-changelog')"><i class="fas fa-history me-2"></i>Historique (Changelog)</a>
                            <a href="?action=manage_societes" class="dropdown-item text-primary fw-bold"><i class="fas fa-building me-2"></i>Gérer les Sociétés du groupe</a>
                            <a href="?action=admin_database" class="dropdown-item text-purple fw-bold"><i class="fas fa-database me-2"></i>Administration BDD</a>
                            <a href="?action=manage_naf" class="dropdown-item text-indigo fw-bold"><i class="fas fa-tags me-2"></i>Gérer les Codes NAF</a>
                            <?php if (($_SESSION['ecotrace_user_role'] ?? '') === 'superadmin'): ?>
                            <a href="?action=manage_users" class="dropdown-item text-danger fw-bold"><i class="fas fa-users-cog me-2"></i>Gérer les Utilisateurs</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>

                            
                            <div class="dropstart">
                                <a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fas fa-download me-2"></i> Imports
                                </a>
                                <div class="dropdown-menu">
                                    <a href="?action=import_csv" class="dropdown-item text-blue"><i class="fas fa-file-excel me-2"></i>Importer CSV enrichi</a>
                                    <a href="?action=import_siren" class="dropdown-item text-teal"><i class="fas fa-highlighter me-2"></i>Surligner via SIREN</a>
                                    <a href="?action=import_sql" class="dropdown-item text-red"><i class="fas fa-database-import me-2"></i>Importer Dump (SQL)</a>
                                </div>
                            </div>

                            <div class="dropstart mt-1">
                                <a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fas fa-upload me-2"></i> Exports
                                </a>
                                <div class="dropdown-menu">
                                    <a href="?action=export_csv" class="dropdown-item text-success"><i class="fas fa-file-csv me-2"></i>Export Validées (CSV)</a>
                                    <a href="?action=export_sql" class="dropdown-item text-orange"><i class="fas fa-database-export me-2"></i>Sauvegarde complète (SQL)</a>
                                </div>
                            </div>

                            <div class="dropstart mt-1">
                                <a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fas fa-chart-line me-2"></i> Traitements
                                </a>
                                <div class="dropdown-menu">
                                    <a href="#" onclick="lancerMajDistances()" class="dropdown-item text-purple"><i class="fas fa-map-marker-alt me-2"></i>Maj Distances (Villes)</a>
                                    <a href="#" onclick="lancerMajNaf()" class="dropdown-item text-pink"><i class="fas fa-book me-2"></i>Maj NAF (INSEE)</a>
                                    <a href="#" onclick="lancerMajGeo()" class="dropdown-item text-lime"><i class="fas fa-globe me-2"></i>Maj Origines Géo</a>
                                    <a href="#" onclick="lancerValidationAuto()" class="dropdown-item text-green"><i class="fas fa-magic me-2"></i>Validation Auto (Siren connus)</a>
                                    <div class="dropdown-divider"></div>
                                    <a href="#" onclick="lancerRelanceIntrouvables()" class="dropdown-item text-warning"><i class="fas fa-broom me-2"></i>Nettoyer & Relancer (Introuvables)</a>
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item text-danger fw-bold" onclick="confirmerViderTables()"><i class="fas fa-trash-x me-2"></i> Vider la base de données</a>
                            <a href="?edit_config=1" class="dropdown-item text-blue"><i class="fas fa-tools me-2"></i> Éditer la configuration</a>
                            <a href="?logout=1" class="dropdown-item text-red"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <header class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item active" id="nav-tab-stats">
                                <a class="nav-link" href="#" onclick="openTab('tab-stats', this)">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fas fa-chart-pie text-green"></i></span>
                                    <span class="nav-link-title">Statistiques RSE</span>
                                </a>
                            </li>
                            <li class="nav-item" id="nav-tab-scope1">
                                <a class="nav-link" href="#" onclick="openTab('tab-scope1', this)">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fas fa-car text-blue"></i></span>
                                    <span class="nav-link-title">Scope 1 &amp; 2 (Direct &amp; Flotte)</span>
                                    <span class="badge bg-blue text-white ms-2"><?= count($flotteVehicules ?? []) ?> véh.</span>
                                </a>
                            </li>
                            <li class="nav-item" id="nav-tab-scope3">
                                <a class="nav-link" href="#" onclick="openTab('tab-scope3', this)">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fas fa-truck text-purple"></i></span>
                                    <span class="nav-link-title">Scope 3 (Achats &amp; Fret)</span>
                                    <span class="badge bg-purple text-white ms-2"><?= count($sourcesValides ?? []) ?></span>
                                </a>
                            </li>
                            <li class="nav-item" id="nav-tab-comprendre-carbone">
                                <a class="nav-link" href="#" onclick="openTab('tab-comprendre-carbone', this)">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fas fa-lightbulb text-warning"></i></span>
                                    <span class="nav-link-title">Comprendre le Bilan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-teal fw-bold" href="?action=report" target="_blank">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fas fa-file-invoice"></i></span>
                                    <span class="nav-link-title">Bilan Carbone Officiel</span>
                                </a>
                            </li>
                        </ul>

                <!-- SÉLECTEUR GLOBAL DE SOCIÉTÉ -->
                <div class="ms-auto d-flex align-items-center py-2">
                    <div class="dropdown">
                        <?php 
                        $currSocId = $_SESSION['active_societe_id'] ?? 'all';
                        $currNom = '🏢 Vue Consolidée (Toutes)';
                        if ($currSocId !== 'all' && !empty($societes)) {
                            foreach ($societes as $soc) {
                                if ($soc['id'] == $currSocId) {
                                    $currNom = '🏬 ' . htmlspecialchars($soc['nom']);
                                    break;
                                }
                            }
                        }
                        ?>
                        <button class="btn btn-outline-primary dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= $currNom ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">Filtrer par Entité</h6></li>
                            <li>
                                <a class="dropdown-item <?= $currSocId === 'all' ? 'active fw-bold' : '' ?>" href="?action=change_societe&societe_id=all">
                                    🏢 Vue Consolidée (Toutes les sociétés)
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (!empty($societes)): ?>
                                <?php foreach ($societes as $soc): ?>
                                    <li>
                                        <a class="dropdown-item <?= $currSocId == $soc['id'] ? 'active fw-bold' : '' ?>" href="?action=change_societe&societe_id=<?= $soc['id'] ?>">
                                            🏬 <?= htmlspecialchars($soc['nom']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <?= $content ?>
                </div>
            </div>

            <!-- FOOTER ENRICHI -->
            <footer class="footer footer-transparent d-print-none mt-auto py-4">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-12 mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0 fw-bold text-muted">
                                <li class="list-inline-item">EcoTrace remercie la communauté open-source ❤️</li>
                                <li class="list-inline-item">
                                    <a href="https://github.com/VOTRE_COMPTE/EcoTrace" target="_blank" class="link-secondary d-inline-flex align-items-center" rel="noopener" title="Voir le code source">
                                        <i class="fab fa-github fs-2 me-1"></i> Projet sur GitHub
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="mt-3 d-flex justify-content-center align-items-center flex-wrap gap-4 text-muted" style="opacity: 0.6; filter: grayscale(100%);">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" height="24" title="PHP">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/mysql/mysql-original-wordmark.svg" height="24" title="MySQL">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/docker/docker-original-wordmark.svg" height="24" title="Docker">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/apache/apache-original-wordmark.svg" height="24" title="Apache">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/git/git-original.svg" height="24" title="Git">
                                
                                <span class="d-flex align-items-center fw-bold" title="Cartographie OpenStreetMap" style="font-size: 15px;">
                                    <i class="fas fa-map me-1 fs-2"></i> OpenStreetMap
                                </span>
                                <span class="d-flex align-items-center fw-bold" title="Données publiques françaises" style="font-size: 15px;">
                                    <i class="fas fa-database me-1 fs-2"></i> OpenData.gouv
                                </span>
                                <span class="d-flex align-items-center fw-bold" title="Interface Utilisateur Tabler" style="font-size: 15px;">
                                    <i class="fas fa-layer-group me-1 fs-2"></i> Tabler.io
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- MODAL VIDER LA BASE AVEC TOUTES LES TABLES -->
    <div class="modal modal-blur fade" id="modalViderTables" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                        <h3 class="text-danger fw-bold mt-2">Nettoyage de la base de données</h3>
                        <div class="text-muted">Sélectionnez les tables que vous souhaitez réinitialiser. Cette action est irréversible.</div>
                    </div>

                    <form id="formViderTables">
                        <div class="card bg-light border-0 p-3 mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="tables[]" value="sources_csv" id="chk_sources" checked>
                                <label class="form-check-label fw-bold" for="chk_sources">
                                    📊 Données d'achats / Imports CSV (`sources_csv`)
                                </label>
                                <div class="text-muted small">Efface la file d'attente, les doublons et l'historique d'import.</div>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="tables[]" value="api_resultats" id="chk_api" checked>
                                <label class="form-check-label fw-bold" for="chk_api">
                                    🔍 Résultats API & Rapprochements (`api_resultats`)
                                </label>
                                <div class="text-muted small">Efface les données d'entreprises récupérées via les API.</div>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="tables[]" value="siren_connus" id="chk_siren">
                                <label class="form-check-label fw-bold text-teal" for="chk_siren">
                                    🏷️ Liste des SIREN Connus (`siren_connus`)
                                </label>
                                <div class="text-muted small">Efface le dictionnaire des SIREN surlignés/prédéfinis.</div>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="tables[]" value="codes_naf" id="chk_naf">
                                <label class="form-check-label fw-bold text-purple" for="chk_naf">
                                    📖 Dictionnaire des codes NAF (`codes_naf`)
                                </label>
                                <div class="text-muted small">Efface les libellés officiels d'activités de l'INSEE.</div>
                            </div>

                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="tables[]" value="societes" id="chk_societes">
                                <label class="form-check-label fw-bold text-danger" for="chk_societes">
                                    🏢 Liste des Sociétés & Filiales (`societes`)
                                </label>
                                <div class="text-muted small">Attention : supprime toutes les filiales (conserve l'entité par défaut #1).</div>
                            </div>
                        </div>
                        <div id="vider-feedback" class="text-danger small mb-2 text-center"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-vider" onclick="executerViderTables()">
                        <i class="fas fa-trash me-1"></i> Vider les tables sélectionnées
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL MAJ DISTANCES -->
    <div class="modal modal-blur fade" id="modalMajDistances" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-purple-lt">
                    <h5 class="modal-title text-purple"><i class="fas fa-map-marker-alt me-2"></i> Mise à jour des distances GPS</h5>
                </div>
                <div class="modal-body text-center py-4" id="majDistancesContent">
                    <div id="majDistancesInit">
                        <div class="spinner-border text-purple mb-3"></div>
                        <div>Recherche des anomalies...</div>
                    </div>
                    <div id="majDistancesProgress" style="display: none;">
                        <h3 class="mb-3">Réparation... <span id="majDistancesText" class="text-purple fw-bold">0 / 0</span></h3>
                        <div class="progress progress-lg mb-2">
                            <div id="majDistancesBar" class="progress-bar progress-bar-striped progress-bar-animated bg-purple" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="majDistancesResult" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="majDistancesFooter" style="display: none;">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Rafraîchir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL MAJ GEO -->
    <div class="modal modal-blur fade" id="modalMajGeo" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-lime-lt">
                    <h5 class="modal-title text-lime"><i class="fas fa-globe me-2"></i> Mise à jour des Origines Géo</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="majGeoInit">
                        <div class="spinner-border text-lime mb-3"></div>
                        <div>Chargement de l'historique...</div>
                    </div>
                    <div id="majGeoProgress" style="display: none;">
                        <h3 class="mb-3">Analyse des adresses... <span id="majGeoText" class="text-lime fw-bold">0 / 0</span></h3>
                        <div class="progress progress-lg mb-2">
                            <div id="majGeoBar" class="progress-bar progress-bar-striped progress-bar-animated bg-lime" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="majGeoResult" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="majGeoFooter" style="display: none;">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Rafraîchir</button>
                </div>
            </div>
        </div>
    </div>

        <!-- MODAL VALIDATION AUTO -->
    <div class="modal modal-blur fade" id="modalValidationAuto" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-green-lt">
                    <h5 class="modal-title text-green"><i class="fas fa-magic me-2"></i> Validation Automatique</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="valAutoProgress">
                        <div class="spinner-border text-green mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h3 class="mb-1 text-green">Rapprochement en cours...</h3>
                        <div class="text-muted small">Validation des entreprises déjà connues dans votre historique.</div>
                    </div>
                    <div id="valAutoResult" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="valAutoFooter" style="display: none;">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Rafraîchir</button>
                </div>
            </div>
        </div>
    </div>

        <!-- MODAL RELANCE INTROUVABLES -->
    <div class="modal modal-blur fade" id="modalRelanceIntrouvables" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning-lt">
                    <h5 class="modal-title text-warning"><i class="fas fa-broom me-2"></i> Nettoyage des Introuvables</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="relanceProgress">
                        <div class="spinner-border text-warning mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h3 class="mb-1 text-warning">Nettoyage en cours...</h3>
                        <div class="text-muted small">Suppression des mots parasites (SARL, SAS...) et relance API.</div>
                    </div>
                    <div id="relanceResult" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="relanceFooter" style="display: none;">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Rafraîchir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL MAJ NAF -->
    <div class="modal modal-blur fade" id="modalMajNaf" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-pink-lt">
                    <h5 class="modal-title text-pink"><i class="fas fa-book me-2"></i> Mise à jour du dictionnaire NAF</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="majNafProgress">
                        <div class="spinner-border text-pink mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h3 class="mb-1 text-pink">Synchronisation SQL en cours...</h3>
                        <div class="text-muted small">Application des libellés officiels de l'INSEE.</div>
                    </div>
                    <div id="majNafResult" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="majNafFooter" style="display: none;">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal" onclick="location.reload()">Fermer et Rafraîchir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script src="js/app.js"></script>
    <script>
        function openTab(tabName, element) {
            var tabcontent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            var tablinks = document.querySelectorAll(".navbar-nav .nav-item");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            var targetContent = document.getElementById(tabName);
            if (targetContent) {
                targetContent.classList.add("active");
                if (element) {
                    element.closest('.nav-item').classList.add("active");
                } else {
                    var btn = document.querySelector(".nav-link[onclick*='" + tabName + "']");
                    if (btn) btn.closest('.nav-item').classList.add("active");
                }
                localStorage.setItem('activeTabEcotrace', tabName);
            } else {
                localStorage.setItem('activeTabEcotrace', tabName);
                window.location.href = 'index.php';
            }
        }

        function openSubTab(subTabId, element) {
            if (window.event) window.event.preventDefault();
            var target = document.getElementById(subTabId);
            if (!target) return;

            var parentTab = target.closest('.tab-content, .scope3-main-tab');
            if (parentTab) {
                parentTab.querySelectorAll('.subtab-content').forEach(function(el) {
                    if (el.closest('.tab-content, .scope3-main-tab') === parentTab) {
                        el.classList.remove('active');
                    }
                });
            }

            if (element) {
                var navPills = element.closest('.nav-pills');
                if (navPills) {
                    navPills.querySelectorAll('.nav-link').forEach(function(l) {
                        l.classList.remove('active');
                    });
                }
                element.classList.add('active');
            }

            target.classList.add('active');
            if (parentTab) {
                localStorage.setItem('activeSubTab_' + parentTab.id, subTabId);
            }
        }

        // --------------------------------------------------------
        // GESTION DU SCROLL ET DU MASQUE DE CHARGEMENT
        // --------------------------------------------------------
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        let ecoIsNavigating = false;

        document.addEventListener('submit', function(e) {
            // EXCEPTION POUR L'IMPORT : On n'affiche pas le masque blanc sur un envoi de fichier
            if (e.target.querySelector('input[type="file"]') || e.target.classList.contains('no-loader')) {
                return;
            }

            ecoIsNavigating = true;
            localStorage.setItem('ecotrace_scroll_pos', window.scrollY);
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.visibility = 'visible';
                loader.style.opacity = '1';
                loader.style.display = 'flex';
            }
        });

        window.addEventListener('load', (event) => { 
            const savedTab = localStorage.getItem('activeTabEcotrace'); 
            if (savedTab && document.getElementById(savedTab)) { 
                openTab(savedTab, null); 
            }
            
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                    loader.style.visibility = 'hidden';
                    loader.style.pointerEvents = 'none';
                }, 400);
            }
        });

        // --------------------------------------------------------
        // REMISE À ZÉRO DE LA BASE DE DONNÉES
        // --------------------------------------------------------
        function confirmerViderTables() {
            let modalEl = document.getElementById('modalViderTables');
            let modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }

        function executerViderTables() {
            let form = document.getElementById('formViderTables');
            let formData = new FormData(form);
            formData.append('action', 'api_empty_tables');

            let checkboxes = form.querySelectorAll('input[name="tables[]"]:checked');
            if (checkboxes.length === 0) {
                document.getElementById('vider-feedback').innerText = 'Veuillez cocher au moins une table à vider.';
                return;
            }

            let btn = document.getElementById('btn-confirm-vider');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Suppression...';
            btn.disabled = true;

            fetch('index.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '?';
                } else {
                    document.getElementById('vider-feedback').innerText = data.message || "Erreur lors de la suppression.";
                    btn.innerHTML = '<i class="fas fa-trash me-1"></i> Vider les tables sélectionnées';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                document.getElementById('vider-feedback').innerText = "Erreur de communication avec le serveur.";
                btn.innerHTML = '<i class="fas fa-trash me-1"></i> Vider les tables sélectionnées';
                btn.disabled = false;
            });
        }

        // --------------------------------------------------------
        // SCRIPTS DE MAINTENANCE (GPS, GEO, NAF)
        // --------------------------------------------------------
        async function lancerMajDistances() {
            document.getElementById('majDistancesInit').style.display = 'block';
            document.getElementById('majDistancesProgress').style.display = 'none';
            document.getElementById('majDistancesResult').style.display = 'none';
            document.getElementById('majDistancesFooter').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalMajDistances')).show();
            
            try {
                let res = await fetch('?action=api_get_distances_todo');
                let result = await res.json();
                let items = result.data;
                let total = items.length;
                
                if (total === 0) {
                    document.getElementById('majDistancesInit').style.display = 'none';
                    document.getElementById('majDistancesResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Tout est parfait !</h3>';
                    document.getElementById('majDistancesResult').style.display = 'block';
                    document.getElementById('majDistancesFooter').style.display = 'block';
                    return;
                }
                
                document.getElementById('majDistancesInit').style.display = 'none';
                document.getElementById('majDistancesProgress').style.display = 'block';
                let done = 0;
                let successCount = 0;
                
                for (let item of items) {
                    let fd = new FormData();
                    fd.append('id', item.id);
                    fd.append('adresse', item.siege_adresse);
                    let sRes = await fetch('?action=api_fix_distance', { method: 'POST', body: fd });
                    let sData = await sRes.json();
                    if (sData.success) successCount++;
                    done++;
                    let pct = Math.round((done / total) * 100);
                    document.getElementById('majDistancesBar').style.width = pct + '%';
                    document.getElementById('majDistancesText').innerText = done + ' / ' + total;
                }
                
                setTimeout(() => {
                    document.getElementById('majDistancesProgress').style.display = 'none';
                    document.getElementById('majDistancesResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Terminé !</h3><p class="text-muted"><strong>' + successCount + '</strong> réparations.</p>';
                    document.getElementById('majDistancesResult').style.display = 'block';
                    document.getElementById('majDistancesFooter').style.display = 'block';
                }, 800);
                        } catch (e) {
                let prog = document.getElementById('majDistancesProgress');
                if (prog) prog.style.display = 'none';
                let res = document.getElementById('majDistancesResult');
                if (res) {
                    res.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-wifi"></i></div><h3 class="text-danger">Erreur de connexion</h3><p class="text-muted">Impossible de joindre le serveur. Veuillez vérifier votre connexion ou réessayer plus tard.</p>';
                    res.style.display = 'block';
                }
                let foot = document.getElementById('majDistancesFooter');
                if (foot) foot.style.display = 'block';
            }
        }

        async function lancerMajGeo() {
            document.getElementById('majGeoInit').style.display = 'block';
            document.getElementById('majGeoProgress').style.display = 'none';
            document.getElementById('majGeoResult').style.display = 'none';
            document.getElementById('majGeoFooter').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalMajGeo')).show();
            
            try {
                let res = await fetch('?action=api_get_geo_todo');
                let result = await res.json();
                let items = result.data;
                let total = items.length;
                
                if (total === 0) {
                    document.getElementById('majGeoInit').style.display = 'none';
                    document.getElementById('majGeoResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Historique vide !</h3>';
                    document.getElementById('majGeoResult').style.display = 'block';
                    document.getElementById('majGeoFooter').style.display = 'block';
                    return;
                }
                
                document.getElementById('majGeoInit').style.display = 'none';
                document.getElementById('majGeoProgress').style.display = 'block';
                let done = 0;
                let successCount = 0;
                
                for (let item of items) {
                    let fd = new FormData();
                    fd.append('id', item.id);
                    fd.append('distance', item.distance);
                    fd.append('adresse', item.siege_adresse);
                    let sRes = await fetch('?action=api_fix_geo', { method: 'POST', body: fd });
                    let sData = await sRes.json();
                    if (sData.success) successCount++;
                    done++;
                    let pct = Math.round((done / total) * 100);
                    document.getElementById('majGeoBar').style.width = pct + '%';
                    document.getElementById('majGeoText').innerText = done + ' / ' + total;
                }
                
                setTimeout(() => {
                    document.getElementById('majGeoProgress').style.display = 'none';
                    document.getElementById('majGeoResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Terminé !</h3><p class="text-muted"><strong>' + successCount + '</strong> origines géo évaluées.</p>';
                    document.getElementById('majGeoResult').style.display = 'block';
                    document.getElementById('majGeoFooter').style.display = 'block';
                }, 500);
                        } catch (e) {
                let prog = document.getElementById('majGeoProgress');
                if (prog) prog.style.display = 'none';
                let res = document.getElementById('majGeoResult');
                if (res) {
                    res.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-wifi"></i></div><h3 class="text-danger">Erreur de connexion</h3><p class="text-muted">Impossible de joindre le serveur. Veuillez vérifier votre connexion ou réessayer plus tard.</p>';
                    res.style.display = 'block';
                }
                let foot = document.getElementById('majGeoFooter');
                if (foot) foot.style.display = 'block';
            }
        }

        async function lancerValidationAuto() {
            document.getElementById('valAutoProgress').style.display = 'block';
            document.getElementById('valAutoResult').style.display = 'none';
            document.getElementById('valAutoFooter').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalValidationAuto')).show();
            
            try {
                let res = await fetch('?action=api_valider_connus');
                let data = await res.json();
                
                setTimeout(() => {
                    document.getElementById('valAutoProgress').style.display = 'none';
                    if (data.success) {
                        document.getElementById('valAutoResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Validation réussie !</h3><p class="text-muted"><strong>' + data.count + '</strong> rapprochements automatiques effectués.</p>';
                    } else {
                        document.getElementById('valAutoResult').innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-exclamation-triangle"></i></div><h3 class="text-danger">Erreur</h3><p class="text-muted">' + data.message + '</p>';
                    }
                    document.getElementById('valAutoResult').style.display = 'block';
                    document.getElementById('valAutoFooter').style.display = 'block';
                }, 800);
                        } catch (e) {
                let prog = document.getElementById('valAutoProgress');
                if (prog) prog.style.display = 'none';
                let res = document.getElementById('valAutoResult');
                if (res) {
                    res.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-wifi"></i></div><h3 class="text-danger">Erreur de connexion</h3><p class="text-muted">Impossible de joindre le serveur. Veuillez vérifier votre connexion ou réessayer plus tard.</p>';
                    res.style.display = 'block';
                }
                let foot = document.getElementById('valAutoFooter');
                if (foot) foot.style.display = 'block';
            }
        }

                async function lancerRelanceIntrouvables() {
            document.getElementById('relanceProgress').style.display = 'block';
            document.getElementById('relanceResult').style.display = 'none';
            document.getElementById('relanceFooter').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalRelanceIntrouvables')).show();
            
            try {
                let res = await fetch('?action=api_relancer_introuvables');
                let data = await res.json();
                
                setTimeout(() => {
                    document.getElementById('relanceProgress').style.display = 'none';
                    let resEl = document.getElementById('relanceResult');
                    if (data.success) {
                        resEl.innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Nettoyage terminé !</h3><p class="text-muted"><strong>' + data.count + '</strong> entités ont été réparées et renvoyées en rapprochement.</p>';
                    } else {
                        resEl.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-exclamation-triangle"></i></div><h3 class="text-danger">Erreur</h3><p class="text-muted">' + data.message + '</p>';
                    }
                    resEl.style.display = 'block';
                    document.getElementById('relanceFooter').style.display = 'block';
                }, 800);
            } catch (e) {
                let prog = document.getElementById('relanceProgress');
                if (prog) prog.style.display = 'none';
                let resEl = document.getElementById('relanceResult');
                if (resEl) {
                    resEl.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-wifi"></i></div><h3 class="text-danger">Erreur de connexion</h3><p class="text-muted">Impossible de joindre le serveur. Veuillez vérifier votre connexion.</p>';
                    resEl.style.display = 'block';
                }
                let foot = document.getElementById('relanceFooter');
                if (foot) foot.style.display = 'block';
            }
        }

        async function lancerMajNaf() {
            document.getElementById('majNafProgress').style.display = 'block';
            document.getElementById('majNafResult').style.display = 'none';
            document.getElementById('majNafFooter').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalMajNaf')).show();
            
            try {
                let res = await fetch('?action=api_maj_naf');
                let data = await res.json();
                
                setTimeout(() => {
                    document.getElementById('majNafProgress').style.display = 'none';
                    if (data.success) {
                        document.getElementById('majNafResult').innerHTML = '<div class="display-4 text-success mb-2"><i class="fas fa-check"></i></div><h3 class="text-success">Base de données synchronisée !</h3><p class="text-muted"><strong>' + data.count + '</strong> entreprises ont reçu leur libellé officiel.</p>';
                    } else {
                        document.getElementById('majNafResult').innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-exclamation-triangle"></i></div><h3 class="text-danger">Erreur</h3><p class="text-muted">' + data.message + '</p>';
                    }
                    document.getElementById('majNafResult').style.display = 'block';
                    document.getElementById('majNafFooter').style.display = 'block';
                }, 800);
                        } catch (e) {
                let prog = document.getElementById('majNafProgress');
                if (prog) prog.style.display = 'none';
                let res = document.getElementById('majNafResult');
                if (res) {
                    res.innerHTML = '<div class="display-4 text-danger mb-2"><i class="fas fa-wifi"></i></div><h3 class="text-danger">Erreur de connexion</h3><p class="text-muted">Impossible de joindre le serveur. Veuillez vérifier votre connexion ou réessayer plus tard.</p>';
                    res.style.display = 'block';
                }
                let foot = document.getElementById('majNafFooter');
                if (foot) foot.style.display = 'block';
            }
        }
    </script>
</body>
</html>