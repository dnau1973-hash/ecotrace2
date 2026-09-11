<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title text-primary">
                <i class="fas fa-database me-2"></i> Administration de la Base de Données
            </h2>
            <div class="text-muted mt-1">Vue d'ensemble et volumétrie des tables d'EcoTrace</div>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <a href="?action=export_sql" class="btn btn-primary">
                <i class="fas fa-download me-2"></i> Exporter un Dump SQL
            </a>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-title">Erreur d'accès à la base de données</h4>
        <div class="text-secondary"><?= htmlspecialchars($error) ?></div>
    </div>
<?php else: ?>
    
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-6">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-blue text-white avatar"><i class="fas fa-table"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Total des tables</div>
                            <div class="text-muted"><?= count($tables) ?> tables enregistrées</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-6">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar"><i class="fas fa-hdd"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Poids estimé des données</div>
                            <div class="text-muted"><?= $this->formatBytes($totalSize) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-mobile-md card-table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nom de la table</th>
                        <th class="text-end">Enregistrements</th>
                        <th class="text-end">Poids</th>
                        <th>Collation</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table): ?>
                    <tr>
                        <td data-label="Nom de la table">
                            <div class="d-flex py-1 align-items-center">
                                <span class="avatar me-2 bg-blue-lt"><i class="fas fa-table"></i></span>
                                <div class="flex-fill">
                                    <div class="font-weight-medium text-primary"><?= htmlspecialchars($table['name']) ?></div>
                                    <div class="text-muted"><a href="#" class="text-reset">Dernière maj : <?= htmlspecialchars($table['updated_at'] ?? $table['created_at'] ?? 'Inconnue') ?></a></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-end" data-label="Enregistrements">
                            <span class="badge bg-green-lt" style="font-size: 14px;"><?= number_format($table['exact_rows'], 0, ',', ' ') ?></span>
                        </td>
                        <td class="text-end text-muted" data-label="Poids">
                            <?= $this->formatBytes($table['size']) ?>
                        </td>
                        <td class="text-muted" data-label="Collation">
                            <?= htmlspecialchars($table['collation'] ?? 'N/A') ?>
                        </td>
                        <td class="text-end">
                            <?php if($table['exact_rows'] > 0): ?>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTruncateTable" onclick="document.getElementById('truncateTableName').innerText = '<?= htmlspecialchars($table['name']) ?>'; document.getElementById('truncateTableLink').href = '?action=admin_truncate_table&table=<?= urlencode($table['name']) ?>';">
                                <i class="fas fa-trash me-1"></i> Vider
                            </button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>Vider</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- Modal de confirmation de vidage -->
<div class="modal modal-blur fade" id="modalTruncateTable" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Êtes-vous sûr ?</div>
                <div>Vous êtes sur le point de vider intégralement la table <strong id="truncateTableName" class="text-danger"></strong>. Cette action est irréversible et supprimera toutes ses données.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="truncateTableLink" class="btn btn-danger">Oui, vider la table</a>
            </div>
        </div>
    </div>
</div>
