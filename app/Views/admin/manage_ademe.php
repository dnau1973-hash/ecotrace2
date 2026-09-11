<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Administration</div>
                <h2 class="page-title"><i class="fas fa-leaf me-2 text-green"></i> Base Empreinte ADEME (Facteurs Monétaires)</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="?" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i> Retour</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible" role="alert">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Correspondance Codes NAF / Ratios CO2</h3>
            </div>
            <div class="card-body border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="text-muted">
                        Recherche rapide :
                        <div class="ms-2 d-inline-block">
                            <input type="text" id="searchAdeme" class="form-control form-control-sm" aria-label="Rechercher" placeholder="Code ou mot clé...">
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="?action=save_ademe">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table card-table table-vcenter table-striped text-nowrap" id="ademeTable">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Code NAF</th>
                                <th>Libellé Officiel</th>
                                <th style="width: 250px;">Facteur d'Émission (kgCO2e / €)</th>
                                <th>Dernière MAJ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ademeList as $item): ?>
                            <tr class="ademe-row">
                                <td><span class="text-muted fw-bold"><?= htmlspecialchars($item['code']) ?></span></td>
                                <td class="ademe-libelle text-wrap text-muted" style="font-size: 13px;"><?= htmlspecialchars($item['libelle']) ?></td>
                                <td>
                                    <input type="number" step="0.0001" min="0" class="form-control" name="facteurs[<?= htmlspecialchars($item['code']) ?>]" value="<?= htmlspecialchars(number_format($item['facteur'], 4, '.', '')) ?>">
                                </td>
                                <td class="text-muted small">
                                    <?= $item['mis_a_jour_le'] ? date('d/m/Y H:i', strtotime($item['mis_a_jour_le'])) : 'N/A' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Enregistrer les modifications</button>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchAdeme');
    const rows = document.querySelectorAll('.ademe-row');

    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        rows.forEach(row => {
            const code = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const libelle = row.querySelector('.ademe-libelle').textContent.toLowerCase();
            if (code.includes(term) || libelle.includes(term)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
